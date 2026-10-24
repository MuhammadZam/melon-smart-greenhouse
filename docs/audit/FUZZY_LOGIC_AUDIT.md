# Fuzzy Logic Audit

Tanggal audit: **22 Oktober 2026 (Asia/Jakarta)**. Project: `D:\Compro`. Audit read-only terhadap kode aplikasi dan database utama; artefak audit disimpan di `docs/audit`.

## Hasil matematika

Mamdani memakai triangle/trapezoid, fuzzifikasi, AND minimum, OR maximum, implication minimum dan aggregation maximum. Defuzzifikasi discrete centroid step1 untuk universe0–100. Unit mathematical tests lulus; audit juga menjalankan oracle geometris independen yang tidak memanggil membership/defuzzifikasi aplikasi.

Input **temperature27, humidity70, soil70, pH6.5** memberi membership optimal1. R01 baik alpha1; R16 baik alpha0.9 tidak mengubah maximum alpha baik1. Kurva output baik naik linear65–80 lalu plateau80–100.

- Discrete numerator: 2412.666666666667.
- Discrete denominator: 28.
- Centroid: 86.16666666666667; pembulatan **86.17**, kategori **BAIK**.
- Engine aktual: 86.17, kategori BAIK; cocok oracle.
- Integral continuous independen: luas 27.5, momen 2362.5, centroid 85.9090909. Perbedaan terhadap hasil discrete 86.1666667 berasal dari sampling endpoint step 1, bukan kesalahan centroid.

Dokumentasi Phase 15.1/manual/rule base dibaca sebagai pembanding, tidak sebagai oracle. Referensi86.17 terverifikasi pada aturan sekarang, bukan pengesahan agronomi.

## Persistensi dan integrasi

FuzzyInferenceResult menyimpan rule_version, inference_source/status, input/membership/rule activation snapshots, conclusion dan recommendation. Monitoring web/API/Sheets memanggil pipeline; dashboard/history/report membaca hasil. Null sensor dan tidak ada rule aktif ditangani dengan status, bukan skor fiktif. Tidak ada dispatch relay dari fuzzy; semua kontrol actuator terpisah simulasi.

## Bug terkonfirmasi

1. **G02/P0**: !empty(externalPayload.score) menolak0. Probe input eksternal0/BURUK menghasilkan86.17/BAIK, sehingga preservasi eksternal tidak benar pada boundary penting.
2. **G05/P1**: nonaktif runtime hanya menahan source laravel_engine. api_ingest/web_form/google_sheets_sync tetap inferensi; probe disabled_api AVAILABLE.
3. Histori update memakai updateOrCreate pada monitoring/rule/source, sehingga revisi dengan versi sama menimpa snapshot. Tidak setara histori semua keputusan immutable.
4. API ingestion belum memetakan payload fuzzy eksternal dan relay dari firmware secara lengkap. Grafik recentFuzzyScores tidak dirender. Dashboard fuzzy mengabaikan date filter; laporan memakai inferred_at dan mengabaikan source/condition.

## PROVISIONAL dan konflik kebutuhan

config/fuzzy.php validation_status=PROVISIONAL_FOR_SOFTWARE_TESTING,16 aturan terkurasi. Membership dan rekomendasi belum disetujui Elektro/agronomi. SRS menyatakan fuzzy berada di ESP32 sedangkan implementasi menghitung analisis lokal; perlu keputusan scope tertulis. Tidak ada klaim kelayakan rekomendasi budidaya atau kontrol fisik.

Perbaikan yang direkomendasikan sesudah persetujuan: preservasi boundary0, policy toggle seluruh ingress, payload/provenance eksternal, revisi histori, lalu validasi rule agronomi bersama stakeholder.

## Verifikasi oracle tambahan

Oracle JavaScript independen menghitung ulang membership, min/max, bobot aturan, agregasi dan centroid untuk 12 vektor input: ideal, panas/kering, lembap/jenuh, pH asam/basa, overlap dan domain endpoints. **12 dari 12 skor cocok** engine. Definisi rule/config dipakai sebagai data, bukan expected value test existing. Bukti: evidence/fuzzy-oracle.mjs dan fuzzy-oracle.json. Ini verifikasi software, bukan validasi agronomi.
