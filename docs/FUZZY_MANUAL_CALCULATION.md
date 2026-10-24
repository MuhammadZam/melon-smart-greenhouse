# PERHITUNGAN MANUAL SISTEM INFERENSI FUZZY MAMDANI (PHASE 15)
## SISTEM WEB MONITORING TANAMAN MELON

**Versi Dokumen:** 1.0  
**Tanggal:** 16 Oktober 2026  
**Status:** Verifikasi Matematis Deterministik  

---

## CONTOH KASUS 1: KONDISI OPTIMAL SEMPURNA

### 1. Vektor Input Crisp:
- Suhu Udara ($T$): $27.0\text{ }^\circ\text{C}$
- Kelembapan Udara ($H$): $70.0\text{ }\%$
- Kelembapan Tanah ($S$): $70.0\text{ }\%$
- pH Media ($pH$): $6.50$

---

### 2. Tahap 1: Fuzzifikasi (Derajat Keanggotaan)

#### A. Suhu Udara ($T = 27.0\text{ }^\circ\text{C}$)
- $\mu_{\text{Dingin}}(27.0) = 0.0$ (karena $27.0 \ge 24.0$)
- $\mu_{\text{Optimal}}(27.0)$:
  Kurva segitiga $[22.0, 27.0, 32.0]$. Karena $x = 27.0$ tepat pada titik puncak $b$:
  $$\mu_{\text{Optimal}}(27.0) = 1.0$$
- $\mu_{\text{Panas}}(27.0) = 0.0$ (karena $27.0 \le 29.0$)

#### B. Kelembapan Udara ($H = 70.0\text{ }\%$)
- $\mu_{\text{Kering}}(70.0) = 0.0$ (karena $70.0 \ge 65.0$)
- $\mu_{\text{Optimal}}(70.0)$:
  Kurva segitiga $[58.0, 70.0, 82.0]$. Karena $x = 70.0$ tepat pada titik puncak $b$:
  $$\mu_{\text{Optimal}}(70.0) = 1.0$$
- $\mu_{\text{Lembap}}(70.0) = 0.0$ (karena $70.0 \le 75.0$)

#### C. Kelembapan Tanah ($S = 70.0\text{ }\%$)
- $\mu_{\text{Kering}}(70.0) = 0.0$
- $\mu_{\text{Ideal}}(70.0)$:
  Kurva segitiga $[58.0, 70.0, 82.0]$. Tepat pada titik puncak:
  $$\mu_{\text{Ideal}}(70.0) = 1.0$$
- $\mu_{\text{Jenuh}}(70.0) = 0.0$

#### D. pH Nutrisi/Media ($pH = 6.50$)
- $\mu_{\text{Asam}}(6.5) = 0.0$
- $\mu_{\text{Optimal}}(6.5)$:
  Kurva segitiga $[5.8, 6.5, 7.2]$. Tepat pada titik puncak:
  $$\mu_{\text{Optimal}}(6.5) = 1.0$$
- $\mu_{\text{Basa}}(6.5) = 0.0$

---

### 3. Tahap 2: Evaluasi Aturan & Firing Strength ($\alpha$)

- **Aturan R01:**
  IF $T$ is Optimal AND $H$ is Optimal AND $S$ is Ideal AND $pH$ is Optimal THEN Condition is **Baik** (Weight 1.0)
  $$\alpha_{\text{R01}} = 1.0 \times \min(1.0, 1.0, 1.0, 1.0) = 1.0$$
- **Aturan R16:**
  IF $T$ is Optimal AND $H$ is Optimal THEN Condition is **Baik** (Weight 0.9)
  $$\alpha_{\text{R16}} = 0.9 \times \min(1.0, 1.0) = 0.9$$
- Seluruh aturan lainnya menghasilkan $\alpha_k = 0.0$ karena anteseden lainnya bernilai $0.0$.

Firing strength maksimum untuk masing-masing konsekuen:
- $\beta_{\text{Baik}} = \max(1.0, 0.9) = 1.0$
- $\beta_{\text{Perlu\_Perhatian}} = 0.0$
- $\beta_{\text{Buruk}} = 0.0$

---

### 4. Tahap 3: Implikasi & Agregasi

Untuk setiap nilai $z \in [0.0, 100.0]$:
$$\mu_{\text{agg}}(z) = \min(1.0, \mu_{\text{Baik}}(z)) = \mu_{\text{Baik}}(z)$$

Kurva $\mu_{\text{Baik}}(z)$ adalah trapesium $[65.0, 80.0, 100.0, 100.0]$:
- Untuk $z \le 65$: $\mu(z) = 0.0$
- Untuk $65 < z < 80$: $\mu(z) = \frac{z - 65}{80 - 65} = \frac{z - 65}{15}$
- Untuk $80 \le z \le 100$: $\mu(z) = 1.0$

---

### 5. Tahap 4: Defuzzifikasi Centroid (Center of Gravity)

Rumus matematis kontinu:
$$z^* = \frac{\int_{0}^{100} z \cdot \mu_{\text{agg}}(z) \, dz}{\int_{0}^{100} \mu_{\text{agg}}(z) \, dz}$$

