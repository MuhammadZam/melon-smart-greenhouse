# LAPORAN AUDIT MATEMATIS LOGIKA FUZZY (PHASE 15.1)
## SISTEM WEB MONITORING TANAMAN MELON
**Direktori Proyek:** `D:\Compro`  
**Tanggal Audit:** 17 Oktober 2026  
**Peran:** Senior Laravel Engineer, Fuzzy Logic Mathematical Engineer, SQA Engineer, Technical Documentation Auditor  
**Status:** VALIDATED & CORRECTED (PASS)  

---

## 1. RINGKASAN EKSEKUTIF & LATAR BELAKANG

Pada Phase 15, modul inferensi fuzzy Mamdani diimplementasikan untuk memberikan penilaian kualitas lingkungan greenhouse melon berbasis 4 parameter input (*temperature*, *humidity*, *soil_moisture*, *ph*) menghasilkan skor kondisi kontinu [0–100] yang diklasifikasikan ke dalam 3 kategori: `BURUK`, `PERLU_PERHATIAN`, dan `BAIK`.

Laporan dokumentasi Phase 15 (`docs/FUZZY_MANUAL_CALCULATION.md`) menyajikan contoh perhitungan manual untuk kondisi optimal ($T=27.0^\circ\text{C}, H=70.0\%, S=70.0\%, pH=6.5$) dengan klaim:
- Numerator = $2270.0$
- Denominator = $28.5$
- Skor Fuzzy = $79.65$ (`BAIK`)

Namun, berdasarkan audit matematis analitik independen pada right-shoulder trapezoid `BAIK` $[65.0, 80.0, 100.0, 100.0]$ dengan diskretisasi integer $z \in [0, 100]$ dan langkah $\Delta z = 1.0$:
- Numerator eksak = $2412.666667$
- Denominator eksak = $28.000000$
- Skor Centroid eksak = $86.166667 \approx 86.17$ (`BAIK`)

Phase 15.1 ini dilaksanakan untuk melakukan audit forensik menyeluruh, mengidentifikasi akar penyebab diskrepansi, mengeliminasi pembulatan prematur, memvalidasi kurva keanggotaan dan rule base, serta membangun **Oracle Matematika Independen** berbasis Python standard library yang terpisah dari engine Laravel.

---

## 2. AUDIT SOURCE CODE (12 POIN PENGECEKAN)

Pemeriksaan mendalam telah dilakukan terhadap seluruh file terkait logika fuzzy:

### 2.1. Implementasi Right-Shoulder Trapezoid
- **File:** `app/Services/FuzzyLogic/Membership/TrapezoidalMembershipFunction.php`
- **Hasil Audit:** Implementasi logika kurva trapesium sudah benar:
  ```php
  if ($this->c == $this->d && $x >= $this->c) {
      return 1.0;
  }
  ```
  Untuk himpunan `BAIK` $[65.0, 80.0, 100.0, 100.0]$, parameter $c=100.0$ dan $d=100.0$ terpenuhi, sehingga untuk semua $z \ge 80.0$ (bagian datar atas $b \le x \le c$) nilai keanggotaan bernilai $1.0$.

### 2.2. Kesalahan Endpoint Inklusif/Eksklusif
- **Hasil Audit:** Pada loop defuzzifikasi awal `MamdaniInferenceService.php`:
  `for ($z = $zMin; $z <= $zMax; $z += $step)`
  Loop berjalan dari $z=0$ hingga $z=100$ secara inklusif (tepat 101 titik integer untuk $\Delta z = 1.0$). Tidak ada endpoint yang terlewat.
  **Perbaikan:** Untuk mencegah drift akumulasi floating-point jika langkah pecahan (misal $0.1$ atau $0.5$), loop telah disempurnakan menjadi iterasi langkah integer:
  ```php
  $stepsCount = (int) round(($zMax - $zMin) / $step);
  for ($i = 0; $i <= $stepsCount; $i++) {
      $z = $zMin + ($i * $step);
      ...
  }
  ```

### 2.3. Perhitungan Numerator ($\sum z \cdot \mu(z)$)
- **Hasil Audit:** Engine PHP mengalokasikan $z \cdot \mu_Z$ secara tepat dan menjumlahkan ke variabel float `$numerator`. Pada kasus optimal, akumulasi menghasilkan $2412.6667$.

### 2.4. Perhitungan Denominator ($\sum \mu(z)$)
- **Hasil Audit:** Akumulasi derajat keanggotaan $\mu_Z$ menghasilkan tepat $28.0000$.

### 2.5. Implementasi Operator Minimum (AND & Implikasi Mamdani)
- **Hasil Audit:**
  - Evaluasi anteseden berbobot: `$rawAlpha = min($antecedentDegrees);`
  - Implikasi Mamdani: `$implication = min($alpha, $muSet);`
  - Keduanya diimplementasikan secara benar sesuai standar Mamdani.

