# Rancangan ulang Fuzzy Mamdani tiga input

Versi lokal: `v2.0-three-input-PROVISIONAL`. Status: **PROVISIONAL_FOR_SOFTWARE_TESTING**. Belum disahkan Tim Elektro atau pembimbing. Pengujian matematika memverifikasi implementasi, bukan ketepatan agronomis.

## Input dan membership

Hanya temperature, soil_moisture, dan water_ph. `humidity` tidak difuzzifikasi atau dimasukkan ke snapshot baru. Alias internal lama `ph` dapat dinormalisasi di batas inference. Pipeline menolak input kosong/di luar domain; data lama tanpa soil_moisture menghasilkan INSUFFICIENT_DATA, bukan pembacaan nol.

Angka di bawah dipertahankan dari konfigurasi provisional sebelumnya untuk pengujian software. Pemaknaan ulang pH menjadi **air** belum membuktikan kurva lama untuk nutrisi/media cocok untuk air. Tim Elektro harus mengesahkannya atau menggantinya dengan versi baru.

| Input | Domain | Himpunan | Bentuk dan titik |
|---|---|---|---|
| temperature | 10–50 | dingin | trapesium [10,10,20,24] |
| temperature | 10–50 | optimal | segitiga [22,27,32] |
| temperature | 10–50 | panas | trapesium [29,34,50,50] |
| soil_moisture | 0–100 | kering | trapesium [0,0,50,65] |
| soil_moisture | 0–100 | ideal | segitiga [58,70,82] |
| soil_moisture | 0–100 | jenuh | trapesium [75,85,100,100] |
| water_ph | 0–14 | asam | trapesium [0,0,5.5,6] |
| water_ph | 0–14 | optimal | segitiga [5.8,6.5,7.2] |
| water_ph | 0–14 | basa | trapesium [6.8,7.5,14,14] |

## Audit rule base

Aturan lama tidak digunakan sebagai paket empat input. R03, R04, R07, R08, R09, R14, R15, dan R16 dihapus dari rule base aktif karena bergantung pada humidity. R01 didesain ulang untuk konjungsi **ketiga** input; penghapusan R16 juga mencegah skor baik hanya dari kondisi udara. Aturan yang independen dari humidity ditinjau dan diberi ID/versi baru. Semua kesimpulan dan rekomendasi kini menandai PROVISIONAL serta meminta verifikasi sensor/konsultasi tim; tidak menyuruh menyalakan fan, menambahkan bahan pengatur pH, atau mengaktifkan pompa fisik.

| ID baru | Antecedent AND | Output |
|---|---|---|
| T3-R01 | temperature optimal, soil ideal, water_ph optimal | baik |
| T3-R02 | temperature panas, soil kering | buruk |
| T3-R05 | water_ph asam, soil kering | buruk |
| T3-R06 | water_ph basa, soil jenuh | buruk |
| T3-R10 | temperature panas, soil ideal | perlu_perhatian |
| T3-R11 | temperature dingin, soil ideal | perlu_perhatian |
| T3-R12 | water_ph asam, temperature optimal | perlu_perhatian |
| T3-R13 | water_ph basa, temperature optimal | perlu_perhatian |

Semua bobot tetap 1. Tidak ada aturan agronomis tambahan untuk mengisi celah secara otomatis. Matriks 27 kombinasi representatif pada suhu [18,27,36], tanah [30,70,90], pH [4.5,6.5,8.5] menghasilkan 19 AVAILABLE dan 8 INSUFFICIENT_RULE_COVERAGE. Ini bukan bukti seluruh domain kontinu telah tercakup. Daftar tepat ada di [evidence matriks](revision/evidence/fuzzy-27-combinations.json).

## Inference dan defuzzification

AND=min, OR=max, implikasi=min, agregasi=max, centroid diskret z=0…100 dengan langkah 1 tetap digunakan. Tidak ada aturan aktif/luas agregasi nol menghasilkan skor NULL dan INSUFFICIENT_RULE_COVERAGE. Tidak ada fallback skor sehat.

Output lama dipertahankan: buruk trapesium [0,0,25,45], perlu_perhatian segitiga [35,55,75], baik trapesium [65,80,100,100]. Kategori skor adalah BURUK di bawah 40; PERLU_PERHATIAN mulai 40 sampai di bawah 65; BAIK mulai 65 sampai 100. Angka ini belum tervalidasi untuk tiga input. Label rentang PDF diselaraskan dengan resolver; bentuk output membership tidak sama dengan batas kategori crisp.

Verifikasi jumlah diskret independen:

| Input (suhu,tanah,pH) | Pembilang | Penyebut | Skor |
|---|---|---|---|
| (27,70,6.5) | 2412.6666667 | 28 | 86.17 |
| (36,30,6.5) | 629 | 35.5 | 17.72 |
| (35,70,6.5) | 1100 | 20 | 55.00 |

Tes juga memastikan humidity yang berubah/null tidak mengubah inferensi tiga input dan kombinasi dingin–tanah jenuh–pH optimal dilaporkan tidak tercakup.

## Riwayat, provenance, dan validasi

Snapshot lama tidak dimigrasikan. Hasil eksternal termasuk nol dipertahankan dengan sumber/versi external-unverified; tidak diberi cap model tiga input lokal. Dashboard menampilkan versi lokal baru atau eksternal yang jelas belum tervalidasi; rekomendasi historis/eksternal tidak ditampilkan sebagai instruksi operasional saat ini. CSV menyertakan versi dan status validasi fuzzy.

Sebelum dipakai untuk keputusan budidaya, Tim Elektro/pembimbing perlu menyetujui kalibrasi, domain/kurva masing-masing sensor, delapan aturan, delapan kombinasi representatif yang belum tercakup, bobot, output, resolusi centroid, batas kategori, ambang stale, dan redaksi rekomendasi. Tidak ada kontrol pompa otomatis yang ditambahkan.
