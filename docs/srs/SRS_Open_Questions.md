# Daftar Pertanyaan Terbuka & Batasan Kolaborasi Lintas Disiplin
**Sistem Web Monitoring Tanaman Melon pada Greenhouse**
*Dokumen Penyelarasan Kebutuhan Tripartit: Informatika, Teknik Elektro, dan Mitra Industri/Agronomi*

---

## 1. Matriks Batasan & Pembagian Tanggung Jawab Lintas Disiplin

Untuk menghindari tumpang tindih wewenang dan memastikan akuntabilitas implementasi sistem cerdas pertanian presisi (*Smart Precision Agriculture*), pembagian tanggung jawab ditetapkan sebagai berikut:

| Entitas Kolaborator | Lingkup & Tanggung Jawab Resmi | Batasan Sistem (Out of Scope) | Status Kesiapan Saat Ini |
| :--- | :--- | :--- | :--- |
| **Program Studi S-1 Informatika**<br>*(Tim Pengembang Web & Server)* | • Pengembangan platform web berbasis Laravel 11 & PHP 8.2.<br>• Perancangan basis data relasional MySQL & Eloquent ORM.<br>• Pembuatan antarmuka visualisasi dashboard interaktif (Chart.js).<br>• Penyediaan REST API Ingestion (`POST /api/monitorings`).<br>• Manajemen master data tanaman melon (CRUD).<br>• Layanan siklus hidup alarm otomatis (`MonitoringAlarmService`).<br>• Pengaturan batas ambang parameter (`/admin/thresholds`).<br>• Pembangunan simulator data IoT telemetri berbasis Python.<br>• Pengujian otomatis unit & fitur (119 tests, 5.935 assertions). | • Tidak merancang perangkat keras (board mikrokontroler).<br>• Tidak menulis firmware C++/Arduino pada ESP32.<br>• Tidak melakukan perakitan fisik relay dan pengkabelan aktuator.<br>• Tidak melakukan kalibrasi sensor analog di greenhouse.<br>• Tidak mengontrol tegangan fisik listrik 12V/220V secara langsung. | **SIAP & TERVERIFIKASI 100%**<br>(Baseline Software Inti Selesai) |
| **Program Studi S-1 Teknik Elektro**<br>*(Tim Perangkat Keras & Otomasi)* | • Pengadaan dan perakitan board mikrokontroler ESP32.<br>• Pemilihan, pengkabelan, dan proteksi optocoupler modul relay.<br>• Integrasi 8 modul sensor fisik presisi (DHT22/SHT31, Capacitive Soil Moisture, DS18B20, Ultrasonik HC-SR04P, BH1750, pH E-201-C, EC DFRobot).<br>• Kalibrasi sensor fisik menggunakan larutan buffer standar.<br>• Penulisan firmware C++/Arduino dengan RTOS/Timer sampling.<br>• Eksekusi logika otomasi lokal tertutup (*closed-loop control*) di greenhouse.<br>• Pengendalian 5 aktuator fisik (Pompa Siram, Pompa Dosing, Kipas Exhaust, Misting, Grow Light).<br>• Pengiriman paket telemetri berkala via HTTP POST JSON ke server web. | • Tidak mengembangkan antarmuka website dan dashboard pengguna.<br>• Tidak mengelola basis data historis terpusat di server.<br>• Tidak mengimplementasikan logika autentikasi pengguna dan RBAC web.<br>• Tidak mengelola pelaporan dan visualisasi tren jangka panjang. | **TAHAP PERANCANGAN & PROTOTYPING**<br>*(Planned Hardware Integration)* |
| **Mitra Industri / Ahli Agronomi**<br>*(Penyedia Domain Budidaya Melon)* | • Penyediaan spesifikasi varietas melon budidaya (*Cucumis melo L.*).<br>• Penetapan rentang fisiologis optimal iklim mikro dan larutan hara melon.<br>• Penentuan umur panen tanaman (Hari Setelah Polinasi / HSP).<br>• Penetapan kriteria grading kualitas buah (bobot, diameter, brix, netting).<br>• Penentuan unit pencatatan budidaya (blok greenhouse / polybag).<br>• Validasi fungsional dan operasional sistem di lingkungan nyata (UAT). | • Tidak terlibat dalam pemrograman kode perangkat lunak maupun perakitan sirkuit elektronik mikrokontroler. | **TAHAP KONSULTASI DOMAIN**<br>*(Pending Agronomic Confirmation)* |

