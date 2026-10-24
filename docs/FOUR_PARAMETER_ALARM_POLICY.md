# KEBIJAKAN SISTEM ALARM EMPAT PARAMETER & TRANSISI (PHASE 13)
## SISTEM WEB MONITORING TANAMAN MELON

**Versi Dokumen:** 1.0  
**Tanggal Berlaku:** 16 Oktober 2026  
**Otoritas:** Tim Teknik Elektro & Tim Pengembang Perangkat Lunak  
**Status:** Diterapkan dan Terverifikasi  

---

## 1. LATAR BELAKANG KEBIJAKAN

Berdasarkan tinjauan operasional sensor di greenhouse budidaya melon oleh Tim Teknik Elektro, sistem otomasi dan peringatan dini difokuskan secara prioritas pada empat variabel mikroklimat dan rizosfer yang memiliki dampak paling langsung terhadap kelangsungan hidup tanaman melon.

Oleh karena itu, pada Phase 13 ditetapkan kebijakan pembatasan parameter alarm menjadi **4 Parameter Operasional Utama**.

---

## 2. KATEGORISASI PARAMETER

### A. Parameter Alarm Aktif (Alarm-Eligible Parameters)
Hanya empat parameter berikut yang dievaluasi oleh `MonitoringAlarmService` untuk memicu alarm aktif baru (`warning` atau `critical`):

1. **`temperature` (Suhu Udara):**
   - Satuan: °C
   - Dampak Kritis: Suhu ekstrem (>35°C) memicu rontok bunga melon dan dehidrasi jaringan.
2. **`humidity` (Kelembapan Udara):**
   - Satuan: %
   - Dampak Kritis: Kelembapan berlebih (>85%) memicu infeksi jamur tepung (*powdery mildew*); kelembapan rendah (<50%) meningkatkan stres transpirasi.
3. **`soil_moisture` (Kelembapan Tanah / Media Tanam):**
   - Satuan: %
   - Dampak Kritis: Tanah kering (<50%) menghambat penyerapan hara fertigasi; tanah terlalu basah (>85%) memicu pembusukan akar (*root rot*).
4. **`ph` (pH Nutrisi / Media Tanam):**
   - Satuan: pH
   - Dampak Kritis: Deviasi pH di luar 5.8 – 7.2 menyebabkan penguncian unsur hara mikro dan makro.

### B. Parameter Monitoring Visual (Non-Alarm Parameters)
Parameter berikut **TIDAK MEMICU ALARM BARU**, namun **TETAP DIDUKUNG PENUH** untuk penyimpanan data, query statistik, dan visualisasi grafik interaktif:

- `ec` (Konduktivitas Listrik Nutrisi)
- `light_intensity` (Intensitas Cahaya)
- `nutrient_temperature` (Suhu Tandon Nutrisi)
- `tank_level` (Level Tandon Nutrisi)
- `plant_height` (Tinggi Tanaman)
- `plant_density` (Kerapatan Kanopi)

*Catatan Integritas Data:* Seluruh kolom database dan endpoint REST API eksisting tetap dipertahankan 100% tanpa ada kolom yang dihapus.

---

## 3. KEBIJAKAN AMBANG BATAS (THRESHOLDS)

1. **Penyimpanan Ambang Batas Non-Alarm:**  
   Nilai `minimum_value` dan `maximum_value` untuk parameter non-alarm tetap disimpan di tabel `monitoring_parameters` sebagai batas wajar agronomis guna mempermudah analisis visual pengguna pada grafik.
2. **Diferensiasi Antarmuka (UI/UX):**  
   Pada halaman **Manajemen Ambang Batas** (`/admin/thresholds`), sistem menampilkan lencana visual eksplisit:
   - Parameter eligible: `<span class="badge">Parameter Alarm Aktif</span>`
   - Parameter non-eligible: `<span class="badge">Monitoring Visual Saja</span>`
3. **Preservasi Nilai Batas:**  
   Tidak ada modifikasi sepihak terhadap nilai ambang batas agronomis tanaman melon yang telah dikonfigurasi sebelumnya.

---

## 4. KEBIJAKAN TRANSISI ALARM LAMA (LEGACY ALARM TRANSITION POLICY)

### Prinsip Transisi:
- **Dilarang Menghapus Histori:** Riwayat alarm lama tidak boleh dihapus dengan perintah `DELETE` atau `TRUNCATE`. Snapshot nilai saat kejadian harus tetap tersimpan untuk audit akademik.
- **Penyelesaian Otomatis Terkendali:** Jika terdapat alarm aktif lama pada parameter yang kini dialihkan menjadi non-alarm (misalnya alarm aktif lama pada `tank_level` atau `ec`), alarm tersebut diselesaikan (*resolved*) secara tertib oleh service `MonitoringAlarmService::reconcileLegacyAlarms()`.

### Atribut Pembaruan Transisi:
- `status`: Diubah dari `'active'` menjadi `'resolved'`.
- `resolved_at`: Waktu eksekusi rekonsiliasi.
- `resolved_value`: Nilai abnormal terakhir yang terekam.
- `notes`: Ditambahkan catatan audit formal:  
  `"[Phase 13 Transition Policy] Diselesaikan otomatis: parameter ini dialihkan menjadi mode monitoring visual saja (non-alarm). Histori dipertahankan."`

### Konsistensi Penghitungan Notifikasi:
Endpoint polling badge navbar (`/api/notifications/status`) dan widget dashboard hanya menghitung alarm aktif dari 4 parameter yang diizinkan (`WHERE parameter_key IN ('temperature', 'humidity', 'soil_moisture', 'ph') AND status = 'active'`), sehingga jumlah notifikasi selalu akurat dan konsisten.