### 2.6. Implementasi Operator Maksimum (OR & Agregasi Antar Rule)
- **Hasil Audit:**
  - Pengelompokan konsekuen: `$consequentMaxAlpha[$consequent] = max($alpha, ...);`
  - Agregasi kurva output:
    $$\mu_{\text{agg}}(z) = \max_{C} (\min(\alpha_C, \mu_C(z)))$$
  - Formulasi ini identik secara matematis dengan union dari seluruh implikasi aturan individual.

### 2.7. Pembobotan Firing Strength
- **Hasil Audit:** Firing strength dihitung dengan rumus:
  $$\alpha_k = w_k \times \alpha_{k,\text{raw}}$$
  Di mana aturan R01 memiliki bobot $w=1.0$ dan R16 memiliki bobot $w=0.9$. Pada kondisi optimal, $\alpha_{\text{R01}} = 1.0 \times 1.0 = 1.0$, dan $\alpha_{\text{R16}} = 0.9 \times 1.0 = 0.9$. Agregasi konsekuen `baik` mengambil $\max(1.0, 0.9) = 1.0$.

### 2.8. Kesalahan Agregasi Output
- **Hasil Audit:** Engine tidak melakukan penjumlahan linier antar aturan yang memiliki konsekuen sama (yang akan menyebabkan nilai $\mu > 1.0$). Agregasi menggunakan envelope maksimum, sesuai standar Mamdani.

### 2.9. Resolusi Sampling
- **Hasil Audit:** Sampling diskret menggunakan interval $\Delta z = 1.0$ pada rentang $[0.0, 100.0]$.

### 2.10. Interpretasi Domain Output
- **Hasil Audit:** Output didefinisikan secara tegas pada semesta pembicaraan $z \in [0.0, 100.0]$. Tidak ada ekstrapolasi di luar rentang $0$ hingga $100$.

### 2.11. Perbedaan Kode dengan Dokumentasi (Root Cause)
- **HASIL TEMUAN UTAMA (ROOT CAUSE):**
  Engine Laravel PHP aktual pada Phase 15 sebenarnya **SUDAH MENGHASILKAN NILAI YANG BENAR ($86.17$)**.
  Diskrepansi angka $79.65$ (dengan numerator $2270.0$ dan denominator $28.5$) **berasal dari kesalahan penulisan dokumentasi manual** pada `docs/FUZZY_MANUAL_CALCULATION.md` (dan dikutip pada `docs/PHASE_15_IMPLEMENTATION_REPORT.md`), di mana penyusun dokumentasi melakukan estimasi kasar tanpa menjalankan skrip verifikasi eksak.
  Hal serupa terjadi pada Contoh Kasus 2, di mana dokumentasi mencatat $20.35$, padahal hasil analitik diskret dan komputasi engine adalah $17.72$.

### 2.12. Pengujian yang Kurang Ketat
- **Hasil Audit:** Pada `tests/Feature/FuzzyLogicInferenceTest.php`, metode `test_09_centroid_defuzzification` hanya menguji:
  `$this->assertGreaterThan(65.0, $result['score']);`
  Assertion yang terlalu longgar ini meloloskan skor $86.17$ tanpa memvalidasi apakah angka tersebut cocok dengan dokumen spesifikasi manual ($79.65$) ataupun oracle independen.
  Selain itu, fungsi `infer()` belum mengembalikan `numerator`, `denominator`, dan `raw_score` ke caller, sehingga caller tidak dapat memverifikasi komponen perhitungan centroid secara transparan.

---

## 3. AUDIT PEMBULATAN PREMATUR (PREMATURE ROUNDING)

Audit menemukan adanya 3 titik pembulatan prematur sebelum Phase 15.1:
1. `FuzzyMembershipService::fuzzify`: Baris 97 menggunakan `round($mu, 4);`.
2. `FuzzyRuleEvaluationService::evaluateRules`: Baris 68 dan 78 menggunakan `round($rawAlpha * $weight, 4);` dan `round($rawAlpha, 4);`.
3. Akumulasi centroid: Meskipun numerator dan denominator menggunakan float unrounded, engine tidak mengekspos nilai unrounded `raw_score` sebelum dibulatkan dengan `round($score, 2)`.

**Koreksi Dilakukan:**
- Seluruh perhitungan internal fuzzifikasi dan evaluasi aturan diubah menggunakan native `(float)` berpresisi tinggi IEEE 754 tanpa pemotongan dini (`round`).
- `MamdaniInferenceService` kini mengembalikan `raw_score`, `numerator`, dan `denominator` secara utuh. Pembulatan 2 desimal hanya dilakukan pada representasi akhir `score` untuk display dan penyimpanan kolom `fuzzy_score` (DECIMAL 5,2).

