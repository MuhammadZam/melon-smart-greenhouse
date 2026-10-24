# LAPORAN FINALISASI DESAIN SISTEM & UI/UX (PHASE 16)
## SISTEM WEB MONITORING TANAMAN MELON

**Tanggal:** 17 Oktober 2026  
**Status Evaluasi:** ✅ **TERVERIFIKASI & MEMENUHI STANDAR AKADEMIK**  
**Role:** UI/UX Engineer, Front-End Architect, dan Accessibility Specialist  

---

## 1. TUJUAN & PRINSIP DESAIN

Finalisasi antarmuka pengguna pada Phase 16 bertujuan untuk menghadirkan pengalaman visual yang:
- **Clean & Minimalis:** Menghilangkan elemen visual berlebihan, gradien mencolok, animasi yang mengalihkan perhatian, dan bayangan (*drop shadow*) berat.
- **Profesional & Akademik:** Mengutamakan keterbacaan data numerik sensor, ketegasan status operasional, serta hierarki informasi yang teratur.
- **Responsif Sepenuhnya:** Memberikan kenyamanan navigasi di seluruh perangkat (smartphone 360–428px, tablet 768–1024px, hingga layar monitor desktop 1080p/2K).
- **Konsisten:** Menerapkan token desain yang terstandarisasi di semua modul (Dashboard, Manajemen Tanaman, Monitoring, Alarm, Simulasi Aktuator, Integrasi Google Sheets, Analisis Fuzzy, dan Laporan Monitoring).

---

## 2. SISTEM TOKEN DESAIN & PALET WARNA

### A. Palet Warna (Tailwind CSS 4):
- **Background Utama:** Dark Slate / Deep Navy (`#090d16` / `bg-slate-950`).
- **Surface / Card Background:** Slate 900 dengan transparansi halus (`bg-slate-900/80` hingga `bg-slate-900/90`).
- **Garis Batas (Borders):** Slate 800 semi-transparan (`border-slate-800/80`), menciptakan pemisahan antar-elemen yang kontras namun tidak tajam di mata.
- **Warna Aksen Fungsional:**
  - *Emerald Green* (`#10b981` / `emerald-400`): Digunakan secara selektif untuk indikasi status normal, data valid, tombol aksi utama, dan identitas botani.
  - *Sky Blue* (`#0284c7` / `sky-400`): Digunakan untuk telemetri lingkungan, kelembapan, dan integrasi cloud.
  - *Amber / Yellow* (`#f59e0b` / `amber-400`): Digunakan untuk peringatan ambang batas (*warning*), parameter mendekati batas toleransi, atau status `PENDING`.
  - *Rose / Red* (`#f43f5e` / `rose-400`): Digunakan khusus untuk alarm kritis aktif (*danger*), anomali tanaman, dan tombol destruktif dengan konfirmasi.
  - *Slate / Zinc* (`#94a3b8` / `slate-400`): Digunakan untuk teks label sekunder, satuan unit, dan metadata waktu.

### B. Tipografi & Skala Teks:
- **Font Utama:** System Sans-Serif Modern (`Inter`, `-apple-system`, `BlinkMacSystemFont`, `Segoe UI`, `Roboto`).
- **Font Numerik & Telemetri:** Monospace font (`ui-monospace`, `SFMono-Regular`, `Menlo`, `Monaco`, `Consolas`) untuk angka sensor, timestamp, dan skor fuzzy guna mempermudah perbandingan digit sejajar.
- **Hierarki Heading:**
  - `H1` (24–28px, font-bold, tracking-tight): Judul modul utama per halaman.
  - `H2` / `H3` (14–18px, font-semibold): Judul section dashboard dan kartu laporan.
  - Body Text (13–14px, font-normal, text-slate-300): Deskripsi dan isi tabel.
  - Microcopy / Unit (10–11px, uppercase, tracking-wider, text-slate-400): Label parameter dan satuan (°C, %, pH, mS/cm, lux).

---

## 3. SELARAS DENGAN REFERENSI DESAIN SMART GREENHOUSE

Bagian **Section B — Sensor Overview** pada dashboard utama didesain khusus agar mencerminkan identitas antarmuka *Smart Greenhouse* modern:
- Tata letak grid 8 kartu sensor disusun dalam 2 kolom pada mobile, 4 kolom pada tablet, dan 4-8 kartu responsif pada desktop.
- Mengadopsi tata letak kartu hitam pekat minimalis dengan ikon garis tipis (*thin stroke line icons*) di sebelah label parameter.
- Nilai telemetri sensor ditampilkan tebal, kontras tinggi, dan berukuran 24–28px dengan satuan (°C, %, pH) yang menyatu alami tanpa pembatas agresif.
- Timestamp pembaruan sensor ditampilkan jelas pada bagian footer kartu untuk menjamin keaslian data.

