# Phase 16.2 — API Security Fix

Project: **Sistem Web Monitoring Tanaman Melon** — `D:\Compro`.
Tanggal penyelesaian: **24 Oktober 2026 (Asia/Jakarta)**. Pekerjaan dimulai 22 Oktober dan dilanjutkan setelah interupsi.

## Root cause dan reproduksi sebelum perbaikan

GET /api/plants, /api/monitorings, /api/monitorings/statistics dan /api/dashboard/summary mengembalikan 200 beserta data kepada guest pada probe audit. routes/api.php tidak memiliki guard baca. Device middleware sebelumnya juga mengizinkan ingestion tanpa token pada local/testing.

## Implementasi

- AuthenticateInternalApi memakai guard web setelah middleware web menguraikan encrypted session cookie. Admin dan role user/pengamat dapat membaca; role lain ditolak 403. Guest/expired cookie ditolak JSON 401.
- Token perangkat hanya memberi izin ingestion. Token terdaftar tanpa sesi pengguna memperoleh 403 pada read API, termasuk notification endpoint. Token invalid tanpa sesi tidak menjadi autentikasi pengguna.
- POST /api/monitorings tetap stateless dengan device.auth dan throttle 60/menit. X-Device-Token atau Bearer token wajib; session admin tidak menggantikan token. Revoked/inactive ditolak 403. Device_id dan source ditetapkan dari registry, bukan klaim body.
- Middleware internal dan role ditempatkan sebelum SubstituteBindings: URL ID yang tidak ada tidak mengungkap 404 kepada pengguna tanpa hak.
- Existing RBAC pada controller/policy tetap berlaku. Form monitoring manual existing tidak diubah kewenangannya dalam fase ini.

Affected files: routes/api.php, routes/web.php, bootstrap/app.php, AuthenticateInternalApi.php, AuthenticateDeviceToken.php, config/services.php dan API controller. API client tests diperbarui untuk autentikasi eksplisit; tidak menggunakan bypass middleware sebagai jalan agar test lulus.

## Klasifikasi semua endpoint aktual

Kategori mengikuti middleware **dan** pembatasan controller/FormRequest; plant mutations dan monitoring edit/update/delete admin-only walaupun middleware route hanya auth. Public login POST tetap dilindungi CSRF web.

| Method | URI | Kategori | Enforcement |
| --- | --- | --- | --- |
| GET / HEAD | // | PUBLIC | web |
| POST | /_ignition/execute-solution | PUBLIC | Spatie\LaravelIgnition\Http\Middleware\RunnableSolutionsEnabled |
| GET / HEAD | /_ignition/health-check | PUBLIC | Spatie\LaravelIgnition\Http\Middleware\RunnableSolutionsEnabled |
| POST | /_ignition/update-config | PUBLIC | Spatie\LaravelIgnition\Http\Middleware\RunnableSolutionsEnabled |
| GET / HEAD | /actuators | AUTHENTICATED USER | web, auth |
| GET / HEAD | /actuators/history | AUTHENTICATED USER | web, auth |
| POST | /actuators/{actuator}/control | ADMIN ONLY | web, auth, role:admin |
| GET / HEAD | /admin/fuzzy/config | ADMIN ONLY | web, auth, role:admin |
| POST | /admin/fuzzy/toggle | ADMIN ONLY | web, auth, role:admin |
| GET / HEAD | /admin/integrations/google-sheets | ADMIN ONLY | web, auth, role:admin |
| POST | /admin/integrations/google-sheets/devices | ADMIN ONLY | web, auth, role:admin |
| POST | /admin/integrations/google-sheets/devices/{device}/activate | ADMIN ONLY | web, auth, role:admin |
| POST | /admin/integrations/google-sheets/devices/{device}/revoke | ADMIN ONLY | web, auth, role:admin |
| POST | /admin/integrations/google-sheets/sync | ADMIN ONLY | web, auth, role:admin |
| GET / HEAD | /admin/management-check | ADMIN ONLY | web, auth, role:admin |
| GET / HEAD | /admin/parameters | ADMIN ONLY | web, auth, role:admin |
| GET / HEAD | /admin/thresholds | ADMIN ONLY | web, auth, role:admin |
| PATCH | /admin/thresholds/{parameter} | ADMIN ONLY | web, auth, role:admin |
| GET / HEAD | /admin/users | ADMIN ONLY | web, auth, role:admin |
| GET / HEAD | /alarms | AUTHENTICATED USER | web, auth |
| GET / HEAD | /alarms/{alarm} | AUTHENTICATED USER | web, auth |
| GET / HEAD | /api/dashboard/summary | AUTHENTICATED USER | api, web, App\Http\Middleware\AuthenticateInternalApi |
| GET / HEAD | /api/monitorings | AUTHENTICATED USER | api, web, App\Http\Middleware\AuthenticateInternalApi |
| POST | /api/monitorings | AUTHORIZED DEVICE INGESTION | api, device.auth, throttle:60,1 |
| GET / HEAD | /api/monitorings/statistics | AUTHENTICATED USER | api, web, App\Http\Middleware\AuthenticateInternalApi |
| GET / HEAD | /api/monitorings/{monitoring} | AUTHENTICATED USER | api, web, App\Http\Middleware\AuthenticateInternalApi |
| GET / HEAD | /api/notifications/status | AUTHENTICATED USER | web, App\Http\Middleware\AuthenticateInternalApi |
| GET / HEAD | /api/plants | AUTHENTICATED USER | api, web, App\Http\Middleware\AuthenticateInternalApi |
| GET / HEAD | /api/plants/{plant} | AUTHENTICATED USER | api, web, App\Http\Middleware\AuthenticateInternalApi |
| GET / HEAD | /charts | AUTHENTICATED USER | web, auth |
| GET / HEAD | /dashboard | AUTHENTICATED USER | web, auth |
| GET / HEAD | /fuzzy/history | AUTHENTICATED USER | web, auth |
| GET / HEAD | /fuzzy/history/{fuzzyInference} | AUTHENTICATED USER | web, auth |
| GET / HEAD | /history | AUTHENTICATED USER | web, auth |
| GET / HEAD | /login | PUBLIC | web, guest |
| POST | /login | PUBLIC | web, guest |
| POST | /logout | AUTHENTICATED USER | web, auth |
| GET / HEAD | /monitorings | AUTHENTICATED USER | web, auth |
| POST | /monitorings | AUTHENTICATED USER | web, auth |
| GET / HEAD | /monitorings/create | AUTHENTICATED USER | web, auth |
| GET / HEAD | /monitorings/{monitoring} | AUTHENTICATED USER | web, auth |
| PUT / PATCH | /monitorings/{monitoring} | ADMIN ONLY | web, auth |
| DELETE | /monitorings/{monitoring} | ADMIN ONLY | web, auth |
| GET / HEAD | /monitorings/{monitoring}/edit | ADMIN ONLY | web, auth |
| GET / HEAD | /plants | AUTHENTICATED USER | web, auth |
| POST | /plants | ADMIN ONLY | web, auth |
| GET / HEAD | /plants/create | ADMIN ONLY | web, auth |
| GET / HEAD | /plants/{plant} | AUTHENTICATED USER | web, auth |
| PUT / PATCH | /plants/{plant} | ADMIN ONLY | web, auth |
| DELETE | /plants/{plant} | ADMIN ONLY | web, auth |
| GET / HEAD | /plants/{plant}/edit | ADMIN ONLY | web, auth |
| GET / HEAD | /reports/monitoring | AUTHENTICATED USER | web, auth |
| GET / HEAD | /reports/monitoring/export/csv | AUTHENTICATED USER | web, auth |
| GET / HEAD | /reports/monitoring/export/pdf | AUTHENTICATED USER | web, auth |
| GET / HEAD | /up | PUBLIC |  |