Dalam perhitungan diskret sistem dengan domain $z \in [0, 100]$ dan resolusi sampling langkah $\Delta z = 1.0$:
- **Domain $z = 0 \dots 65$:** $\mu(z) = 0.0$, sehingga kontribusi pembilang dan penyebut $= 0.0$.
- **Domain lereng naik $z = 66 \dots 79$ (14 titik integer):**
  Definisikan indeks $k = z - 65 \in [1, 14]$:
  $$\mu(z) = \frac{k}{15}$$
  $$\sum_{z=66}^{79} \mu(z) = \frac{1}{15} \sum_{k=1}^{14} k = \frac{1}{15} \times \frac{14 \times 15}{2} = 7.0$$
  $$\sum_{z=66}^{79} z \cdot \mu(z) = \frac{1}{15} \sum_{k=1}^{14} (k + 65) k = \frac{1}{15} \left( \sum_{k=1}^{14} k^2 + 65 \sum_{k=1}^{14} k \right)$$
  $$\sum_{k=1}^{14} k^2 = \frac{14 \times 15 \times 29}{6} = 1015, \quad 65 \sum_{k=1}^{14} k = 65 \times 105 = 6825$$
  $$\sum_{z=66}^{79} z \cdot \mu(z) = \frac{1015 + 6825}{15} = \frac{7840}{15} \approx 522.666667$$
- **Domain plateau kanan $z = 80 \dots 100$ (21 titik integer):**
  $$\mu(z) = 1.0$$
  $$\sum_{z=80}^{100} \mu(z) = 21 \times 1.0 = 21.0$$
  $$\sum_{z=80}^{100} z \cdot \mu(z) = \sum_{z=80}^{100} z = \frac{80 + 100}{2} \times 21 = 90 \times 21 = 1890.0$$

Hasil akumulasi diskret sistem:
- **Denominator ($\sum \mu(z)$):** $7.0 + 21.0 = 28.0$
- **Numerator ($\sum z \cdot \mu(z)$):** $522.666667 + 1890.0 = 2412.666667$
- **Skor Centroid Persis ($z^*$):**
  $$z^* = \frac{2412.666667}{28.0} = 86.166667 \approx 86.17$$

Kategori output: **`BAIK`** (karena $z^* \ge 65.0$).

---

## CONTOH KASUS 2: STRES PANAS & KEKERINGAN TANAH

### 1. Vektor Input Crisp:
- Suhu Udara ($T$): $35.0\text{ }^\circ\text{C}$ (Panas)
- Kelembapan Udara ($H$): $45.0\text{ }\%$ (Kering)
- Kelembapan Tanah ($S$): $40.0\text{ }\%$ (Kering)
- pH Media ($pH$): $6.50$ (Optimal)

### 2. Fuzzifikasi:
- Suhu: $\mu_{\text{Panas}}(35.0) = 1.0$ (karena $35.0 \ge 34.0$)
- Kelembapan Udara: $\mu_{\text{Kering}}(45.0) = 1.0$ (karena $45.0 \le 50.0$)
- Kelembapan Tanah: $\mu_{\text{Kering}}(40.0) = 1.0$ (karena $40.0 \le 50.0$)
- pH: $\mu_{\text{Optimal}}(6.5) = 1.0$

### 3. Evaluasi Aturan:
- **R02:** IF $T$ Panas AND $S$ Kering THEN **Buruk** $\rightarrow \alpha = \min(1.0, 1.0) = 1.0$
- **R14:** IF $T$ Panas AND $H$ Kering AND $S$ Kering THEN **Buruk** $\rightarrow \alpha = \min(1.0, 1.0, 1.0) = 1.0$

Firing strength maksimum konsekuen:
- $\beta_{\text{Buruk}} = \max(1.0, 1.0) = 1.0$
- $\beta_{\text{Perlu\_Perhatian}} = 0.0$
- $\beta_{\text{Baik}} = 0.0$

### 4. Defuzzifikasi Centroid:
Kurva $\mu_{\text{Buruk}}(z)$ adalah trapesium left-shoulder $[0.0, 0.0, 25.0, 45.0]$:
- **Domain plateau kiri $z = 0 \dots 25$ (26 titik integer):**
  $$\mu(z) = 1.0$$
  $$\sum_{z=0}^{25} \mu(z) = 26 \times 1.0 = 26.0$$
  $$\sum_{z=0}^{25} z \cdot \mu(z) = \frac{0 + 25}{2} \times 26 = 325.0$$
- **Domain lereng turun $z = 26 \dots 44$ (19 titik integer):**
  Definisikan indeks $j = 45 - z \in [19, 18, \dots, 1]$:
  $$\mu(z) = \frac{45 - z}{45 - 25} = \frac{j}{20}$$
  $$\sum_{z=26}^{44} \mu(z) = \frac{1}{20} \sum_{j=1}^{19} j = \frac{1}{20} \times \frac{19 \times 20}{2} = 9.5$$
  $$\sum_{z=26}^{44} z \cdot \mu(z) = \frac{1}{20} \sum_{j=1}^{19} (45 - j) j = \frac{1}{20} \left( 45 \sum_{j=1}^{19} j - \sum_{j=1}^{19} j^2 \right)$$
  $$\sum_{j=1}^{19} j = 190, \quad \sum_{j=1}^{19} j^2 = \frac{19 \times 20 \times 39}{6} = 2470$$
  $$\sum_{z=26}^{44} z \cdot \mu(z) = \frac{45(190) - 2470}{20} = \frac{8550 - 2470}{20} = \frac{6080}{20} = 304.0$$
- **Domain $z = 45 \dots 100$:** $\mu(z) = 0.0$, kontribusi $= 0.0$.

Hasil akumulasi diskret sistem:
- **Denominator ($\sum \mu(z)$):** $26.0 + 9.5 = 35.5$
- **Numerator ($\sum z \cdot \mu(z)$):** $325.0 + 304.0 = 629.0$
- **Skor Centroid Persis ($z^*$):**
  $$z^* = \frac{629.0}{35.5} \approx 17.718310 \approx 17.72$$

Kategori output: **`BURUK`** (karena $z^* < 40.0$).

Hasil verifikasi menunjukkan kesesuaian 100% antara formulasi analitik manual, implementasi engine PHP Laravel, dan Oracle Matematika Independen Python.