---

## 4. RESPONSIVE LAYOUT AUDIT & VIEWPORT OPTIMIZATION

| Elemen UI | Mobile (< 640px) | Tablet (640px - 1024px) | Desktop (> 1024px) |
|---|---|---|---|
| **Sidebar Navigasi** | Off-canvas drawer (tersembunyi secara default, dibuka via burger menu, backdrop overlay) | Collapsible icon-mode atau side-drawer | Sidebar tetap (*fixed sidebar*) selebar 256px dengan status rute aktif |
| **Bilah Filter Laporan** | Vertikal (1 input per baris), touch target $\ge 44\text{px}$ | 2–3 kolom dengan tombol aksi di baris terpisah | Grid horizontal terpadu (5 parameter filter + tombol submit & reset dalam satu baris) |
| **Grid Kartu Sensor** | 2 kolom (mudah di-tap, tidak ada teks terpotong) | 4 kolom seimbang | 4 kolom $\times$ 2 baris proporsional |
| **Tabel Data Observasi** | Horizontal scroll dengan pembungkus `.overflow-x-auto` | Penuh dengan kolom sekunder yang teratur | Penuh tanpa horizontal bar, aksi baris sejajar kanan |
| **Grafik Chart.js** | Menyesuaikan lebar layar (height 220px, scrollable jika perlu) | Menyesuaikan lebar layar (height 260px) | Menyesuaikan lebar kontainer kartu (height 300px) |
| **Modal & Konfirmasi Dialog** | Lebar 92vw, padding 16px, tombol stack vertikal | Lebar 500px, padding 24px, tombol horizontal | Lebar 500–600px, posisi tengah layar presisi |

---

## 5. PENANGANAN STATE & INTERAKSI PENGGUNA

1. **Loading State:**
   - Seluruh form dan tombol ekspor dilengkapi dengan feedback visual saat permintaan sedang diproses.
   - Pemicu ekspor PDF dan CSV menampilkan transisi disabled state untuk mencegah *double-clicking*.
2. **Empty State:**
   - Jika filter laporan atau filter dashboard tidak menemukan rekaman, halaman menyajikan panel informatif dengan ikon netral, judul "Tidak Ada Data Ditemukan", serta rekomendasi: "Silakan ubah rentang tanggal atau reset filter untuk menampilkan rekaman observasi lainnya."
   - Menghindari halaman kosong tanpa petunjuk aksi.
3. **Error State:**
   - Validasi formulir (seperti tanggal akhir lebih awal dari tanggal mulai) menampilkan pesan kesalahan tepat di samping input terkait dengan border merah lembut (`border-rose-500/50`) dan teks `text-rose-400`.
4. **Touch Target Size:**
   - Seluruh tombol aksi, dropdown filter, dan navigasi pagination memiliki tinggi minimum 40–44px untuk menjamin kenyamanan pengoperasian pada layar sentuh.

---

## 6. KEPATUHAN AKSESIBILITAS (A11Y)

- **Rasio Kontras Warna:** Teks utama (`text-white` dan `text-slate-200`) pada background gelap (`bg-slate-900` / `bg-slate-950`) memiliki rasio kontras $\ge 7:1$, melampaui standar WCAG AA (4.5:1).
- **Fokus Keyboard:** Seluruh input form, link navigasi, dan tombol aksi memiliki state `:focus-visible` dengan outline ring berwarna emerald (`focus:ring-2 focus:ring-emerald-400 focus:outline-none`).
- **Semantic HTML:** Menggunakan elemen struktur HTML5 standar (`<main>`, `<nav>`, `<header>`, `<footer>`, `<section>`, `<aside>`, `<table`, `<thead>`, `<tbody>`).
- **Form Association:** Semua label form terikat ke input melalui atribut `for` dan `id` yang unik.

---

## 7. KESIMPULAN

Antarmuka Sistem Web Monitoring Tanaman Melon pada Phase 16 telah diselaraskan menjadi satu sistem desain yang utuh, elegan, profesional, dan siap secara visual maupun fungsional untuk dipresentasikan dalam evaluasi akademik dan demonstrasi operasional.
