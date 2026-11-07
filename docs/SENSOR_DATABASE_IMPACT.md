# Dampak database revisi tiga sensor

Tanggal: 7 November 2026. Database proyek dan volume Docker existing tidak dimigrasikan, dihapus, atau di-rollback selama pengerjaan.

## Perubahan skema yang disiapkan

`database/migrations/2026_11_07_000001_allow_retired_humidity_to_be_null.php` hanya memperbolehkan NULL pada kolom decimal `monitorings.humidity`. Kolom lama semula NOT NULL, sehingga observasi baru tanpa kelembapan udara memerlukan perubahan kompatibel ini. Tipe dan presisi tetap sama; seluruh nilai lama tetap utuh. `down()` sengaja tidak mengembalikan NOT NULL karena observasi baru dapat memiliki NULL.

Migration ini **belum diterapkan ke database proyek**. Sesuai instruksi pengguna, tidak ada perintah `migrate`, destructive rollback, DROP TABLE, atau penghapusan volume yang dijalankan. Penerapan non-destruktif perlu dijadwalkan sebelum mengaktifkan kode baru pada database existing. Tidak diperlukan perubahan destruktif atau persetujuan penghapusan data.

MySQL pengujian diinisialisasi pada database Docker baru dan terpisah dengan menjalankan `up()` definisi skema. Ini tidak memakai database `.env` proyek. Harness pengujian menolak koneksi selain SQLite memory atau database MySQL bernama `compro_revision_test` pada port 3327. Setiap tes menggunakan transaksi yang dibatalkan. Uji konkurensi/UI menggunakan `compro_revision_concurrency` terpisah pada container uji yang sama.

## Preservasi

- `ph` tetap kolom fisik; accessor `water_ph` menghindari rename, backfill, atau duplikasi nilai.
- `humidity`, `ec`, `light_intensity`, `nutrient_temperature`, `tank_level`, dan reported_fan_state tetap ada pada skema/model untuk sejarah. Request tervalidasi dan serializer aktif tidak mempublikasikannya sebagai sensor aktif.
- Update formulir hanya mengubah field tervalidasi; nilai sensor nonaktif yang pernah tersimpan tidak di-null-kan.
- Record aktuator FAN-01 dan command log lama dipertahankan; service menolak perintah baru dan UI aktif hanya memuat PUMP-01.
- Alarm lama tidak dihapus. Scope aktif/ringkasan mengabaikan parameter nonaktif. Fungsi rekonsiliasi legacy yang sudah ada tetap tersedia secara eksplisit, mencatat resolusi kebijakan; fungsi ini tidak dijalankan pada database proyek.
- Threshold numerik existing tidak diubah. Accessor label/satuan hanya menyajikan pH air tanpa satuan; nilai mentah dan snapshot database tidak ditulis ulang.
- Hasil inferensi lama tetap tersimpan menurut monitoring, sumber, dan versi. Versi lokal baru `v2.0-three-input-PROVISIONAL` membuat hasil terpisah; hasil eksternal memakai provenance `external-unverified`.
- Ledger `monitoring_ingestion_keys`, unique identity, transaksi, locking, serta autentikasi perangkat tetap digunakan. Receipt lama tidak dihapus untuk memaksa ingest ulang.

## Audit threshold

Sumber runtime adalah `monitoring_parameters`: minimum_value, maximum_value, critical_min_value, critical_max_value. Nilai berikut adalah preset repository sebelumnya, bukan hasil pengukuran database proyek atau validasi agronomis baru:

| Key storage | Minimum–maksimum | Critical minimum | Critical maksimum |
|---|---|---|---|
| temperature | 20–32 | NULL | 38 |
| soil_moisture | 60–80 | 30 | NULL |
| ph | 5.8–7.2 | 4.5 | 8.5 |

Pelanggaran memakai `< minimum` / `> maximum`; batas tepat termasuk rentang normal. Severity mengikuti critical threshold yang sudah tersimpan. Perubahan threshold admin dicatat di application log dengan user ID, parameter ID, nilai sebelum, dan sesudah. Parameter nonaktif tidak dapat diperbarui melalui endpoint threshold aktif; parameter_key tidak dapat dialihkan melalui form.

Jangan menjalankan ulang seeder threshold pada database existing untuk menerapkan revisi: seeder adalah fixture/fresh setup dan dapat memulihkan nilai preset. Tinjau serta pertahankan konfigurasi aktual terlebih dahulu. Perubahan label seeder pH mempertahankan identitas registry lama bila sudah ada.