---

## 2. Daftar Pertanyaan Terbuka untuk Tim Teknik Elektro

Seluruh poin pertanyaan di bawah ini berstatus **`TBD / Pending Confirmation`**. Jawaban resmi dari Tim Elektro akan menjadi acuan spesifikasi teknis untuk implementasi antarmuka perangkat keras pada fase berikutnya.

### Pertanyaan 1: Mode Kontrol Otomatis vs Manual
* **Pertanyaan**: Apakah pengendalian aktuator di greenhouse berjalan secara otonom penuh di edge mikrokontroler ESP32 (*closed-loop feedback*), ataukah memerlukan kemampuan kendali intervensi manual (*override*) langsung dari dashboard web? Bagaimana mekanisme *fail-safe* jika jaringan Wi-Fi greenhouse atau web server mengalami gangguan (*downtime*)?
* **Implikasi Perangkat Lunak**: Jika web mendukung override, server Laravel harus menyediakan antrean perintah (*command queue*). Jika murni otonom di ESP32, web hanya bertindak sebagai pemantau pasif (*read-only status*).
* **Usulan Rekayasa**: Terapkan arsitektur *autonomous local edge*. ESP32 tetap mengendalikan aktuator berdasarkan set-point lokal meskipun jaringan putus, sementara perintah override dari web bersifat prioritas intervensi sementara.
* **Status**: `TBD / Pending Confirmation (Tim Elektro)`

### Pertanyaan 2: Protokol Komunikasi Kendali Aktuator
* **Pertanyaan**: Apabila disepakati bahwa web dapat mengirim perintah override manual, protokol apa yang digunakan oleh ESP32 untuk mengambil perintah tersebut? Apakah ESP32 melakukan *HTTP GET polling* secara berkala ke `/api/actuator-commands`, ataukah tim Elektro merekomendasikan penggunaan broker MQTT?
* **Implikasi Perangkat Lunak**: Polling HTTP mudah diterapkan namun menimbulkan latensi (5–10 detik). MQTT membutuhkan instalasi broker MQTT terpisah (misal Mosquitto) di server.
* **Usulan Rekayasa**: Gunakan HTTP GET polling pada tahap prototype awal (interval 5 detik). Evaluasi transisi ke MQTT hanya jika responsivitas kendali pompa membutuhkan latensi sub-detik.
* **Status**: `TBD / Pending Confirmation (Tim Elektro)`

### Pertanyaan 3: Mekanisme Pelaporan Status dan Log Aktuator
* **Pertanyaan**: Bagaimana mekanisme ESP32 dalam melaporkan perubahan status operasional aktuator (ON/OFF) ke server web? Apakah dikirim melalui endpoint terpisah `POST /api/actuator-logs` setiap kali relay berpindah saklar, ataukah status status aktuator digabung sebagai atribut tambahan pada payload telemetri `POST /api/monitorings`?
* **Implikasi Perangkat Lunak**: Menggabungkan status aktuator pada telemetri reguler menyederhanakan transmisi jaringan ESP32, namun endpoint log terpisah memberikan riwayat kejadian (*event-driven audit*) yang lebih presisi.
* **Usulan Rekayasa**: Kirimkan status saklar (current_state) dalam payload telemetri rutin, dan gunakan endpoint log terpisah hanya untuk insiden pemicu anomali kritis.
* **Status**: `TBD / Pending Confirmation (Tim Elektro)`

