# ARSITEKTUR MESIN INFERENSI FUZZY LOGIC (PHASE 15)
## SISTEM WEB MONITORING TANAMAN MELON

**Versi Dokumen:** 1.0  
**Tanggal:** 16 Oktober 2026  
**Status Implementasi:** Provisional for Software Testing (`PROVISIONAL_FOR_SOFTWARE_TESTING`)  
**Metode:** Mamdani Fuzzy Inference System (Discrete Centroid Defuzzification)  

---

## 1. DESAIN SISTEM & TUJUAN ARSITEKTUR

Tujuan utama mesin inferensi logika fuzzy adalah memberikan analisis kondisi lingkungan (mikroklimat dan media tanam) tanaman melon secara holistik untuk **Decision Support System (DSS)** budidaya, bukan mendiagnosis patologi/kesehatan tanaman secara medis/agronomis.

Sistem ini **BUKAN** Large Language Model (LLM), deep learning, model generatif, atau black-box cloud AI, melainkan sistem pakar berbasis aturan (*rule-based expert system*) deterministik dan matematis.

```
+---------------------------------------------------------------------------------------+
|                                ALUR INGESTION DATA                                    |
|   Web Form (Admin/Pengamat)  |  REST API  |  Google Sheets Sync  |  Python Simulator  |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
                        +-------------------------------------+
                        |          Model Monitoring           |
                        |      (Penyimpanan Data Dasar)       |
                        +-------------------------------------+
                                           |
                   +-----------------------+-----------------------+
                   |                                               |
                   v                                               v
    +------------------------------+               +-------------------------------+
    |    MonitoringAlarmService    |               |      FuzzyPipelineService     |
    |  - Threshold-based Biner     |               |  - Multi-parameter evaluation |
    |  - Hanya 4 Parameter         |               |  - Failure-isolated (Try/Catch|
    |  - Normal / Warning / Critical               |  - Idempotent storage         |
    +------------------------------+               +-------------------------------+
                                                                   |
                                    +------------------------------+
                                    |
                                    v
                     +-----------------------------+
                     |    FuzzyMembershipService   |
                     |  - Fuzzifikasi Input        |
                     |  - Triangular / Trapezoidal |
                     +-----------------------------+
                                    |
                                    v
                     +-----------------------------+
                     |  FuzzyRuleEvaluationService |
                     |  - Operator AND (Minimum)   |
                     |  - Operator OR (Maximum)    |
                     |  - Firing Strength (Alpha)  |
                     +-----------------------------+
                                    |
                                    v
                     +-----------------------------+
                     |   MamdaniInferenceService   |
                     |  - Implikasi (Min)          |
                     |  - Agregasi (Max)           |
                     |  - Defuzzifikasi (Centroid) |
                     +-----------------------------+
                                    |
                                    v
                     +-----------------------------+
                     |    FuzzyConclusionService   |
                     |  - Sintesis Kesimpulan      |
                     |  - Rekomendasi Budidaya     |
                     +-----------------------------+
                                    |
                                    v
                     +-----------------------------+
                     |     FuzzyInferenceResult    |
                     |  - Database MySQL & SQLite  |
                     |  - Snapshot Audit Trail     |
                     +-----------------------------+
```

---

## 2. MODULARITAS DAN TANGGUNG JAWAB KOMPONEN

Arsitektur Phase 15 memisahkan tugas secara tegas ke dalam lapisan modular:

| Komponen | Namespace / Path | Tanggung Jawab Utama |
| :--- | :--- | :--- |
| **Membership Contract** | `App\Services\FuzzyLogic\Contracts\MembershipFunctionInterface` | Kontrak perhitungan $\mu(x) \in [0, 1]$ dan inspeksi kurva |
| **Triangular Membership** | `App\Services\FuzzyLogic\Membership\TriangularMembershipFunction` | Implementasi kurva segitiga $[a, b, c]$ |
| **Trapezoidal Membership** | `App\Services\FuzzyLogic\Membership\TrapezoidalMembershipFunction` | Implementasi kurva trapesium $[a, b, c, d]$ (mendukung left/right shoulder) |
| **Membership Service** | `App\Services\FuzzyLogic\FuzzyMembershipService` | Fuzzifikasi crisp input dan penyediaan himpunan output |
| **Rule Evaluation Service** | `App\Services\FuzzyLogic\FuzzyRuleEvaluationService` | Evaluasi aturan, kalkulasi firing strength $\alpha$ menggunakan AND min / OR max |
| **Mamdani Inference Service**| `App\Services\FuzzyLogic\MamdaniInferenceService` | Implikasi min, agregasi max, defuzzifikasi Centroid COG pada semesta $z \in [0, 100]$ |
| **Conclusion Service** | `App\Services\FuzzyLogic\FuzzyConclusionService` | Perumusan kesimpulan bahasa manusia dan rekomendasi agronomis melon |
| **Pipeline Service** | `App\Services\FuzzyLogic\FuzzyPipelineService` | Validasi input, isolasi kegagalan, preservasi data eksternal, dan penyimpanan persisten |
| **Result DTO** | `App\DTO\FuzzyInferenceResult` | Struktur transfer objek untuk pertukaran data hasil inferensi |
| **Eloquent Model** | `App\Models\FuzzyInferenceResult` | Pemodelan data hasil inferensi, relasi ke `Monitoring` dan `Plant` |

---

## 3. SEPARASI ARSITEKTURAL KETAT (ALARM VS FUZZY VS AKTUATOR)

1. **Sistem Alarm:**
   - Ambang batas (*crisp thresholds*) independen per parameter.
   - Tetap dibatasi secara ketat hanya pada 4 parameter: `temperature`, `humidity`, `soil_moisture`, `ph`.
   - Menghasilkan status `NORMAL`, `WARNING`, `CRITICAL`.
2. **Sistem Fuzzy Logic:**
   - Mengevaluasi kombinasi ke-4 parameter secara simultan menggunakan logika samar (*fuzzy multi-valued*).
   - Menghasilkan skor indeks (0–100), kategori (`BAIK`, `PERLU_PERHATIAN`, `BURUK`), kesimpulan, dan rekomendasi.
   - **TIDAK** memicu alarm baru dan **TIDAK** mengubah threshold alarm.
3. **Sistem Kontrol Aktuator:**
   - Simulasi kontrol pompa fertigasi dan kipas exhaust.
   - Beroperasi berdasarkan perintah manual Administrator atau jadwal.
   - **TIDAK** digerakkan otomatis oleh skor fuzzy pada Phase 15.
   - **TIDAK** terhubung ke relay fisik greenhouse.

---

## 4. PENANGANAN INTEGRITAS DATA & KEGAGALAN

- **Isolasi Kegagalan (*Failure Isolation*):** Seluruh proses inferensi dibungkus blok `try/catch`. Kegagalan pada mesin inferensi tidak akan membatalkan penyimpanan record `Monitoring`.
- **Status `INSUFFICIENT_DATA`:** Jika salah satu parameter dari 4 parameter input bernilai `null` atau di luar rentang fisika wajar, status ditetapkan `INSUFFICIENT_DATA` dan skor tetap `null` (tidak mengarang skor).
- **Status `INSUFFICIENT_RULE_COVERAGE`:** Jika input tidak memicu satupun aturan aktif pada rule base ($\sum \mu(z) = 0$), status ditetapkan `INSUFFICIENT_RULE_COVERAGE` dan skor tetap `null`.
- **Status `STALE`:** Jika observasi telah berumur lebih dari 24 jam (`stale_threshold_hours`), status dicatat `STALE`.
- **Idempotensi & Versioning:** Kombinasi `monitoring_id`, `rule_version`, dan `inference_source` bersifat unik. Perubahan versi rule memungkinkan evaluasi ulang tanpa menimpa riwayat versi sebelumnya.
- **Preservasi Hasil Eksternal:** Data inferensi yang dikirim langsung dari Google Sheets atau ESP32 disimpan dengan `inference_source = 'google_sheets_payload'` atau `'external_esp32'` dan tidak ditimpa oleh kalkulasi lokal Laravel.
