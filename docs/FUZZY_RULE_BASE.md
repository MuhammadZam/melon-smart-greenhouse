# DOKUMENTASI MATRIKS ATURAN FUZZY (PHASE 15)
## SISTEM WEB MONITORING TANAMAN MELON

**Versi Dokumen:** 1.0  
**Tanggal:** 16 Oktober 2026  
**Status:** `PROVISIONAL_FOR_SOFTWARE_TESTING` (16 Aturan Terkurasi)  

---

> [!IMPORTANT]
> Aturan inferensi IF-THEN di bawah ini disusun sebagai basis pengujian fungsional perangkat lunak. Matriks ini **BUKAN** representasi 81 kombinasi otomatis yang belum divalidasi, melainkan sampel terkurasi untuk merepresentasikan skenario ideal, waspada, dan kritis. Matriks ini harus disahkan oleh Tim Teknik Elektro sebelum digunakan pada lingkungan produksi komersial.

---

## 1. TABEL LENGKAP 16 ATURAN INFERENSI PROVISIONAL

| ID | Nama Aturan | Anteseden (IF) | Operator | Konsekuen (THEN) | Bobot | Kesimpulan Agronomis |
| :--- | :--- | :--- | :---: | :--- | :---: | :--- |
| **R01** | Kondisi Sempurna Seimbang | Temp: Optimal<br>Hum: Optimal<br>Soil: Ideal<br>pH: Optimal | AND | **Baik** | 1.0 | Seluruh parameter iklim mikro dan media tumbuh melon berada dalam rentang ideal. |
| **R02** | Stres Panas & Kekeringan Ekstrem | Temp: Panas<br>Soil: Kering | AND | **Buruk** | 1.0 | Suhu lingkungan tinggi disertai kelembapan media yang kering memicu stres air akut pada melon. |
| **R03** | Panas & Lembap (Risiko Jamur) | Temp: Panas<br>Hum: Lembap | AND | **Perlu Perhatian** | 1.0 | Kombinasi suhu panas dan udara lembap menghambat transpirasi tanaman melon serta memicu risiko jamur/embun tepung. |
| **R04** | Dingin & Sangat Lembap | Temp: Dingin<br>Hum: Lembap | AND | **Perlu Perhatian** | 1.0 | Suhu dingin dan udara lembap memperlambat metabolisme serta penyerapan nutrisi tanaman. |
| **R05** | Media Asam & Kekeringan Tanah | pH: Asam<br>Soil: Kering | AND | **Buruk** | 1.0 | Kondisi media asam bersamaan dengan tanah kering berisiko merusak perakaran dan mengunci unsur hara makro. |
| **R06** | Media Basa & Media Jenuh Air | pH: Basa<br>Soil: Jenuh | AND | **Buruk** | 1.0 | Media terlalu jenuh air dan basa memicu asfiksia akar (kurang oksigen) serta defisiensi zat besi/mikro. |
| **R07** | Iklim Baik tapi Tanah Kering | Temp: Optimal<br>Hum: Optimal<br>Soil: Kering | AND | **Perlu Perhatian** | 1.0 | Suhu dan kelembapan udara optimal, namun kelembapan tanah rendah sehingga tanaman berisiko layu ringan. |
| **R08** | Iklim Baik tapi Tanah Jenuh | Temp: Optimal<br>Hum: Optimal<br>Soil: Jenuh | AND | **Perlu Perhatian** | 1.0 | Suhu dan kelembapan udara stabil, namun media tumbuh terlalu basah. |
| **R09** | Udara Kering pada Suhu Normal | Temp: Optimal<br>Hum: Kering | AND | **Perlu Perhatian** | 1.0 | Kelembapan udara rendah meningkatkan defisit tekanan uap air (VPD) dan mempercepat penguapan. |
| **R10** | Panas Moderat dengan Tanah Ideal | Temp: Panas<br>Soil: Ideal | AND | **Perlu Perhatian** | 1.0 | Suhu udara greenhouse menghangat di atas ambang optimal melon, meskipun ketersediaan air tanah masih mencukupi. |
| **R11** | Dingin Moderat dengan Tanah Ideal | Temp: Dingin<br>Soil: Ideal | AND | **Perlu Perhatian** | 1.0 | Suhu udara di bawah standar tumbuh aktif tanaman melon. |
| **R12** | Penyimpangan pH Asam Moderat | pH: Asam<br>Temp: Optimal | AND | **Perlu Perhatian** | 1.0 | Nilai pH larutan nutrisi berada di bawah batas nyaman penyerapan fosfat dan kalsium melon. |
| **R13** | Penyimpangan pH Basa Moderat | pH: Basa<br>Temp: Optimal | AND | **Perlu Perhatian** | 1.0 | Nilai pH larutan nutrisi terlalu basa, berpotensi menyebabkan pengendapan mikro nutrien. |
| **R14** | Kekeringan Komprehensif Ekstrem | Temp: Panas<br>Hum: Kering<br>Soil: Kering | AND | **Buruk** | 1.0 | Kondisi lingkungan kering menyeluruh berisiko tinggi mematikan pucuk tanaman dan menggugurkan bakal buah melon. |
| **R15** | Basah Dingin Komprehensif | Temp: Dingin<br>Hum: Lembap<br>Soil: Jenuh | AND | **Buruk** | 1.0 | Lingkungan dingin berkelembapan jenuh memicu pembusukan leher akar (damping off) dan layu bakteri. |
| **R16** | Optimal Suhu & Udara | Temp: Optimal<br>Hum: Optimal | AND | **Baik** | 0.9 | Kondisi mikroklimat udara greenhouse sangat nyaman untuk fotosintesis tanaman melon. |

---

## 2. PENANGANAN KASUS TANPA CAKUPAN ATURAN (INSUFFICIENT RULE COVERAGE)

Jika kombinasi nilai sensor input tidak memicu satupun aturan aktif di atas (seluruh derajat keanggotaan anteseden menghasilkan kekuatan penyalaan $\alpha_k = 0.0$):
- Sistem **TIDAK MENGARANG SKOR**.
- Output skor ditetapkan sebagai `null`.
- Kategori ditetapkan sebagai `null`.
- Status evaluasi dicatat sebagai `INSUFFICIENT_RULE_COVERAGE`.
- Kesimpulan menyarankan koordinasi dengan Tim Elektro untuk perumusan aturan baru.
