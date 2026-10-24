# Phase 16.2 — Deduplication & Data Integrity Fix

Project: **Sistem Web Monitoring Tanaman Melon** — `D:\Compro`.
Tanggal penyelesaian: **24 Oktober 2026 (Asia/Jakarta)**. Pekerjaan dimulai 22 Oktober dan dilanjutkan setelah interupsi.

## Root cause dan reproduksi

API lama selalu Monitoring::create. Source identifier hanyalah index nonunique, sehingga dua retry identik menghasilkan 201 dua kali dan dua record. Sheets menggunakan pre-check aplikasi serta plant+timestamp fallback tanpa source/device, menyebabkan race dan potensi penggabungan observasi valid.

## Identitas observasi baru

Identitas di-hash SHA-256 dari array terstruktur: versi v1, source server, device_id terautentikasi/telemetry, plant_id, jenis identitas, lalu stable event ID atau canonical timestamp. Nilai sensor tidak pernah dipakai sebagai identitas.

1. Utamakan header **Idempotency-Key**, atau body **idempotency_key/event_id**. Jika beberapa disediakan, nilainya harus sama.
2. Key wajib string ASCII 1–128 karakter: karakter pertama alfanumerik, berikutnya alfanumerik/titik/underscore/titik dua/dash. Kosong, slash, whitespace dan key terlalu panjang memperoleh 422.
3. Tanpa key, gunakan device + source + plant + waktu pengukuran yang dinormalisasi ke timezone aplikasi, termasuk enam digit mikrodetik. Bentuk timezone ekuivalen menghasilkan key sama.
4. Pengukuran berbeda dengan sensor sama tetap berbeda jika event ID/waktu berbeda. Source/device berbeda juga mempunyai namespace berbeda.
5. Client harus memakai ID yang sama pada retry dan ID baru pada observasi baru. Jangan berganti dari strategi timestamp ke event ID untuk replay event yang sama. Jika dua event benar-benar mempunyai timestamp sama, kirim event ID berbeda.

## Database dan transaksi

Tabel baru **monitoring_ingestion_keys** mempunyai identity_hash PRIMARY KEY, payload_hash, observed_at, monitoring_id nullable/FK dan created_at. observed_at menjaga waktu mikrodetik; kolom/tampilan monitorings legacy tetap detik. Tidak mengubah precision histori lama.

Service menyisipkan unique receipt **sebelum** membuat monitoring, alarm dan inference dalam transaksi. Satu writer menjadi pemenang; duplicate constraint ditangani setelah rollback transaksi yang kalah, lalu membaca receipt yang telah committed. Payload fingerprint menggunakan nilai canonical (alias/default/type numeric diseragamkan), bukan raw JSON byte atau kesamaan sensor sebagai identitas.

- Baru: **201**, meta.created=true, meta.replayed=false.
- Replay identik: **200**, monitoring ID sama, meta.replayed=true; tidak mengulang alarm/inference.
- Key sama tetapi payload berbeda: **409 OBSERVATION_IDENTITY_CONFLICT**; record lama tidak diubah.
- Receipt merujuk observasi yang telah dihapus: 409, bukan membuat ulang diam-diam.

Source API ditentukan dari registry: python_simulator→simulator, lainnya→api. Sheets menggunakan google_sheets. Klaim source dari body diabaikan. Source identifiers historis tidak ditulis ulang.

## Penanganan data existing

Tidak memasang unique index yang gagal karena duplikasi historis. Untuk timestamp event resolusi detik, satu record legacy yang benar-benar cocok dapat diadopsi oleh ledger tanpa mengubah record tersebut. Jika kandidat ganda atau payload berbeda, service menolak 409 dan meminta peninjauan. Stable event ID dan fractional timestamps yang tidak pernah tersimpan sebelumnya tidak diasumsikan identik dengan histori coarse timestamp.

Audit read-only sebelum migration menemukan **0 grup duplikat dengan identitas device yang dapat dipetakan**, **721 telemetry rows tanpa device_id**, dan **132 grup timestamp tanpa atribusi perangkat**. Grup terakhir hanyalah kandidat, bukan bukti duplikasi; bisa berasal dari event/perangkat berbeda. Data tersebut tetap utuh, tidak digabung/dihapus. Simulator lama mungkin tercatat sebagai api; tidak ada reclassification otomatis.

## Migration dan rollback

Migration additive sudah diterapkan, backup sebelum perubahan tersedia lokal pada phase16_2/evidence/pre-migration.sqlite (diabaikan Git). Up hanya membuat tabel ledger, tidak backfill atau menghapus monitoring/alarm/fuzzy/log. Down hanya menghapus tabel ledger; test membuktikan observasi tetap ada. Untuk rollback operasional hentikan ingress dan rencanakan pemulihan ledger/code bersama; hilangnya ledger dapat mengurangi kemampuan replay key lama. Jangan otomatis mengganti database aktif dengan backup setelah ada data baru.

## Expected dan actual tests

| Skenario | Actual |
| --- | --- |
| Request valid dan 10 retry identik | 1×201 + 9×200; monitoring ID sama; tepat 1 inference |
| 10 request concurrent | 10 proses HTTP kernel pada shared SQLite, barrier sebelum release; tepat 1 monitoring/inference/alarm |
| Sensor sama, timestamp/event berbeda | Semua observasi tersimpan |
| Subsecond berbeda dan timezone ekuivalen | Subsecond berbeda tersimpan; ekuivalen replay |
| Source/device berbeda | 3 observasi berbeda tidak digabung |
| Payload berbeda pada key sama | 409; data lama identik |
| Sheets row duplicate dan repeated sync | Import sekali; replay skipped |
| Legacy duplicate dan rollback | Tidak dihapus; conflict eksplisit dan schema rollback aman |
| Invalid/conflicting key | 422; tidak membuat receipt/record |

Bukti concurrency: [concurrent.json](phase16_2/evidence/concurrent.json). Pengujian ini benar-benar multi-process, bukan loop sequential bernama concurrent. MySQL dan distributed deployment masih NOT VERIFIED.
