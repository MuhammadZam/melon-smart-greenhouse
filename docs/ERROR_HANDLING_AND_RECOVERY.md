# PENANGANAN KESALAHAN & PEMULIHAN SISTEM (PHASE 14)
## SISTEM WEB MONITORING TANAMAN MELON

**Versi Dokumen:** 1.0  
**Tanggal:** 16 Oktober 2026

---

## 1. TAKSONOMI KESALAHAN & STRATEGI PEMULIHAN

Modul integrasi telemetri Phase 14 mengklasifikasikan kesalahan ke dalam empat kategori utama:

| Kategori Kesalahan | Contoh Skenario | Dampak Sistem | Strategi Penanganan & Pemulihan |
| :--- | :--- | :--- | :--- |
| **1. Kesalahan Validasi Baris (Data Invalid)** | Suhu bernilai non-numerik (`"abc"`), pH di luar batas (`25.0`), timestamp korup | Baris bersangkutan tidak dapat disimpan ke MySQL | Baris ditandai `records_failed`, pesan error dicatat di log audit. Kursor baris tetap maju agar baris korup tidak memblokir pembacaan baris-baris berikutnya. Status run: `PARTIAL`. |
| **2. Kegagalan Jaringan & Timeout** | Google Sheets API tidak merespons dalam 10 detik, DNS gagal | Eksekusi sinkronisasi terputus | HTTP Client melakukan *retry* 3 kali dengan *exponential backoff* (500ms, 1000ms, 2000ms). Jika tetap gagal, lempar `GoogleSheetsException::timeout()`, catat log `FAILED`, dan status kesegaran beralih ke `error`. |
| **3. Pelanggaran Batas Kuota (Rate Limit)** | Request melampaui 60 request/menit dari Google API | Respons HTTP 429 Too Many Requests | Tangkap `GoogleSheetsException::rateLimited()`, batalkan eksekusi batch saat ini, lepaskan scheduler lock, dan tunda jadwal sinkronisasi berikutnya selama 5 menit. |
| **4. Kesalahan Hak Akses & Kredensial** | File `credentials.json` hilang, izin sheet belum diberikan | Respons HTTP 401 / 403 | Tangkap `GoogleSheetsException::unauthenticated()`, tandai integrasi live sebagai `PENDING`, berikan catatan instruksi bagi Administrator di dashboard web. |

---

## 2. PENGAMANAN KONKURENSI (SCHEDULER LOCK)

Untuk mencegah dua proses sinkronisasi berjalan bersamaan (misal cron job berjalan sebelum proses manual selesai):
```php
$lock = Cache::lock("google_sheets_sync_lock_{$config->id}", 60);

if (!$lock->get()) {
    // Proses sebelumnya masih berjalan: lewati eksekusi saat ini secara aman
    return GoogleSheetsSyncLog::create([
        'status' => 'FAILED',
        'error_details' => 'Sinkronisasi dilewati: scheduler lock aktif pada server.',
        ...
    ]);
}

try {
    // Proses sinkronisasi inkremental
} finally {
    $lock->release();
}
```

---

## 3. PANDUAN PEMULIHAN OPERASIONAL (RUNBOOK UNTUK ADMIN)

### Skenario A: Spreadsheet Diurutkan Ulang atau Baris Baru Disisipkan di Tengah
Jika pengguna spreadsheet menyisipkan data di baris-baris awal yang telah terlewati oleh kursor:
1. Buka terminal server.
2. Jalankan perintah:
   ```bash
   php artisan telemetry:sync-google-sheets --reset-cursor
   ```
3. Sistem akan mereset `last_synced_row` kembali ke baris 1.
4. Mesin sinkronisasi membaca ulang seluruh baris; data yang sudah ada akan otomatis dilewati melalui pengecekan `source_identifier` (tanpa insert duplikat).

### Skenario B: Ganti File Kredensial Service Account
1. Unduh file private key JSON baru dari Google Cloud Console.
2. Timpa file di `storage/app/credentials/google-service-account.json`.
3. Buka halaman `/admin/integrations/google-sheets` dan klik **Sinkronkan Sekarang**.
