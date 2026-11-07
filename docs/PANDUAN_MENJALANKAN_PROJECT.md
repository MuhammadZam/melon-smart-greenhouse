# Panduan Menjalankan Project

Panduan lokal Windows/PowerShell untuk Sistem Web Monitoring Tanaman Melon, berdasarkan konfigurasi repository pada 29 Oktober 2026.

## 1. Menjalankan project yang sudah terpasang di komputer ini

Project berada di `D:\Compro`. Saat panduan dibuat, `.env` menggunakan SQLite di `D:/Compro/database/database.sqlite`, serta folder `vendor` dan `node_modules` sudah tersedia.

Buka terminal pertama di VS Code:

```powershell
cd D:\Compro
php artisan serve --host=127.0.0.1 --port=8000
```

Buka terminal kedua dan biarkan kedua terminal tetap berjalan:

```powershell
cd D:\Compro
npm.cmd run dev
```

Buka **http://127.0.0.1:8000**. Masuk menggunakan akun demo jika akun tersebut sudah dibuat melalui seeder:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@melon.com` | `password123` |
| Pengamat | `pengamat@melon.com` | `password123` |

Gunakan `Ctrl+C` pada masing-masing terminal untuk menghentikan server. Menjalankan ulang project cukup mengulangi dua perintah server di atas; tidak perlu mengulang seeder.

## 2. Instalasi awal pada salinan baru

Siapkan PHP 8.2+ beserta ekstensi yang dibutuhkan Composer dan `pdo_sqlite`/`sqlite3`, Composer, serta Node.js dan npm yang kompatibel dengan dependency terkunci project. Jalankan dari root project:

```powershell
cd D:\Compro
if (!(Test-Path .env)) { Copy-Item .env.example .env }
composer install
npm.cmd ci
```

Edit `.env` untuk memakai SQLite lokal. Sesuaikan path jika lokasi project berbeda:

```dotenv
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
DB_DATABASE=D:/Compro/database/database.sqlite
```

Nilai `DB_HOST`, `DB_PORT`, `DB_USERNAME`, dan `DB_PASSWORD` tidak dipakai oleh koneksi SQLite. Jika ada `DB_URL` yang berisi koneksi lain, kosongkan agar pengaturan SQLite ini berlaku.

```powershell
if (!(Test-Path database/database.sqlite)) { New-Item -ItemType File -Path database/database.sqlite | Out-Null }
```

Hanya jika `APP_KEY` masih kosong, buat key aplikasi:

```powershell
php artisan key:generate
```

Kemudian:

```powershell
php artisan config:clear
php artisan migrate
```

Untuk database baru yang ingin diisi akun dan data demo, jalankan sekali:

```powershell
php artisan db:seed
```

Seeder menambahkan data simulasi dan menetapkan akun demo. Jangan mengulanginya pada database berisi data penting tanpa memeriksa dampaknya. `migrate:fresh --seed` menghapus seluruh tabel; perintah tersebut tidak diperlukan untuk menjalankan project.

Lanjutkan dengan dua terminal pada bagian 1.

## 3. Menjalankan dengan asset hasil build

Sebagai alternatif server Vite, hentikan `npm.cmd run dev`, kemudian jalankan:

```powershell
npm.cmd run build
php artisan serve --host=127.0.0.1 --port=8000
```

Jika Vite terhenti paksa dan browser masih meminta server Vite, periksa `public/hot`. Hapus file tersebut hanya ketika server Vite sudah berhenti, lalu muat ulang halaman.

## 4. Alternatif Docker dan MySQL

Gunakan Docker Desktop dengan Docker Compose, serta Node.js/npm di host untuk asset. Untuk salinan baru, salin `.env.example` menjadi `.env` sebelum menjalankan Compose. Untuk project existing, cadangkan `.env` dan database sebelum beralih dari SQLite ke MySQL; data SQLite tidak otomatis dipindahkan.

Konfigurasi contoh database berada di `.env.example`. Service `app` di `compose.yaml` memakai MySQL dengan host `mysql` dan port internal `3306`. Port akses aplikasi dari Windows adalah `8000`, dan port MySQL dari Windows adalah `3308` secara default. Pastikan variabel `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, dan `DB_ROOT_PASSWORD` sesuai konfigurasi MySQL, serta `DB_URL` kosong.

```powershell
docker compose build
docker compose run --rm app composer install
```

Jika `APP_KEY` kosong:

```powershell
docker compose run --rm app php artisan key:generate
```

```powershell
docker compose run --rm app php artisan config:clear
docker compose run --rm app php artisan migrate
```

Hanya untuk mengisi database demo baru:

```powershell
docker compose run --rm app php artisan db:seed
```

```powershell
npm.cmd ci
npm.cmd run build
docker compose up -d
docker compose logs --tail=50 app
```

Buka **http://localhost:8000**. Hentikan dengan `docker compose down`. Entrypoint container tidak menginstal dependency atau menjalankan migrasi otomatis. Jalur Docker/MySQL belum diverifikasi saat audit 22 Oktober 2026 karena daemon Docker tidak tersedia.

## 5. Kendala umum dan dokumentasi terkait

- `php`/`composer` tidak dikenali: periksa PATH instalasi PHP/Composer. Di komputer ini PHP tersedia di `C:\xampp\php\php.exe`.
- PowerShell memblokir `npm.ps1`: gunakan `npm.cmd` seperti contoh di atas.
- `could not find driver`: aktifkan ekstensi driver database yang dipakai pada `php.ini` untuk PHP CLI.
- `no such table`: periksa koneksi database, lalu jalankan `php artisan migrate`.
- `No application encryption key`: buat `APP_KEY` hanya jika masih kosong.
- `Vite manifest not found`: jalankan server Vite atau build asset sesuai bagian 1/3.
- Port 8000 terpakai: gunakan `php artisan serve --port=8001`, lalu buka port tersebut.
- Tampilan rusak setelah build: laporan audit mencatat masalah CSS/Tailwind; keberhasilan server berjalan tidak menjamin seluruh temuan audit sudah diperbaiki.

Lihat [laporan audit utama](audit/MASTER_PROJECT_AUDIT_REPORT.md) untuk temuan dan batas verifikasi, [panduan Google Sheets](GOOGLE_SHEETS_SETUP_GUIDE.md), [autentikasi perangkat](DEVICE_AUTHENTICATION_GUIDE.md), dan [simulator IoT](../iot-simulator/README.md) untuk integrasi opsional. Integrasi tersebut tidak diperlukan untuk membuka aplikasi lokal.
