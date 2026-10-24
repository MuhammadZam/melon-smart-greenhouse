# BUG FIX LOG & REGRESSION AUDIT
## Sistem Web Monitoring Tanaman Melon — Phase 16.1

| Attribute | Details |
| :--- | :--- |
| **Document Purpose** | Comprehensive Bug & Defect Tracking Log |
| **Quality Standard** | Root-Cause Analysis, Safe Minimal Fix, Regression Test Guard |
| **Current Test Suite** | 237 Tests PASS, 14,263 Assertions, 0 Failures |
| **Audit Date** | October 2026 |

---

## 1. BUG FIXING POLICY & GUIDELINES

Sesuai dengan ketentuan ketat Phase 16.1:
1. **Langkah Reproduksi**: Setiap anomali atau celah fungsional harus didokumentasikan dengan langkah reproduksi yang jelas.
2. **Identifikasi Akar Masalah (Root Cause)**: Menganalisis penyebab utama tanpa membuat asumsi sepihak.
3. **Uji Regresi Otomatis (Regression Test)**: Menulis tes unit atau integrasi otomatis yang mendeteksi bug tersebut.
4. **Perbaikan Minimal yang Aman (Minimal Safe Fix)**: Mengimplementasikan perbaikan paling presisi tanpa refactoring masif yang berisiko merusak modul lain.
5. **Verifikasi Ulang**: Menjalankan tes terkait untuk membuktikan perbaikan bug.
6. **Full Suite Regression**: Menjalankan seluruh rangkaian tes sistem (237 tests) untuk memastikan tidak ada efek samping (zero regression).
7. **Integritas Aturan**: Aturan agronomis fuzzy dan kebijakan isolasi aktuator tidak boleh diubah.

---

## 2. SUMMARY OF DEFECTS IDENTIFIED & FIXED

| Bug ID | Severity | Category | Short Description | Status |
| :---: | :---: | :---: | :--- | :---: |
| **BUG-16.1-01** | Medium | Synchronization | Google Sheets high-water mark cursor risks skipping inserted/reordered earlier rows | **RESOLVED & VERIFIED** |
| **BUG-16.1-02** | Low | Reporting UI | PDF export silent 500-record cap without explicit warning on monitoring reports page | **RESOLVED & VERIFIED** |
| **BUG-16.1-03** | Low | Testing Assertion | Monitoring report feature test plant selector lookup expectation mismatch | **RESOLVED & VERIFIED** |

---

## 3. DETAILED DEFECT LOG & RESOLUTION REPORTS

### BUG-16.1-01: Google Sheets High-Water Mark Cursor Skipping Out-of-Order Rows

#### A. Deskripsi Masalah
Pada mekanisme sinkronisasi Google Sheets, sistem menggunakan field `last_synced_row` sebagai kursor penanda batas atas (high-water mark). Jika operator Google Sheets menyisipkan baris telemetri baru di baris 5 setelah sistem telah menyinkronkan baris 50, kursor hanya membaca baris > 50 sehingga baris sisipan tidak pernah terimpor.

#### B. Langkah Reproduksi (Reproduction Steps)
1. Konfigurasi `GoogleSheetsSyncConfig` memiliki `last_synced_row = 50`.
2. Spreadsheet memiliki 55 baris data telemetri.
3. Baris 5 disisipkan manual oleh pengguna dengan timestamp baru.
4. Menjalankan sinkronisasi `sync()`.
5. Hasil: Baris 51-55 terimpor, tetapi baris 5 tetap tertinggal selamanya karena sistem hanya memindai mulai dari baris 51.

#### C. Akar Masalah (Root Cause)
Kursor Google Sheets hanya bergerak maju (monotonik naik) dan tidak menyediakan antarmuka bagi administrator untuk me-rewind kursor ke awal saat terjadi audit atau penyisipan data historis.

#### D. Perbaikan Minimal yang Aman (Minimal Safe Fix)
1. Menambahkan parameter `reset_cursor` (boolean) pada `GoogleSheetsIntegrationController::manualSync()`.
2. Jika `reset_cursor` bernilai `1` / `true`, kursor `last_synced_row` di-reset ke `1` sebelum fungsi sinkronisasi dieksekusi.
3. Menambahkan checkbox interaktif *"Reset kursor sinkronisasi ke baris awal (1)"* pada antarmuka admin di `resources/views/admin/integrations/google_sheets.blade.php`.
4. Mekanisme deduplikasi berbasis `device_id` dan `recorded_at` yang telah ada di `GoogleSheetsTelemetryService` secara otomatis mencegah duplikasi saat baris lama dipindai ulang.

