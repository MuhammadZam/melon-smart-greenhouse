# LAPORAN AUDIT & PENGUATAN KEAMANAN SISTEM (PHASE 16)
## SISTEM WEB MONITORING TANAMAN MELON

**Tanggal:** 17 Oktober 2026  
**Status Audit:** ✅ **VERIFIED SECURE**  
**Role:** Senior Security Engineer, Laravel Security Specialist, dan Systems Auditor  

---

## 1. IKHTISAR AUDIT KEAMANAN

Seiring dengan finalisasi antarmuka dan penyediaan modul laporan yang memproses data telemetri historis, audit penguatan keamanan (*Security Hardening Audit*) telah dilakukan untuk memastikan bahwa seluruh permukaan serangan (*attack surface*) tertutup dan memenuhi standar keamanan aplikasi web modern.

---

## 2. MATRIKS EVALUASI KEAMANAN

| Area Keamanan | Mekanisme Pertahanan | Status Evaluasi | Keterangan |
|---|---|:---:|---|
| **Autentikasi & Sesi** | Session-based Auth, Bcrypt hashing (cost 12), proteksi session fixation | ✅ SECURE | Tamu dialihkan ke login saat mengakses rute terproteksi |
| **Role-Based Access (RBAC)** | Role middleware (`admin`, `pengamat`), otorisasi granular di controller | ✅ SECURE | Admin memiliki akses penuh; Pengamat read-only; Tamu diblokir |
| **Proteksi CSRF** | Laravel CSRF token verification pada semua request `POST`, `PUT`, `DELETE` | ✅ SECURE | Form filter laporan menggunakan metode `GET` idempotent |
| **Proteksi Injeksi SQL** | PDO Prepared Statements via Eloquent ORM & Query Builder | ✅ SECURE | Tidak ada *raw SQL concatenations* yang rentan injeksi |
| **CSV Formula Injection** | Karakter awal berbahaya (`=`, `+`, `-`, `@`, `\t`, `\r`) disanitasi | ✅ SECURE | Mencegah eksploitasi remote code execution via Excel |
| **Autentikasi Perangkat IoT** | Header token & signature verification pada endpoint `/api/monitorings` | ✅ SECURE | Endpoint ingestion terlindungi dari pengiriman data tanpa otorisasi |
| **Kerahasiaan Kredensial** | `.env` separation, penyembunyian private key Google Sheets | ✅ SECURE | Tidak ada token atau kredensial yang bocor ke HTML/JS |
| **Simulasi Aktuator** | Operasi kontrol simulasi hanya dapat dieksekusi oleh Admin | ✅ SECURE | Tidak ada koneksi fisik langsung ke relay perangkat keras |
| **Logging & Error Disclosure**| `APP_DEBUG` terisolasi, penanganan exception terenkapsulasi | ✅ SECURE | Tidak membocorkan jejak tumpukan (*stack trace*) ke pengguna |

---

## 3. AUDIT KHUSUS AREA BERISIKO TINGGI

### A. Sanitasi Injeksi Formula CSV (CWE-1236):
- **Risiko:** Pengguna dengan hak input catatan tanaman atau penyerang yang mengirim payload API dapat menyisipkan string seperti `=CMD|' /C calc'!A0` atau `+2+5` pada kolom `notes` atau `plant_condition`. Ketika staf akademik mengunduh CSV dan membukanya di Microsoft Excel, payload dapat mengeksekusi perintah shell berbahaya.
- **Implementasi Hardening:**
  ```php
  // app/Services/Reports/MonitoringReportExportService.php
  if (is_string($value) && strlen($value) > 0) {
      if (in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
          $value = "'" . $value;
      }
  }
  ```
- **Hasil:** Berhasil dinetralkan 100% dan terverifikasi oleh `test_17_csv_export_sanitizes_against_formula_injection()`.

### B. Pemisahan Hak Akses Ekspor & Laporan:
- Modul `/reports/monitoring` beserta rute ekspor `/export/csv` dan `/export/pdf` dilindungi oleh middleware `auth` dan hak akses `Pengamat` serta `Admin`.
- Tamu yang mencoba mengunduh dokumen secara langsung melalui URL dipaksa melakukan autentikasi terlebih dahulu.

### C. Pemisahan Simulator vs Kredensial Produksi:
- Simulator IoT Python yang digunakan untuk pengujian beban dan demonstrasi menggunakan token pengujian lokal yang terisolasi.
- Token simulator ini didefinisikan secara terpisah dan dilarang keras untuk digunakan sebagai kredensial produksi pada perangkat keras ESP32 fisik kelak.

### D. Pencegahan Bypass Kontrol Aktuator:
- Pengamat (*Observer*) yang mencoba mengirim request `POST /actuators/{actuator}/control` ditolak dengan response HTTP 403 (*Forbidden*).
- Status kontrol aktuator hanya memanipulasi baris status pada tabel database simulasi tanpa mengeksekusi GPIO controller fisik.

---

## 4. REKOMENDASI PENGUATAN UNTUK DEPLOYMENT PRODUKSI (PHASE 17)

1. Pastikan variabel `APP_DEBUG=false` pada environment produksi server kampus.
2. Gunakan protokol HTTPS (TLS 1.3) untuk seluruh komunikasi web dan telemetri API guna mencegah *Man-in-the-Middle* (MitM) sniffing pada data sensor.
3. Batasi laju permintaan (*Rate Limiting*) pada endpoint `/api/monitorings` untuk mengantisipasi potensi serangan *Denial-of-Service* (DoS) dari perangkat IoT yang mengalami gangguan jaringan.
