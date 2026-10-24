# VERIFIKASI MATEMATIS INDEPENDEN LOGIKA FUZZY (PHASE 15.1)
## SISTEM WEB MONITORING TANAMAN MELON
**File Referensi:** `scratch/fuzzy_oracle.py` & `scratch/verify_all_scenarios.php`  
**Tanggal:** 17 Oktober 2026  
**Status:** 100% VERIFIED INDEPENDENTLY (17 / 17 SCENARIOS MATCH)  

---

## 1. ARSITEKTUR ORACLE INDEPENDEN

Untuk memastikan validasi matematis yang objektif dan bebas dari *circular dependency* (tidak menggunakan kode yang sedang diuji sebagai alat pengujian itu sendiri), telah dibangun **Oracle Matematika Independen** berbasis Python standard library murni (`scratch/fuzzy_oracle.py`).

### Karakteristik Desain Oracle:
1. **Zero External Dependencies:** Hanya menggunakan modul bawaan Python (`math`, `json`, `typing`). Tidak mengimpor pustaka pihak ketiga seperti `numpy` atau `scikit-fuzzy`.
2. **Decoupled:** Tidak memanggil atau bergantung pada class PHP Laravel (`MamdaniInferenceService`, `FuzzyMembershipService`, dsb.).
3. **Presisi Tinggi:** Menggunakan float 64-bit native IEEE 754 tanpa pemotongan dini (*unrounded internal pipeline*).
4. **Reproducible:** Menghasilkan snapshot hasil inferensi dalam format JSON terstruktur (`scratch/oracle_scenarios.json`) yang diverifikasi langsung oleh test suite PHPUnit.

---

## 2. FORMULASI MATEMATIS ORACLE

### 2.1. Fungsi Keanggotaan Segitiga
$$\mu_{\text{tri}}(x; a, b, c) = \begin{cases} 
0, & x \le a \text{ atau } x \ge c \\
\frac{x - a}{b - a}, & a < x < b \\
1, & x = b \\
\frac{c - x}{c - b}, & b < x < c 
\end{cases}$$

### 2.2. Fungsi Keanggotaan Trapesium
$$\mu_{\text{trap}}(x; a, b, c, d) = \begin{cases}
1, & \text{jika } a = b \text{ dan } x \le b \quad (\text{Left-Shoulder}) \\
1, & \text{jika } c = d \text{ dan } x \ge c \quad (\text{Right-Shoulder}) \\
0, & x \le a \text{ atau } x \ge d \\
\frac{x - a}{b - a}, & a < x < b \\
1, & b \le x \le c \\
\frac{d - x}{d - c}, & c < x < d 
\end{cases}$$

### 2.3. Evaluasi Anteseden & Firing Strength
- Operator konjungsi `AND` (Mamdani T-norm min):
  $$\alpha_{k,\text{raw}} = \min_{j} \mu_{A_{k,j}}(x_j)$$
- Pembobotan aturan:
  $$\alpha_k = w_k \times \alpha_{k,\text{raw}}, \quad w_k \in (0.0, 1.0]$$

### 2.4. Implikasi & Agregasi Mamdani
- Implikasi per konsekuen himpunan luaran $C \in \{\text{buruk}, \text{perlu\_perhatian}, \text{baik}\}$:
  $$\beta_C = \max_{k \in \text{Rules with consequent } C} \alpha_k$$
  $$\mu_C'(z) = \min(\beta_C, \mu_C(z))$$
- Agregasi kurva output (S-norm max):
  $$\mu_{\text{agg}}(z) = \max_{C} \mu_C'(z)$$

### 2.5. Defuzzifikasi Centroid Diskret (Center of Gravity)
Untuk domain $z \in [z_{\min}, z_{\max}]$ dengan langkah diskret $\Delta z$:
$$z^* = \frac{\sum_{i=0}^{N} z_i \cdot \mu_{\text{agg}}(z_i)}{\sum_{i=0}^{N} \mu_{\text{agg}}(z_i)}, \quad z_i = z_{\min} + i \cdot \Delta z, \quad N = \frac{z_{\max} - z_{\min}}{\Delta z}$$

