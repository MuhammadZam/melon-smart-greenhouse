# Security Audit Report

Tanggal audit: **22 Oktober 2026 (Asia/Jakarta)**. Project: `D:\Compro`. Audit read-only terhadap kode aplikasi dan database utama; artefak audit disimpan di `docs/audit`.

## Kesimpulan

**Belum siap production.** Temuan eksploitasi fungsional dibatasi pada database terisolasi. Tidak ada token/secret dicetak dalam laporan atau probe output. Tidak melakukan serangan jaringan/perangkat pihak lain.

| Kontrol | Hasil | Evidence |
| --- | --- | --- |
| Auth web/session/password | PASS scoped | AuthController session regenerate/invalidate; User hashed cast; AuthenticationTest. |
| RBAC admin web | PASS scoped | Observer403 halaman pengaturan; fixture suite mutasi. |
| Guest API read | FAIL/P0 | Empat API GET200 tanpa token/session; routes/api.php tidak auth. |
| CSRF/XSS/SQLi | PARTIAL | Middleware web, Blade escaping dan binding Eloquent ada; tidak full penetration test. |
| Device token hash/revoke REST | PASS scoped | SHA256 token lookup, revoke/inactive403, invalid401. |
| Development token production | FAIL/P1 | Seed preset tanpa guard; probe201 dalam environment production. |
| Device revoke jalur Sheets | FAIL/P1 | Sync tidak memeriksa active/revoked registry sebelum import. |
| Rate limiting | PARTIAL | POST ingestion throttle60; login/GET API tidak memiliki limiter route. |
| Secret handling | PARTIAL | Credential service account tidak tersedia; preset dev masih di source; .env deployment access belum diuji. |
| HTTPS/debug/session production | NOT VERIFIED | Local HTTP dan APP_DEBUG=true; belum deployment TLS/reverse-proxy config. |
| Exports | PASS scoped | Guest redirect, kedua role read mendapat akses; CSV guard karakter awal diverifikasi suite. |

## Dependency audit

Composer audit --locked selesai dengan exit1 karena temuan, bukan gagal jaringan pada eksekusi ulang yang diberi akses. Ada **4 advisory records**, **3 GHSA unik** untuk laravel/framework; dua record merujuk GHSA CRLF yang sama sehingga tidak dihitung sebagai dua kerentanan unik.

| Package | Advisory | Severity database | Sumber |
| --- | --- | --- | --- |
| laravel/framework | Laravel Framework: CRLF injection in default email rule  | high | https://github.com/advisories/GHSA-5vg9-5847-vvmq |
| laravel/framework | Laravel Framework: Temporary Signed URL Path Confusion | medium | https://github.com/advisories/GHSA-crmm-hgp2-wgrp |
| laravel/framework | Laravel: XSS in Debug Page Information | low | https://github.com/advisories/GHSA-jh5r-qr3c-85q8 |

Npm audit: **0** advisory pada153 dependencies saat pemeriksaan. Ini bukan jaminan seluruh app aman. Applicability/prasyarat exploit tiap advisory belum diuji; presence dependency affected harus ditriase, bukan menyatakan semua dapat dieksploitasi di workflow sekarang.

Runtime Laravel 11.57.0 padaPHP 8.2.12. Support security Laravel 11 berakhir12 Maret 2026 menurut [release notes resmi](https://laravel.com/docs/11.x/releases), sehingga persyaratan Laravel 11 sekarang berkonflik dengan readiness deployment aman. Upgrade framework/PHP merupakan rekomendasi pekerjaan berikut, tidak dipasang dalam audit karena mengubah kode/dependency baseline.

## Integritas & concurrency

API duplicate event tersimpan dua kali; Sheets event dedup aplikasi tanpa unique source identifier; alarm lookup index bukan unique. Test lockForUpdate SQLite sequential tidak membuktikan isolasi MySQL nyata. Tidak mengklaim race exploit sudah direproduksi. Login gagal/data master belum mempunyai audit trail konsisten setara command actuator logs.

## Tindakan berurutan

Tutup akses read API, perbaiki boundary fuzzy0, rotasi/isolasi preset perangkat, limiter login dan pagination API, triage advisory/upgrade supported runtime; kemudian uji TLS, session cookie, XSS/CSRF/SQLi dan load secara terkontrol. Jangan publikasi sebelum verifikasi ulang.
