# DRAF SPESIFIKASI KEBUTUHAN SISTEM INFERENSI FUZZY LOGIC (PHASE 13)
## SISTEM WEB MONITORING TANAMAN MELON

**Versi Dokumen:** 1.0 (Draft For Review)  
**Tanggal:** 16 Oktober 2026  
**Status:** Draf Perencanaan Konseptual (Belum Diimplementasikan di Kode Produksi)  
**Tujuan:** Menyelaraskan pemahaman teknis AI / Fuzzy Logic antara Tim Elektro dan Tim Pengembang Web  

---

## 1. PENDAHULUAN & KLARIFIKASI RUANG LINGKUP

Berdasarkan klarifikasi dari Tim Teknik Elektro, istilah **"AI"** yang dimaksudkan dalam proyek ini adalah **Sistem Inferensi Logika Fuzzy (Fuzzy Logic Inference System)** berbasis aturan agronomis (*rule-based expert system*), **BUKAN** Large Language Model (LLM), Generative AI, Neural Networks, atau Deep Learning berbasis cloud.

### Batasan Tegas Phase 13:
> [!IMPORTANT]
> - Dokumen ini merupakan **draf kebutuhan dan rancangan konseptual**.
> - Pada Phase 13, logika fuzzy **TIDAK DIIMPLEMENTASIKAN DALAM BENTUK KODE PRODUKSI**.
> - Parameter fungsi keanggotaan (*membership functions*) dan matriks aturan inferensi (*rule base*) **tidak boleh difinalisasi secara sepihak** tanpa persetujuan tertulis dari Tim Elektro dan pakar agronomi melon.

---

## 2. PERBEDAAN FUNDAMENTAL: ALARM STATUS VS INFERENSI FUZZY

Sangat penting untuk memisahkan secara arsitektural antara sistem **Alarm** dan sistem **Inferensi Fuzzy**:

| Dimensi Perbandingan | Sistem Alarm (Phase 12–13) | Sistem Inferensi Fuzzy (Phase Integrasi Mendatang) |
| :--- | :--- | :--- |
| **Sifat Logika** | Biner / Ambang batas tegas (*Crisp Thresholds*) | Logika Samar Derajat Keanggotaan (*Fuzzy Multi-valued*) |
| **Fungsi Utama** | Deteksi pelanggaran batas keselamatan instan (*Safety & Alerting*) | Penilaian kondisi komprehensif & pendukung keputusan (*Decision Support*) |
| **Input** | Dievaluasi per parameter independen | Mengevaluasi kombinasi multi-parameter secara simultan |
| **Output** | `Normal`, `Warning`, `Critical` | Skor Indeks Kenyamanan (0–100), Kategori Kondisi, Kesimpulan Agronomis |
| **Ketergantungan** | Tidak tergantung pada model AI | Memerlukan mesin inferensi (Mamdani/Sugeno) |

*Contoh Kasus:* Suhu udara berada pada batas atas normal (31.5°C) dan kelembapan berada pada batas bawah normal (61%). Bagi sistem Alarm, kondisi ini adalah **NORMAL** (tidak ada batas yang dilanggar). Namun bagi sistem Fuzzy, kombinasi ini dapat menghasilkan kesimpulan **"POTENSI STRES PANAS & TRANSPIRASI TINGGI"**, merekomendasikan aktivasi kipas sirkulasi lebih awal.

---

## 3. RANCANGAN KONSEPTUAL VARIABEL INPUT

Kandidat variabel input inferensi fuzzy direncanakan mengambil data dari 4 parameter operasional utama:

### 1. Suhu Udara (`temperature`)
- Satuan: °C
- Domain Semesta Pembicaraan (*Universe of Discourse*): 15.0 – 45.0 °C
- Himpunan Fuzzy Konseptual:
  - `Dingin` (*Cold*)
  - `Optimal` (*Normal*)
  - `Panas` (*Hot*)

### 2. Kelembapan Udara (`humidity`)
- Satuan: %
- Domain Semesta Pembicaraan: 30.0 – 100.0 %
- Himpunan Fuzzy Konseptual:
  - `Kering` (*Dry*)
  - `Optimal` (*Ideal*)
  - `Lembap` (*Humid*)

### 3. Kelembapan Tanah (`soil_moisture`)
- Satuan: %
- Domain Semesta Pembicaraan: 20.0 – 100.0 %
- Himpunan Fuzzy Konseptual:
  - `Kering` (*Under-watered*)
  - `Ideal` (*Optimum Moisture*)
  - `Jenuh Air` (*Over-saturated*)

### 4. pH Nutrisi / Media (`ph`)
- Satuan: pH
- Domain Semesta Pembicaraan: 4.0 – 9.0
- Himpunan Fuzzy Konseptual:
  - `Asam` (*Acidic*)
  - `Optimal` (*Neutral-Balanced*)
  - `Basa` (*Alkaline*)

---

## 4. METODE INFERENSI & RANCANGAN OUTPUT

### A. Metode Inferensi yang Diusulkan
- **Metode Mamdani:** Direkomendasikan karena sifatnya yang intuitif, mudah dipahami oleh pakar agronomi, dan menghasilkan output dalam bentuk himpunan linguistik sebelum didefuzzifikasi.
- **Alternatif (Sugeno Orde-0/1):** Dapat dipertimbangkan jika inferensi langsung digunakan untuk durasi durasi menit penyalaan pompa.

### B. Struktur Konseptual Output
Output sistem inferensi fuzzy dirancang memiliki format data terstruktur:

```json
{
  "inference_timestamp": "2026-10-16 14:00:00",
  "method": "Mamdani",
  "input_vector": {
    "temperature": 32.4,
    "humidity": 58.0,
    "soil_moisture": 62.0,
    "ph": 6.4
  },
  "comfort_score": 78.5,
  "condition_category": "WASPADA_SUHU_TINGGI",
  "conclusion": "Lingkungan greenhouse cenderung panas dengan transpirasi moderat. Direkomendasikan menyalakan kipas sirkulasi selama 15 menit.",
  "recommended_action": {
    "actuator": "exhaust_fan",
    "suggested_state": "ON",
    "suggested_duration_minutes": 15
  },
  "source_of_inference": "edge_esp32_or_laravel_engine"
}
```

---

## 5. CHECKLIST KOORDINASI DENGAN TIM ELEKTRO (ACTION ITEMS)

Sebelum implementasi kode inferensi dapat dimulai pada phase berikutnya, Tim Elektro dan Tim Agronomi dimohon menetapkan:

- [ ] Kurva dan titik koordinat Fungsi Keanggotaan (*Membership Functions*) trapesium/segitiga untuk masing-masing parameter.
- [ ] Matriks Tabel Aturan (*Rule Base*) lengkap (contoh: *IF Temperature IS Hot AND Humidity IS Dry THEN Condition IS Extreme Stress*).
- [ ] Lokasi eksekusi inferensi: Apakah inferensi dijalankan di **firmware ESP32** lalu hasilnya dikirim ke Google Sheets, atau dihitung di **backend Laravel**?
- [ ] Validasi akademis terhadap akurasi rumus defuzzifikasi (Centroid / Weighted Average).