Jika $\sum_{i=0}^{N} \mu_{\text{agg}}(z_i) = 0$, sistem menetapkan status `INSUFFICIENT_RULE_COVERAGE` dengan nilai skor `NULL`.

---

## 3. TABEL PERBANDINGAN: ORACLE PYTHON VS ENGINE LARAVEL PHP

Tabel berikut menyajikan hasil komparasi antara Oracle Python dan Mesin Inferensi PHP untuk ke-17 skenario (A hingga P) dengan toleransi selisih $\epsilon < 10^{-4}$ untuk skor dan $\epsilon < 10^{-3}$ untuk numerator/denominator:

| Skenario | Deskripsi Kondisi Input | Aturan Aktif | Oracle Skor | PHP Skor | Oracle Numerator | PHP Numerator | Oracle Denominator | PHP Denominator | Kategori | Selisih Skor | Status |
| :--- | :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **A** | Semua parameter optimal ($T=27, H=70, S=70, pH=6.5$) | R01, R16 | 86.17 | 86.17 | 2412.67 | 2412.67 | 28.00 | 28.00 | BAIK | $0.000000$ | **PASS** |
| **B** | Suhu tinggi & tanah kering ($T=36, H=50, S=30, pH=6.5$) | R02, R14 | 17.72 | 17.72 | 629.00 | 629.00 | 35.50 | 35.50 | BURUK | $0.000000$ | **PASS** |
| **C** | Udara lembap & tanah jenuh ($T=18, H=90, S=90, pH=6.5$) | R04, R15 | 30.95 | 30.95 | 1679.00 | 1679.00 | 54.25 | 54.25 | BURUK | $0.000000$ | **PASS** |
| **D** | pH terlalu asam ($T=27, H=70, S=70, pH=4.5$) | R12, R16 | 72.49 | 72.49 | 3222.20 | 3222.20 | 44.45 | 44.45 | BAIK | $0.000000$ | **PASS** |
| **E** | pH terlalu basa ($T=27, H=70, S=70, pH=8.5$) | R13, R16 | 72.49 | 72.49 | 3222.20 | 3222.20 | 44.45 | 44.45 | BAIK | $0.000000$ | **PASS** |
| **F** | Kombinasi overlap aturan beda ($T=30, H=62, S=62, pH=6.5$) | R01, R02, R07, R09, R10, R14, R16 | 55.98 | 55.98 | 1365.93 | 1365.93 | 24.40 | 24.40 | PERLU_PERHATIAN | $0.000000$ | **PASS** |
| **G** | Input tepat di titik batas kurva ($T=22, H=58, S=58, pH=5.8$) | R05 | 20.31 | 20.31 | 337.20 | 337.20 | 16.60 | 16.60 | BURUK | $0.000000$ | **PASS** |
| **H** | Input di area overlap dua himpunan ($T=23, H=60, S=60, pH=6.0$) | R01, R07, R09, R11, R16 | 67.32 | 67.32 | 789.87 | 789.87 | 11.73 | 11.73 | BAIK | $0.000000$ | **PASS** |
| **I** | Tidak ada aturan aktif ($T=15, H=40, S=90, pH=6.5$) | *(None)* | NULL | NULL | 0.00 | 0.00 | 0.00 | 0.00 | NULL | $0.000000$ | **PASS** |
| **J** | Sensor null / missing ($T=\text{null}, H=\text{null}, S=70, pH=6.5$) | *(None)* | NULL | NULL | 0.00 | 0.00 | 0.00 | 0.00 | NULL | $0.000000$ | **PASS** |
| **K** | Nilai input di luar rentang normal ($T=55, H=-5, S=110, pH=15$) | R06 | 17.72 | 17.72 | 629.00 | 629.00 | 35.50 | 35.50 | BURUK | $0.000000$ | **PASS** |
| **L** | Hanya 1 aturan aktif ($T=35, H=70, S=70, pH=6.5$) | R10 | 55.00 | 55.00 | 1100.00 | 1100.00 | 20.00 | 20.00 | PERLU_PERHATIAN | $0.000000$ | **PASS** |
| **M** | Multi aturan konsekuen sama ($T=27, H=70, S=70, pH=6.5$) | R01, R16 (Baik) | 86.17 | 86.17 | 2412.67 | 2412.67 | 28.00 | 28.00 | BAIK | $0.000000$ | **PASS** |
| **N** | Multi aturan konsekuen beda ($T=18, H=90, S=90, pH=6.5$) | R04, R15 (Mod+Buruk) | 30.95 | 30.95 | 1679.00 | 1679.00 | 54.25 | 54.25 | BURUK | $0.000000$ | **PASS** |
| **O** | Left-shoulder vs right-shoulder murni ($T=35, H=45, S=40, pH=6.5$) | R02, R14 (Buruk) | 17.72 | 17.72 | 629.00 | 629.00 | 35.50 | 35.50 | BURUK | $0.000000$ | **PASS** |
| **P1** | Resolusi sampling $\Delta z = 0.5$ (Optimal) | R01, R16 | 86.04 | 86.04 | 4775.08 | 4775.08 | 55.50 | 55.50 | BAIK | $0.000000$ | **PASS** |
| **P2** | Resolusi sampling $\Delta z = 0.1$ (Optimal) | R01, R16 | 85.93 | 85.93 | 23675.02 | 23675.02 | 275.50 | 275.50 | BAIK | $0.000000$ | **PASS** |

