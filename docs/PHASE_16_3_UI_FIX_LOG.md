# Phase 16.3 — UI fix log

## Pre-implementation record — 24 October 2026

Required audit reports and Phase 16.2 implementation/remaining risks reviewed. Existing screenshots show missing theme, enormous icons and overflow. Tailwind 4 is installed while the CSS entry uses v3 directives. Existing backend security, ingestion, fuzzy logic and history must be preserved.

Planned application files, recorded before implementation:

- `resources/css/app.css`: restore Tailwind 4 theme/utilities, responsive containment, focus and motion preferences.
- `resources/views/layouts/app.blade.php`, `layouts/auth.blade.php`: responsive shell, semantic landmarks, mobile navigation state.
- `resources/views/layouts/partials/sidebar.blade.php`, `navbar.blade.php`: accessible mobile drawer and notification placement.
- Targeted existing page templates and `resources/js/app.js` only where browser evidence establishes a layout, chart or interaction defect.
- Browser verification scripts, screenshots and six Phase 16.3 reports.

MySQL prerequisite: Docker CLI installed, but daemon pipe absent on first probe. Docker Desktop startup attempted; compatibility remains BLOCKED until a running isolated MySQL test succeeds. No migration or test on the primary MySQL database is authorized by this workflow.

Before screenshots will be captured against a disposable SQLite copy before changing CSS. No new project or redesign. No API, fuzzy rule, alarm threshold, ingestion or hardware policy changes planned.

Evidence-driven scope additions (recorded before the related edits): local font asset and license under `resources/fonts` to remove repeated external font stalls; labels in fuzzy/actuator history, alarm filters and Sheets registration; chart canvas alternatives and form error associations. MySQL gate subsequently PASS on isolated MySQL 8.0.46 (12 migrations and 10 concurrent HTTP workers).


## Implementasi dan hasil akhir

| Masalah / root cause | Perbaikan | Bukti |
| --- | --- | --- |
| Entry Tailwind v3 pada compiler v4 | Import v4, source scan eksplisit dan preflight/theme | Build + 40 screenshot after |
| Ketergantungan font eksternal / loading lambat | Plus Jakarta Sans variable Latin WOFF2 lokal, OFL license; system fallback | Aset Vite font, screenshot |
| Drawer berkedip, tab fokus menuju menu tertutup | x-cloak, inert, focus scope, Escape/overlay, scroll lock dan focus return | Drawer 375/768 + interactions.json |
| Dropdown terpotong di smartphone, tidak ada feedback jaringan | Posisi fixed mobile dalam viewport; loading/error/retry dan ARIA state | Notifikasi 375/768; network abort/retry test |
| Child grid/canvas/tabel memaksa lebar | min-width:0, container scroll lokal dan chart max-width; tick chart lebih ringkas | 40/40 tanpa overflow; resize 4 viewport |
| Kontras teks kecil / tombol / pagination dark | Teks muted lebih terang, primary emerald lebih gelap, dark pagination konsisten | accessibility-initial.json → accessibility.json |
| Form tanpa label/error association | for/id atau aria-label, aria-invalid, aria-describedby, role alert | Validasi native dan server; axe |
| Alarm Semua Tanaman ditolak nullable integer | Nilai opsi template dari all menjadi kosong; backend tetap | Filter severity dan reset browser PASS |
| Modal belum memberi fokus setelah x-show | Fokus menunggu elemen terlihat, Tab terkurung, Escape/cancel dan return | 52 interaksi PASS + modal-check.json |
| Badge status / detail modal menyusut hingga huruf patah | Header kartu bertumpuk pada mobile; badge tidak shrink; detail grid proporsional | Screenshot aktuator dan dialog |
| Heading fuzzy menjadi dua kolom sempit | Aliran teks heading normal; alternatif teks chart | Screenshot mobile fuzzy |
| Klaim visual Live IoT Ready / SSL certified tanpa bukti | Label netral Monitoring IoT / Akses akun terdaftar | Tidak mengubah autentikasi atau status integrasi |

### File aktual

- `resources/css/app.css`
- `resources/js/app.js`
- `resources/views/actuators/index.blade.php`
- `resources/views/alarms/index.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/dashboard/index.blade.php`
- `resources/views/fuzzy/history.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/auth.blade.php`
- `resources/views/monitorings/create.blade.php`
- `resources/views/monitorings/edit.blade.php`
- `resources/views/plants/create.blade.php`
- `resources/views/plants/edit.blade.php`
- `resources/views/admin/integrations/google_sheets.blade.php`
- `resources/views/layouts/partials/navbar.blade.php`
- `resources/views/layouts/partials/sidebar.blade.php`
- `resources/fonts/plus-jakarta-sans-latin-wght-normal.woff2`
- `resources/fonts/PLUS_JAKARTA_SANS_LICENSE.txt`

Build akhir PASS, regresi 297/2.075, MySQL gate PASS. Semua perubahan aplikasi hanya di resources. Beberapa kegagalan harness selama iterasi (tab background, selector pagination tersembunyi, re-run aktuator sudah ON, assert MySQL memakai kolom salah/tipe factory sebelum reload) diperbaiki sebelum hasil akhir. Tidak menganggap error harness sebagai bukti backend rusak; hasil akhir berasal dari rerun nyata.