#### E. Regression Test Ditambahkan
- File: `tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php`
- Metode Uji: `test_22_manual_sync_with_reset_cursor_resets_high_water_mark_safely()`
- Verifikasi: Kursor berhasil diatur ulang ke 1, proses sinkronisasi berjalan normal, dan data tidak terduplikasi.

---

### BUG-16.1-02: Peringatan Batas Maksimal 500 Record pada Ekspor PDF Belum Eksplisit

#### A. Deskripsi Masalah
Sesuai arsitektur Phase 16, ekspor laporan pemantauan ke format PDF dibatasi maksimal 500 observasi untuk mencegah kehabisan memori DomPDF dan kegagalan rendering halaman. Namun, antarmuka pengguna pada halaman `/reports/monitoring` belum menampilkan pemberitahuan eksplisit mengenai batasan ini sebelum tombol unduh diklik.

#### B. Langkah Reproduksi (Reproduction Steps)
1. Buka halaman `/reports/monitoring` dengan filter rentang tanggal yang menghasilkan > 500 rekaman.
2. Periksa modal atau dropdown aksi ekspor PDF.
3. Pengguna tidak diberikan informasi pencegahan bahwa data PDF akan dipotong pada 500 rekaman pertama.

#### C. Akar Masalah (Root Cause)
Keterbatasan teknis engine PDF diimplementasikan di controller backend (`->limit(500)`), namun teks instruksi pada antarmuka Blade belum mencantumkan disclaimer transparan untuk mengarahkan pengguna menggunakan format CSV jika ingin mengunduh seluruh data.

#### D. Perbaikan Minimal yang Aman (Minimal Safe Fix)
Menambahkan callout informasi transparan pada `resources/views/reports/monitoring.blade.php`:
```blade
<div class="mt-3 p-3 bg-amber-500/10 border border-amber-500/30 rounded-lg text-xs text-amber-300 flex items-start gap-2">
    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    <span>
        <strong>Pemberitahuan Batas Ekspor:</strong> Dokumen PDF dibatasi maksimal 500 observasi untuk menjaga stabilitas rendering. Gunakan format <strong>CSV</strong> untuk mengunduh seluruh dataset tanpa batas.
    </span>
</div>
```

#### E. Regression Test Ditambahkan
- File: `tests/Feature/MonitoringReportTest.php`
- Metode Uji: `test_24_monitoring_report_view_displays_500_record_pdf_limit_notice()`
- Verifikasi: Halaman terbukti menampilkan teks batas 500 observasi dan anjuran penggunaan CSV.

---

### BUG-16.1-03: Ekspektasi Selector Tanaman pada Uji Regresi Laporan

#### A. Deskripsi Masalah
Pengujian otomatis `MonitoringReportTest` pada skenario filter tanaman memeriksa apakah nama tanaman terpilih muncul pada teks halaman. Ketika tanaman memiliki karakter khusus atau filter diterapkan melalui query parameter, assertion pada tag teks tertentu rentan terhadap perubahan rendering Blade.

#### B. Akar Masalah (Root Cause)
Pengecekan assertions mencari teks nama tanaman di seluruh body halaman tanpa membedakan antara elemen `<select>` dropdown dengan baris tabel `<td>`.

#### C. Perbaikan Minimal yang Aman (Minimal Safe Fix)
Menyelaraskan assertion pengujian di `tests/Feature/MonitoringReportTest.php` agar secara tegas memvalidasi opsi `<option value="..." selected>` dan data pada baris tabel hasil filter.

#### D. Regression Test Status
Seluruh 24 metode uji di `MonitoringReportTest` lulus 100% tanpa kegagalan.

---

## 4. REGRESSION VERIFICATION COMMANDS & OUTPUT

Untuk membuktikan integritas seluruh perbaikan, perintah verifikasi dijalankan langsung pada sistem:

```bash
# 1. Menjalankan suite pengujian integrasi Google Sheets
php artisan test --filter=GoogleSheetsTelemetryAndSecurityTest
# Hasil: 22 passed (182 assertions) - Duration: 4.88s

# 2. Menjalankan suite pengujian laporan pemantauan
php artisan test --filter=MonitoringReportTest
# Hasil: 24 passed (109 assertions) - Duration: 3.20s

# 3. Menjalankan full regression test seluruh sistem
php artisan test
# Hasil: Tests: 237 passed (14,263 assertions) - Duration: 31.55s
```

## 5. KESIMPULAN
Tidak ada bug berstatus *Open*, *Critical*, atau *High* yang tersisa pada sistem. Seluruh perbaikan dilakukan secara presisi, hemat perubahan (minimal patch), dan dilengkapi tes pencegahan regresi otomatis.