### Pertanyaan 4: Algoritma Logika Kendali Lokal
* **Pertanyaan**: Apakah logika kendali aktuator pada firmware ESP32 menggunakan aturan *threshold with hysteresis* (deadband) sederhana, ataukah mengimplementasikan algoritma kecerdasan komputasi seperti *Fuzzy Logic Mamdani/Sugeno*?
* **Implikasi Perangkat Lunak**: Jika menggunakan Fuzzy Logic, server web perlu menampilkan derajat keanggotaan atau label linguistik kondisi (misal: "Agak Panas", "Sangat Kering") pada kartu kondisi tanaman.
* **Status**: `TBD / Pending Confirmation (Tim Elektro)`

### Pertanyaan 5: Mekanisme Sinkronisasi Ambang Batas
* **Pertanyaan**: Bagaimana sinkronisasi nilai ambang batas (*set-point*) operasional antara firmware ESP32 dengan pengaturan di web (`/admin/thresholds`)? Apakah ESP32 menarik konfigurasi batas dari web saat *booting*, ataukah nilai batas di-hardcode di dalam kode sumber firmware ESP32?
* **Implikasi Perangkat Lunak**: Jika ESP32 menarik konfigurasi batas dari web, server wajib menyediakan endpoint `GET /api/parameters/thresholds` yang mengembalikan format JSON nilai minimum, maksimum, dan histeresis.
* **Status**: `TBD / Pending Confirmation (Tim Elektro)`

### Pertanyaan 6: Interval Baku Transmisi Telemetri Sensor
* **Pertanyaan**: Berapa interval baku pengiriman data telemetri dari ESP32 ke server web (misal per 10 detik, 30 detik, atau 60 detik) pada kondisi operasional normal?
* **Implikasi Perangkat Lunak**: Interval pengiriman menentukan estimasi volume data:
  * Interval 10 detik: ~8.640 baris/tanaman/hari (~260.000 baris/bulan).
  * Interval 30 detik: ~2.880 baris/tanaman/hari (~86.400 baris/bulan).
  * Interval 60 detik: ~1.440 baris/tanaman/hari (~43.200 baris/bulan).
  Interval ini juga menjadi dasar waktu tunggu deteksi alarm perangkat offline (FR-32).
* **Usulan Rekayasa**: Tetapkan interval 30 detik untuk kondisi normal, dan 10 detik saat terdeteksi anomali kritis.
* **Status**: `TBD / Pending Confirmation (Tim Elektro)`

### Pertanyaan 7: Jadwal Kesiapan Sensor Analog pH dan EC
* **Pertanyaan**: Kapan modul sensor analog pH (E-201-C) dan EC nutrisi (DFRobot) dijadwalkan siap dikalibrasi dengan cairan standar (buffer pH 4.01/6.86 dan cairan 1413 µS/cm) serta dihubungkan ke ADC ESP32?
* **Implikasi Perangkat Lunak**: Server web saat ini telah mendukung skema *nullable* (FR-20) sehingga aman menerima data tanpa pH/EC, namun kalibrasi fisik diperlukan untuk memvalidasi alarm FR-38.
* **Status**: `TBD / Pending Confirmation (Tim Elektro)`

### Pertanyaan 8: Ketersediaan Modul Visual ESP32-CAM
* **Pertanyaan**: Apakah pada rancangan perangkat keras terdapat penambahan modul kamera mikrokontroler (ESP32-CAM) untuk mengambil snapshot visual tajuk daun dan fisik buah melon secara remote?
* **Implikasi Perangkat Lunak**: Jika ESP32-CAM diterapkan, server Laravel harus menyediakan endpoint unggah gambar biner `POST /api/plants/{id}/snapshots` dan sistem penyimpanan file *storage*.
* **Status**: `TBD / Pending Confirmation (Tim Elektro)`

---

## 3. Daftar Pertanyaan Terbuka untuk Tim Mitra Industri / Agronomi

Seluruh poin di bawah ini berstatus **`TBD / Pending Confirmation`**. Masukan ahli agronomi akan dituangkan ke dalam *seed data* dan logika rekomendasi budidaya.

