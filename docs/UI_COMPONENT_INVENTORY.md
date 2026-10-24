# UI component inventory — flat refinement

## Direction and scope recorded before implementation

White canvas #FFFFFF, secondary surface #F8F9FA, ink #171717, muted text #666666, border #E5E7EB, primary #176B50, error #B42318. Retain the local Plus Jakarta Sans font and all existing workflows. Cards2px, buttons/inputs/dialogs4px, tables0px. Color indicates actions or real status, never sensor identity.

Planned presentation-only changes: all web Blade templates (including public home, auth, dashboard, monitoring/plants CRUD, alarms/thresholds, fuzzy/history/config, actuators/history, Sheets and reports), shared components/layouts, app.css and app.js. Preserve the print PDF template and all backend/config/schema/contracts. Capture before/after in docs/ui_flat using a disposable SQLite copy.

Implementation inventory and verification results follow after browser checks.


## Inventory hasil implementasi

| Komponen | Aturan akhir | Lokasi utama |
| --- | --- | --- |
| Canvas / surface | White /#F8F9FA; ink#171717,muted#666666,line#E5E7EB | flat.css |
| Primary action | #176B50 → hover#12543F,pressed.98,focus2px | ui-button-primary,bg-accent |
| Secondary action | Surface netral → hover#F0F2F1; edit bukan warning | Template+flat.css |
| Error / warning | #B42318 /dark amber,hanya status/feedback bermakna | Alert,alarm,fuzzy,simulation states |
| Button / input | Radius4px,min-height44px; input14px | flat.css |
| Card / section | Radius2px; section divider mengganti outer decorative card | Dashboard+summary-card |
| Table | Radius0,border horizontal,hover halus,kolom numerik rata kanan | Semua tabel web+app.js |
| Sensor value | Angka24px,unit14px,timestamp12px,N/A untuk null | sensor-reading.blade.php |
| Typography | Font lokal tunggal; title24–28,section16–18,body14,support12,metric24–32 | flat.css |
| Sidebar / navbar | White,border separator,active soft green; mobile inert+focus trap+overlay | Shared partials |
| Dropdown / modal | Radius4px,transisi150ms,focus/escape/return dipertahankan | Navbar,aktuator |
| Chart | White,green primary,gray boundaries,12px axes,150ms/reduced-motion | flatMonitoringTheme plugin |
| Native POST loading | Button disabled+aria-busy+Memproses...,restored on pageshow | app.js |
| Destructive action | Native confirmation; locked delete explains retention constraint | Plants/monitoring/Sheets templates |

Primary/secondary/danger controls diperiksa berdasarkan fungsi; role authorization dan validasi tetap milik backend existing. Color map status-badge tetap mengikuti kategori existing. Tidak mengganti satu sensor dengan warna khusus. Semua27 template web existing diselaraskan; PDF print template tidak berubah. Komponen baru sensor-reading menjaga format precision existing dan tidak menganggap0 sebagai null.

Referensi verifikasi: [hasil68 checks](UI_INTERACTION_TEST_RESULTS.md) dan [matrix88 tampilan](UI_RESPONSIVE_VERIFICATION.md).