---

## 4. ANALISIS SENSITIVITAS RESOLUSI SAMPLING (SKENARIO P)

Pada integrasi diskret centroid Mamdani:
- Dengan $\Delta z = 1.0$: terdapat 101 titik evaluasi ($0, 1, 2, \dots, 100$). Skor optimal $= 86.17$.
- Dengan $\Delta z = 0.5$: terdapat 201 titik evaluasi ($0.0, 0.5, 1.0, \dots, 100.0$). Skor optimal $= 86.04$.
- Dengan $\Delta z = 0.1$: terdapat 1001 titik evaluasi ($0.0, 0.1, 0.2, \dots, 100.0$). Skor optimal $= 85.93$.

Secara analitik kontinu:
$$\int_{65}^{80} \frac{z - 65}{15} \, dz = \left[ \frac{(z - 65)^2}{30} \right]_{65}^{80} = \frac{15^2}{30} = 7.5$$
$$\int_{80}^{100} 1.0 \, dz = 20.0 \implies \text{Penyebut Kontinu} = 7.5 + 20.0 = 27.5$$
$$\int_{65}^{80} z \frac{z - 65}{15} \, dz = \frac{1}{15} \left[ \frac{z^3}{3} - \frac{65 z^2}{2} \right]_{65}^{80} = 562.5$$
$$\int_{80}^{100} z \, dz = \left[ \frac{z^2}{2} \right]_{80}^{100} = \frac{10000 - 6400}{2} = 1800.0 \implies \text{Pembilang Kontinu} = 562.5 + 1800.0 = 2362.5$$
$$\text{Centroid Kontinu } z^*_{\text{kontinu}} = \frac{2362.5}{27.5} = 85.909091 \approx 85.91$$

Ketika $\Delta z \to 0$ (dari $1.0 \to 0.5 \to 0.1$), skor diskret konvergen secara mulus menuju nilai analitik kontinu $85.91$:
- $\Delta z = 1.0 \implies 86.17$
- $\Delta z = 0.5 \implies 86.04$
- $\Delta z = 0.1 \implies 85.93$
- $\Delta z \to 0 \implies 85.91$

Hal ini membuktikan secara matematis bahwa implementasi diskret sistem dengan $\Delta z = 1.0$ adalah representasi Riemann sum yang valid, deterministik, dan konsisten.

---

## 5. KESIMPULAN VERIFIKASI

1. Seluruh 17 skenario verifikasi menghasilkan nilai eksak identik (selisih $0.000000$) antara engine PHP Laravel dan Oracle Python independen.
2. Status klasifikasi kategori (`BAIK`, `PERLU_PERHATIAN`, `BURUK`, dan `INSUFFICIENT_RULE_COVERAGE`) konsisten $100\%$.
3. Tidak ditemukan anomali pembagian nol (*division by zero*), `NaN`, *infinity*, atau *underflow/overflow*.
