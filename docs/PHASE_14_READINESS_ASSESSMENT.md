# LEMBAR EVALUASI KESIAPAN MENMENUJU PHASE 14 (READINESS ASSESSMENT)
## SISTEM WEB MONITORING TANAMAN MELON

**Tanggal Evaluasi:** 16 Oktober 2026  
**Status Evaluasi:** **READY FOR REVIEW / CONDITIONAL READY**  
**Fokus Phase 14:** Integrasi Data Google Sheets Riil & Persiapan Aktuator Fisik  

---

## 1. EVALUASI KESIAPAN ARSITEKTUR KODE LARAVEL

Dari sisi perangkat lunak Laravel, arsitektur sistem telah **100% SIAP** menerima integrasi fase berikutnya:

| Aspek Arsitektur | Status Kesiapan | Catatan Teknis |
| :--- | :---: | :--- |
| **Model Data Aktuator** | **SIAP** | Entitas `actuators` dan `actuator_command_logs` telah dimigrasikan dan teruji. |
| **Pemisahan State** | **SIAP** | Struktur data membedakan `simulation_state` dari `desired_hardware_state` dan `reported_hardware_state`. |
| **Audit Trail & Logging** | **SIAP** | Mekanisme pencatatan operator, waktu, perintah, dan status berhasil berjalan secara transaksional. |
| **Kebijakan 4 Parameter Alarm** | **SIAP** | Hanya 4 parameter kritis yang memicu alarm aktif; parameter lainnya aman sebagai data analitik visual. |
| **Kanal Telemetri Cadangan** | **SIAP** | REST API (`/api/monitorings`) dan Python Simulator tetap aktif dan tidak terganggu. |
| **Kesiapan UI/UX** | **SIAP** | Halaman kontrol, modal konfirmasi, paginasi riwayat, dan widget ringkasan dashboard telah terpasang. |

---

## 2. SYARAT PRASYARAT (PREREQUISITES) DARI TIM ELEKTRO UNTUK PHASE 14

Sebelum Phase 14 dapat dieksekusi, terdapat dokumen dan kredensial eksternal yang **WAJIB DISEDIAKAN** oleh Tim Teknik Elektro:

### A. Kredensial & Akses Google Sheets
- [ ] **Spreadsheet ID Riil:** ID dokumen spreadsheet Google resmi dari Tim Elektro.
- [ ] **Sheet Name Baku:** Nama lembar kerja yang memuat data telemetri (misal: `Data_Greenhouse_Melon`).
- [ ] **Google Cloud Service Account Credentials:** Berkas `service-account.json` yang memiliki hak akses baca (*Viewer*) ke spreadsheet terkait.
- [ ] **Konfirmasi Frekuensi Pengiriman:** Interval penulisan data oleh ESP32 (misal: tiap 1 menit, 5 menit, atau 10 menit).

### B. Spesifikasi Sistem Inferensi Fuzzy Logic
- [ ] **Kurva Fungsi Keanggotaan (*Membership Functions*):** Nilai titik batas numerik trapesium/segitiga untuk suhu, kelembapan, soil moisture, dan pH.
- [ ] **Matriks Aturan (*Rule Base*):** Tabel keputusan resmi yang telah divalidasi oleh pembimbing atau pakar agronomi.
- [ ] **Target Komputasi:** Kesepakatan tertulis apakah inferensi dijalankan di ESP32 (*edge*) atau dihitung di server Laravel.

### C. Protokol Keamanan & Keselamatan Hardware Aktuator
- [ ] **Interlock Fisik Hardware:** Memastikan sirkuit relay memiliki proteksi beban lebih (*overload*) dan proteksi waktu kerja maksimum (*maximum continuous runtime cut-off*) agar pompa tidak bekerja tanpa henti jika koneksi terputus.
- [ ] **Mekanisme Fallback Manual:** Tombol fisik manual di greenhouse yang dapat mengesampingkan perintah cloud jika terjadi kondisi darurat (*emergency stop*).

---

## 3. RISIKO DAN LIMITASI SAAT INI (PHASE 13)

1. **State Aktuator Belum Mencerminkan Relay Fisik:**  
   Perubahan tombol ON/OFF di web saat ini hanya mengubah nilai kolom di database MySQL (`actuators.simulation_state`). Status fisik pompa dan kipas di greenhouse belum berubah secara mekanikal.
2. **Ketergantungan Kuota Google Sheets API:**  
   Jika Tim Elektro memilih interval penulisan terlalu rapat (<10 detik) dan sinkronisasi Laravel tidak memakai kursor inkremental, terdapat risiko terkena *HTTP 429 Too Many Requests (Rate Limit)* dari Google Cloud.
3. **Keterlambatan Jaringan (*Network Latency*):**  
   Aliran data `ESP32 → Google Sheets → Laravel` memiliki latensi inheren (10 detik hingga 1 menit) dibandingkan koneksi direct MQTT/HTTP REST API.

---

## 4. PERNYATAAN STATUS AKHIR & PANDUAN PENGHENTIAN

> [!CAUTION]
> **ATURAN KETAT PHASE 13:**  
> Pengerjaan Phase 13 telah selesai secara paripurna. Sesuai instruksi pengguna, asisten **DILARANG** melanjutkan ke Phase 14 secara otomatis.  
> Asisten **BERHENTI DI SINI (STOP)** dan menunggu instruksi serta persetujuan dari pengguna.