---

## 4. AUDIT RULE BASE (16 ATURAN PROVISIONAL)

Audit terhadap seluruh 16 aturan pada `config/fuzzy.php`:
1. **Validitas Variabel:** Seluruh aturan hanya menggunakan variabel yang terdaftar (`temperature`, `humidity`, `soil_moisture`, `ph`).
2. **Validitas Himpunan:** Seluruh label himpunan fuzzy cocok dengan definisi pada `inputs` (`optimal`, `panas`, `dingin`, `ideal`, `kering`, `jenuh`, `asam`, `basa`).
3. **Operator:** Seluruh aturan menggunakan operator `AND` (`min`), tidak ditemukan sintaks operator tidak valid.
4. **Bobot Aturan:** R01–R15 berbobot $1.0$, R16 berbobot $0.9$. Seluruh bobot berada dalam rentang valid $(0, 1]$.
5. **Konsekuen:** Seluruh konsekuen valid (`baik`, `perlu_perhatian`, `buruk`).
6. **Duplikasi:** Tidak ada aturan yang memiliki kombinasi anteseden identik.
7. **Kontradiksi:**
   - Tidak ada aturan identik yang menghasilkan konsekuen berbeda.
   - Aturan subset (seperti R02 Panas+Kering $\rightarrow$ Buruk dan R14 Panas+Kering+Kering $\rightarrow$ Buruk) bersifat konsisten dan saling memperkuat (*monotonic reinforcement*).
   - Pada kondisi overlap (misal R04 dan R15, atau R07 dan R16), agregasi Mamdani melakukan unifikasi kurva luaran secara bertingkat, yang merupakan sifat dasar dari penalaran logika fuzzy bergradasi.
8. **Coverage Gap Handling:** Kombinasi matriks sensor yang tidak memicu ke-16 aturan ditangani secara aman dengan status `INSUFFICIENT_RULE_COVERAGE` tanpa menyebabkan nilai $0/0$ (NaN).
9. **Status Formal:** Tetap dipertahankan sebagai `'PROVISIONAL_FOR_SOFTWARE_TESTING'` sampai disahkan oleh Tim Teknik Elektro/Agronomi.

---

## 5. AUDIT FUNGSI KEANGGOTAAN & KONDISI BATAS

### Triangular Membership Function
- Ditambahkan validasi parameter degenerate: menolak $a \ge c$ dengan `InvalidArgumentException`.
- Dipastikan lereng naik linear $\frac{x-a}{b-a}$ dan lereng turun $\frac{c-x}{c-b}$ aman dari pembagian nol.
- Nilai di luar $[a, c]$ tepat $0.0$, nilai di puncak $b$ tepat $1.0$.

### Trapezoidal Membership Function
- Ditambahkan validasi parameter degenerate: menolak $a \ge d$ dengan `InvalidArgumentException`.
- Ditambahkan validasi kurva mustahil: menolak kurva yang sekaligus *left-shoulder* dan *right-shoulder* ($a=b$ dan $c=d$) yang akan menghasilkan keanggotaan $1.0$ di seluruh semesta $(-\infty, \infty)$.
- Mendukung *left-shoulder* murni ($a=b$) di mana $\mu(x) = 1.0$ untuk semua $x \le b$.
- Mendukung *right-shoulder* murni ($c=d$) di mana $\mu(x) = 1.0$ untuk semua $x \ge c$.
- Plateau atas $b \le x \le c$ menghasilkan $\mu(x) = 1.0$.

---

## 6. KESIMPULAN AUDIT MATEMATIS

1. **Akar Masalah Teridentifikasi:**
   Diskrepansi antara $79.65$ dan $86.17$ adalah **dokumentasi yang salah** pada `FUZZY_MANUAL_CALCULATION.md`, bukan kesalahan komputasi pada engine PHP.
2. **Koreksi Kode:**
   - Validasi parameter kurva diperketat terhadap kondisi batas dan parameter degenerate.
   - Pembulatan prematur dihilangkan dari fuzzifikasi dan evaluasi aturan.
   - Engine mengekspos `numerator`, `denominator`, dan `raw_score` untuk transparansi matematis penuh.
   - Loop defuzzifikasi menggunakan indeks langkah integer untuk menghindari akumulasi floating-point.
3. **Koreksi Dokumentasi:**
   `docs/FUZZY_MANUAL_CALCULATION.md` telah diperbaiki dengan penurunan matematis analitik eksak yang membuktikan nilai $86.166667 \approx 86.17$ dan $17.718310 \approx 17.72$.
4. **Validasi Independen:**
   Oracle Python murni dibangun dan 17 skenario (A s.d. P) terbukti cocok $100\%$ dengan implementasi Laravel PHP.