### Pertanyaan 1: Varietas Melon dan Karakteristik Fase Budidaya
* **Pertanyaan**: Apa saja varietas melon unggulan yang dibudidayakan di dalam greenhouse (misal: Golden Emerald, Inthanon, Rock Melon, Alisha)? Berapa umur panen standar dalam Hari Setelah Polinasi (HSP), dan bagaimana rincian rentang ideal lingkungan pada 4 fase tumbuh utama (Vegetatif, Pembungaan, Pembesaran Buah, Pematangan)?
* **Implikasi Perangkat Lunak**: Menentukan master data varietas pada form pendaftaran tanaman (`plants/create`) serta penyesuaian threshold dinamis per fase tumbuh.
* **Status**: `TBD / Pending Confirmation (Mitra Industri)`

### Pertanyaan 2: Standar Objektif Grading Kualitas Buah
* **Pertanyaan**: Apa kriteria baku pengelompokan mutu panen buah melon (Grade A, Grade B, Grade C, Afkir)? Berapa ambang batas bobot buah (gram), diameter (cm), kerapatan jaring/netting, dan minimal tingkat kemanisan (derajat Brix)?
* **Implikasi Perangkat Lunak**: Kriteria ini akan diimplementasikan sebagai logika validasi dan pengelompokan otomatis pada modul observasi buah (FR-26 & FR-45).
* **Status**: `TBD / Pending Confirmation (Mitra Industri)`

### Pertanyaan 3: Granularitas Satuan Pencatatan Budidaya
* **Pertanyaan**: Apakah pemantauan dan pencatatan budidaya di lapangan dilakukan per blok greenhouse, per batch tanam, atau per polybag/tanaman individual menggunakan label identitas unik (QR Code / Tag Tanaman)?
* **Implikasi Perangkat Lunak**: Mempengaruhi skema database entitas `plants`. Jika berbasis polybag individual, sistem membutuhkan fitur pemindaian kamera QR Code pada aplikasi web mobile.
* **Status**: `TBD / Pending Confirmation (Mitra Industri)`

### Pertanyaan 4: Kebutuhan Format dan Jadwal Pelaporan
* **Pertanyaan**: Siapa saja personel mitra (manajer kebun, pemilik investasi, agronomis lapangan) yang memerlukan akses sistem? Format ekspor laporan apa yang paling dibutuhkan (PDF, Excel/CSV), dan berapa frekuensi laporan yang diinginkan (harian, mingguan, per siklus tanam)?
* **Implikasi Perangkat Lunak**: Menjadi dasar penambahan pustaka generator laporan (misal: Laravel-DomPDF atau Maatwebsite Excel) pada rilis pengembangan selanjutnya.
* **Status**: `TBD / Pending Confirmation (Mitra Industri)`

### Pertanyaan 5: Indikator Agronomi Paling Kritis
* **Pertanyaan**: Dari 8 parameter sensor yang dipantau, parameter apa yang memiliki dampak paling destruktif dan mematikan terhadap tanaman melon jika terjadi anomali mendadak dalam hitungan jam?
* **Implikasi Perangkat Lunak**: Digunakan untuk menetapkan prioritas eskalasi notifikasi alarm (*critical severity alerts*) pada dashboard operator.
* **Status**: `TBD / Pending Confirmation (Mitra Industri)`

---

## 4. Prosedur Penyelesaian Status TBD

1. **Rapat Koordinasi Tripartit**: Diagendakan pertemuan resmi antara Tim Informatika, Tim Elektro, dan Mitra Industri.
2. **Berita Acara Kesepakatan**: Jawaban resmi atas 13 pertanyaan terbuka dituangkan ke dalam *Berita Acara Penyelarasan Kebutuhan Teknis*.
3. **Revisi Minor Dokumen SRS**: Status `TBD / Pending Confirmation` pada dokumen SRS dan Matriks Keterlacakan (RTM) akan dimutakhirkan menjadi `Confirmed` bersamaan dengan rilis revisi dokumen versi 2.1.
