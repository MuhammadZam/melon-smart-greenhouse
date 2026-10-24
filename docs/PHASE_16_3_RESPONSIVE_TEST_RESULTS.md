# Phase 16.3 — Responsive Test Results

Tanggal: **24 Oktober 2026 (Asia/Jakarta)**. Project existing: `D:\Compro`. Lingkup: pemulihan UI; tidak memulai Phase 16.4.

## PASS — 40 pemeriksaan

Microsoft Edge melalui Playwright; login admin pada salinan SQLite, bukan sesi/database utama. Viewport sesuai permintaan: 375×812, 768×1024, 1366×768, 1920×1080.

| Halaman | 375×812 | 768×1024 | 1366×768 | 1920×1080 |
| --- | --- | --- | --- | --- |
| login | PASS | PASS | PASS | PASS |
| dashboard | PASS | PASS | PASS | PASS |
| monitoring | PASS | PASS | PASS | PASS |
| plants | PASS | PASS | PASS | PASS |
| charts | PASS | PASS | PASS | PASS |
| alarms | PASS | PASS | PASS | PASS |
| fuzzy | PASS | PASS | PASS | PASS |
| actuators | PASS | PASS | PASS | PASS |
| sheets | PASS | PASS | PASS | PASS |
| reports | PASS | PASS | PASS | PASS |

Lebar dokumen sama dengan lebar viewport pada seluruh 40 kombinasi. Sembilan halaman terautentikasi memberi HTTP200 pada empat viewport; /charts adalah redirect existing ke /dashboard#charts, bukan halaman baru. Login memiliki screenshot guest terpisah pada setiap ukuran. Tidak ada JavaScript pageerror pada run akhir.

## Komponen dan interaksi

- Sidebar: desktop tetap 256px dengan offset shell, mobile drawer tertutup tidak dapat difokuskan; buka/tutup, Escape, overlay, focus return diuji 375 dan768. x-cloak mencegah kilatan menu saat loading.
- Navbar: judul menyusut, profil rinci disembunyikan pada smartphone, target tombol minimal44px; dropdown notifikasi berada di dalam viewport. Loading, error jaringan dan retry diverifikasi.
- Dashboard: A–G dipertahankan, delapan kartu sensor dan nilai unavailable tetap jujur. Ada13 slot canvas; pada dataset existing, sebagian sensor ekspansi tidak mempunyai nilai dalam jendela query dan menampilkan empty state. Tidak memaksa13 grafik berisi data.
- Grafik: resize375/768/1366/1920, tooltip observasi, toggle legenda, filter tanaman dan empty-state tanggal masa depan PASS. Backend pemilihan data tidak berubah.
- Tabel: scroll hanya dalam panel, focusable bila lebar tabel melebihi panel; ArrowRight dan pagination halaman2 diuji di smartphone. Kolom tidak dihilangkan untuk menyamarkan overflow.
- Form/filter: login required, native + server plant validation, asosiasi error, monitoring/plants/alarm/fuzzy/reports apply/reset PASS.
- Modal: confirmation simulasi terlihat dalam viewport, input fokus, Escape, batal, success feedback; kontrol tetap simulasi database.
- Export CSV/PDF dan Sheets mock sync memberi feedback; kontrak server tidak diubah.

[52 hasil interaksi](phase16_3/evidence/interactions.json), [40 metrik browser](phase16_3/evidence/browser-after.json), [screenshot before/after](phase16_3/visual-comparison.html).

## Aksesibilitas

Axe pada10 halaman utama + plants/create + monitorings/create di375×812: **0 detected WCAG violations** pada run akhir. Label, aria-expanded/controls, dialog semantics, focus-visible, reduced motion, scroll region dan alternatif canvas ditambahkan. Pemeriksaan incomplete tetap disimpan di accessibility.json; belum mengklaim screen-reader atau WCAG certification. Empat viewport adalah emulasi desktop, bukan perangkat fisik.
