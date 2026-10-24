# DOKUMENTASI FUNGSI KEANGGOTAAN FUZZY (PHASE 15)
## SISTEM WEB MONITORING TANAMAN MELON

**Versi Dokumen:** 1.0  
**Tanggal:** 16 Oktober 2026  
**Status:** `PROVISIONAL_FOR_SOFTWARE_TESTING` (Belum Divalidasi Tim Teknik Elektro)  
**Tujuan:** Mendokumentasikan spesifikasi matematis kurva keanggotaan untuk pengujian software.  

---

> [!WARNING]
> Seluruh kurva dan nilai ambang batas fungsi keanggotaan di bawah ini berstatus **PROVISIONAL UNTUK PENGUJIAN PERANGKAT LUNAK** dan belum disahkan secara formal oleh Tim Teknik Elektro maupun pakar agronomi melon. Nilai ini tidak boleh digunakan sebagai diagnosis agronomis resmi.

---

## 1. FORMULA MATEMATIS KURVA KEANGGOTAAN

### A. Fungsi Keanggotaan Segitiga (*Triangular Membership Function*)
Didefinisikan oleh parameter tuple $[a, b, c]$ di mana $a \le b \le c$:

$$\mu(x) = \begin{cases}
0, & x \le a \\
\frac{x - a}{b - a}, & a < x < b \\
1, & x = b \\
\frac{c - x}{c - b}, & b < x < c \\
0, & x \ge c
\end{cases}$$

### B. Fungsi Keanggotaan Trapesium (*Trapezoidal Membership Function*)
Didefinisikan oleh parameter tuple $[a, b, c, d]$ di mana $a \le b \le c \le d$:

$$\mu(x) = \begin{cases}
0, & x \le a \\
\frac{x - a}{b - a}, & a < x < b \\
1, & b \le x \le c \\
\frac{d - x}{d - c}, & c < x < d \\
0, & x \ge d
\end{cases}$$

**Kasus Bahu (*Shoulder Functions*):**
- *Left-shoulder:* Jika $a = b$, maka $\mu(x) = 1.0$ untuk semua $x \le b$.
- *Right-shoulder:* Jika $c = d$, maka $\mu(x) = 1.0$ untuk semua $x \ge c$.

---

## 2. VARIABEL INPUT & PARAMETER KURVA

### 1. Suhu Udara (`temperature`)
- **Semesta Pembicaraan (*Universe of Discourse*):** $10.0 - 50.0 \text{ } ^\circ\text{C}$
- **Satuan:** $^\circ\text{C}$

| Himpunan Linguistik | Tipe Kurva | Parameter $[a, b, c, (d)]$ | Deskripsi Agronomis Provisional |
| :--- | :--- | :--- | :--- |
| **Dingin** | Trapezoidal (Left-shoulder) | $[10.0, 10.0, 20.0, 24.0]$ | Suhu dingin di bawah kebutuhan vegetatif |
| **Optimal** | Triangular | $[22.0, 27.0, 32.0]$ | Rentang suhu ideal pertumbuhan melon |
| **Panas** | Trapezoidal (Right-shoulder)| $[29.0, 34.0, 50.0, 50.0]$ | Suhu panas berisiko transpirasi ekstrem |

### 2. Kelembapan Udara (`humidity`)
- **Semesta Pembicaraan:** $0.0 - 100.0 \text{ } \%$
- **Satuan:** $\%$

| Himpunan Linguistik | Tipe Kurva | Parameter $[a, b, c, (d)]$ | Deskripsi Agronomis Provisional |
| :--- | :--- | :--- | :--- |
| **Kering** | Trapezoidal (Left-shoulder) | $[0.0, 0.0, 50.0, 65.0]$ | Kelembapan rendah, memicu penguapan cepat |
| **Optimal** | Triangular | $[58.0, 70.0, 82.0]$ | Kelembapan ideal untuk fotosintesis melon |
| **Lembap** | Trapezoidal (Right-shoulder)| $[75.0, 85.0, 100.0, 100.0]$| Kelembapan tinggi berisiko serangan jamur |

### 3. Kelembapan Tanah (`soil_moisture`)
- **Semesta Pembicaraan:** $0.0 - 100.0 \text{ } \%$
- **Satuan:** $\%$

| Himpunan Linguistik | Tipe Kurva | Parameter $[a, b, c, (d)]$ | Deskripsi Agronomis Provisional |
| :--- | :--- | :--- | :--- |
| **Kering** | Trapezoidal (Left-shoulder) | $[0.0, 0.0, 50.0, 65.0]$ | Media kekurangan air (*under-watered*) |
| **Ideal** | Triangular | $[58.0, 70.0, 82.0]$ | Ketersediaan air media mencukupi |
| **Jenuh** | Trapezoidal (Right-shoulder)| $[75.0, 85.0, 100.0, 100.0]$| Media tergenang air (*over-saturated*) |

### 4. pH Nutrisi/Media (`ph`)
- **Semesta Pembicaraan:** $0.0 - 14.0$
- **Satuan:** $\text{pH}$

| Himpunan Linguistik | Tipe Kurva | Parameter $[a, b, c, (d)]$ | Deskripsi Agronomis Provisional |
| :--- | :--- | :--- | :--- |
| **Asam** | Trapezoidal (Left-shoulder) | $[0.0, 0.0, 5.5, 6.0]$ | pH rendah, mengunci fosfat & kalsium |
| **Optimal** | Triangular | $[5.8, 6.5, 7.2]$ | Rentang pH seimbang nutrisi melon |
| **Basa** | Trapezoidal (Right-shoulder)| $[6.8, 7.5, 14.0, 14.0]$ | pH tinggi, menghambat mikro nutrien |

---

## 3. VARIABEL OUTPUT: SKOR KONDISI GREENHOUSE

- **Semesta Pembicaraan:** $0.0 - 100.0 \text{ Index}$
- **Metode Defuzzifikasi:** Centroid (*Center of Gravity*) dengan resolusi diskret $\Delta z = 1.0$

| Himpunan Linguistik | Tipe Kurva | Parameter $[a, b, c, (d)]$ |
| :--- | :--- | :--- |
| **Buruk** | Trapezoidal (Left-shoulder) | $[0.0, 0.0, 25.0, 45.0]$ |
| **Perlu Perhatian** | Triangular | $[35.0, 55.0, 75.0]$ |
| **Baik** | Trapezoidal (Right-shoulder)| $[65.0, 80.0, 100.0, 100.0]$ |

### Kategori Hasil Defuzzifikasi:
- **`BAIK`**: Skor $65.0 \le z^* \le 100.0$
- **`PERLU_PERHATIAN`**: Skor $40.0 \le z^* < 65.0$
- **`BURUK`**: Skor $0.0 \le z^* < 40.0$
