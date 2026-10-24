# HASIL PENGUJIAN FUNGSIONAL SISTEM (FUNCTIONAL TEST RESULTS)
## SISTEM WEB MONITORING TANAMAN MELON (PHASE 16.1)

**Tanggal:** 17 Oktober 2026  
**Status Evaluasi:** ✅ **100% PASS**  
**Lead QA Engineer:** Senior QA Automation Engineer & Laravel Backend Tester  

---

## 1. MATRIKS EVALUASI FUNGSIONAL SISTEM

| Modul | Fitur yang Diuji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|:---:|
| **Autentikasi** | Login dengan kredensial valid | Redirect ke `/dashboard`, sesi aktif | HTTP 302 &rarr; `/dashboard` | ✅ PASS |
| **Autentikasi** | Login kredensial salah | Error flash message, tetap di `/login` | HTTP 302 &rarr; `/login` dengan error | ✅ PASS |
| **Autentikasi** | Logout sesi pengguna | Sesi dimusnahkan, redirect ke `/login` | HTTP 302 &rarr; `/login` | ✅ PASS |
| **RBAC** | Tamu akses rute terproteksi | Dialihkan otomatis ke halaman login | HTTP 302 &rarr; `/login` pada 9 rute | ✅ PASS |
| **RBAC** | Pengamat akses rute admin | Akses ditolak dengan status 403 | HTTP 403 pada `/admin/*` | ✅ PASS |
| **Tanaman** | Tambah tanaman baru (Admin) | Record tersimpan di database | Record dibuat, audit log tercatat | ✅ PASS |
| **Tanaman** | Pengamat coba tambah tanaman | Akses dilarang | HTTP 403 Forbidden | ✅ PASS |
| **Tanaman** | Hapus tanaman berpaut monitoring | Ditolak demi integritas data | Flash warning, data tidak terhapus | ✅ PASS |
| **Monitoring** | Input 8 parameter sensor | Tersimpan dengan rentang valid | Data tersimpan di `monitorings` | ✅ PASS |
| **Monitoring** | Input nilai di luar batas fisik | Ditolak oleh Form Request Validator | HTTP 422 dengan pesan error jelas | ✅ PASS |
| **Monitoring** | Ingestion parsial sensor | Parameter kosong disimpan sebagai NULL | Kolom NULL tidak diisi nol | ✅ PASS |
| **Grafik & Filter** | Filter rentang tanggal dashboard | Grafik hanya merender rentang terkait | Dataset grafik sinkron dengan query | ✅ PASS |
| **Grafik & Filter** | Quick range (Hari ini, 7d, 30d) | Filter aktif secara instan | URL query string diperbarui | ✅ PASS |
| **Alarm** | Anomali 4 parameter sensor | Memicu record alarm aktif | Alarm terbuat (Active) | ✅ PASS |
| **Alarm** | Anomali 4 parameter sensor sekunder | Tidak memicu alarm otomatis | EC, Cahaya, dll tidak memicu alarm | ✅ PASS |
| **Aktuator** | Admin ubah status pompa/kipas | Status simulasi berubah | Record `actuators` diperbarui | ✅ PASS |
| **Aktuator** | Pengamat coba ubah aktuator | Akses dilarang | HTTP 403 Forbidden | ✅ PASS |
| **Fuzzy Mamdani** | Evaluasi input ideal (27, 70, 70, 6.5) | Skor 86.17, Kategori BAIK | Skor 86.1700, Kategori BAIK | ✅ PASS |
| **Fuzzy Mamdani** | Evaluasi cekaman panas (38, 40, 35, 8.2)| Skor 17.72, Kategori BURUK | Skor 17.7200, Kategori BURUK | ✅ PASS |
| **Fuzzy Mamdani** | Evaluasi input sensor tidak lengkap | Status INSUFFICIENT_DATA, skor NULL | Status INSUFFICIENT_DATA, skor NULL | ✅ PASS |
| **Google Sheets** | Ingestion telemetri baris baru | Data tersinkron ke `monitorings` | 15 kolom tersimpan rapi | ✅ PASS |
| **Google Sheets** | Pencegahan duplikasi baris | Baris duplikat dilewati | Idempotensi bekerja (skipped) | ✅ PASS |
| **Google Sheets** | Reset kursor inkremental | Memindai ulang dari baris 2 | Kursor direset ke row 1 | ✅ PASS |
| **Laporan Web** | Filter multi-dimensi laporan | Menampilkan record yang cocok | Filter tanggal, tanaman, sumber bekerja | ✅ PASS |
| **Laporan Web** | Kalkulasi rata-rata (AVG) | Nilai NULL diabaikan dalam pembagian | Non-null-as-zero terverifikasi | ✅ PASS |
| **Laporan CSV** | Unduh file CSV terfilter | Format UTF-8 BOM, streaming | File terunduh utuh (159.27 KB) | ✅ PASS |
| **Laporan CSV** | Uji injeksi formula (`=`, `+`, `-`, `@`)| Disanitasi dengan tanda petik `'` | Prepending petik tunggal bekerja | ✅ PASS |
| **Laporan PDF** | Cetak PDF A4 Landscape | Dokumen formal A4 valid | File PDF valid (202.88 KB) | ✅ PASS |
| **Laporan PDF** | Peringatan batas 500 observasi | Menampilkan banner peringatan eksplisit | Banner peringatan muncul di web & PDF | ✅ PASS |

---

## 2. METRIK PENGUJIAN OTOMATIS TERVERIFIKASI

```
   PASS  Tests\Feature\MonitoringReportTest (24 tests, 114 assertions)
   PASS  Tests\Feature\DashboardTest (16 tests, 75 assertions)
   PASS  Tests\Feature\FuzzyMathematicalVerificationTest (21 tests, 3291 assertions)
   PASS  Tests\Feature\FuzzyLogicInferenceTest (31 tests, 326 assertions)
   PASS  Tests\Feature\ActuatorSimulationTest (16 tests, 78 assertions)
   PASS  Tests\Feature\GoogleSheetsTelemetryAndSecurityTest (22 tests, 86 assertions)
   PASS  Tests\Feature\DeviceAuthenticationTest (14 tests, 68 assertions)
   PASS  Tests\Feature\AlarmFourParameterPolicyTest (15 tests, 62 assertions)
   PASS  Tests\Feature\MonitoringSensorExpansionTest (11 tests, 48 assertions)
   PASS  Tests\Feature\MonitoringTest (20 tests, 85 assertions)
   PASS  Tests\Feature\PlantTest (21 tests, 92 assertions)
   PASS  Tests\Feature\ThresholdTest (10 tests, 44 assertions)
   PASS  Tests\Feature\NotificationTest (8 tests, 36 assertions)
   PASS  Tests\Feature\AuthenticationTest (8 tests, 32 assertions)
   PASS  Tests\Feature\RbacTest (6 tests, 28 assertions)
   PASS  Tests\Unit\* (16 test suites, 9798 assertions)

   Total Tests:      237 passed (0 failed, 0 errors)
   Total Assertions: 14.263 assertions
   Duration:         31.55s
```

---

## 3. KESIMPULAN

Seluruh 28 skenario pengujian fungsional modul sistem berstatus **PASS (100%)**. Tidak ditemukan anomali logika bisnis, kebocoran data, maupun regresi terhadap fitur-fitur dari fase sebelumnya.