## Kontrak respons

| Kondisi | Expected / Actual |
| --- | --- |
| Guest read privat | 401 / 401 |
| Cookie sesi kedaluwarsa | 401 / 401 |
| Cookie terenkripsi yang valid tanpa actingAs | 200 / 200; body data diverifikasi |
| Pengamat ke endpoint admin, termasuk mutation | 403 / 403 |
| Admin ke endpoint diizinkan | 200 / 200 |
| Device valid ingestion | 201 / 201 |
| Tanpa token / token invalid | 401 / 401 |
| Token revoked / inactive | 403 / 403 |
| Device mencoba read internal | 403 / 403 |
| Body mencoba spoof device/source | Identitas registry tetap dipakai |

## Tests dan bukti

Phase16_2ApiSecurityTest mencakup tujuh private reads, positive role access, device read denial, unknown role, ordering binding, admin mutations, device token states serta expired dan valid encrypted database session cookies. Cookie JSON test memakai withCredentials agar cookie benar-benar dikirim. Existing AuthenticationTest/RBAC/device tests juga lulus dalam regresi. Detail case ada pada [hasil regresi](PHASE_16_2_REGRESSION_TEST_RESULTS.md).

## Compatibility plan

Client baca anonymous harus bermigrasi ke sesi web terautentikasi. Firmware/simulator tidak memakai sesi: kirim token aktif dari registry pada setiap POST. Hapus ketergantungan terhadap X-Device-Auth-Warning dan ENFORCE_DEVICE_AUTH=false; flag tersebut tidak dapat membuka akses. Python simulator tidak memerlukan endpoint GET untuk menemukan plant_id; operator mengonfigurasikannya. Tidak ada pembukaan akses read kepada seluruh device untuk mempertahankan perilaku insecure lama.

## Remaining risk

Preset development yang sudah tersimpan belum dirotasi. TLS, production cookies, limiter login/GET dan advisori dependency belum ditutup. Tidak ada klaim penetration-test PASS atau live ESP32 PASS.

## Catatan kategori public development

Tujuh route dalam kategori PUBLIC mencakup homepage, dua route login, health endpoint, serta tiga route _ignition yang terdaftar oleh package pada runtime development. Route Ignition memakai guard/config package; kategori ini tidak berarti endpoint tersebut diizinkan terbuka pada production. APP_DEBUG dan konfigurasi deployment tetap risiko tersisa. Tidak mengubah dependency/konfigurasi debug package dalam scope tiga bug inti ini.
