# ARSITEKTUR SIMULASI KONTROL AKTUATOR (PHASE 13)
## SISTEM WEB MONITORING TANAMAN MELON

**Versi Dokumen:** 1.0  
**Tanggal:** 16 Oktober 2026  
**Klasifikasi:** Internal Engineering Specification  
**Penulis:** Senior Laravel Engineer & IoT System Architect  

---

## 1. PENDAHULUAN & PRINSIP DASAR

Modul kontrol aktuator pada Phase 13 dirancang sebagai **Sistem Simulasi Tertutup**. Tujuan utama modul ini adalah mempersiapkan fondasi model data, alur bisnis (*business flow*), pencatatan audit (*command audit trail*), otorisasi multi-lapis (RBAC), dan antarmuka pengguna (UI/UX) sebelum integrasi perangkat keras fisik dilakukan pada fase berikutnya.

### Prinsip Utama (Guiding Principles):
1. **Mode Simulasi Mutlak:** Kontrol hanya memodifikasi state pada basis data aplikasi (`simulation_state`).
2. **Isolasi Hardware Fisik:** Dilarang keras melakukan transmisi sinyal fisik, HTTP request ke IP ESP32, komunikasi relay switching, atau publikasi MQTT.
3. **Integritas Transaksional & Concurrency Safety:** Perubahan state dilindungi oleh transaksi basis data atomik dengan *pessimistic locking* (`lockForUpdate`).
4. **Idempotensi Penuh:** Perintah berulang yang menghasilkan state identik tidak memicu error atau anomali sistem, namun tetap dicatat secara transparan pada log audit.
5. **Separasi Peran (RBAC):** Hanya pengguna dengan peran `admin` yang memiliki izin mengeksekusi perintah; pengguna dengan peran `pengamat` hanya diberikan akses baca (*read-only*).

---

## 2. TAKSONOMI STATE AKTUATOR (STATE ISOLATION)

Untuk menghindari kerancuan konsep antara lingkungan simulasi dan realitas operasional perangkat keras di lapangan, sistem mendefinisikan tiga tingkatan state:

```
+-----------------------------------------------------------------------------------+
|                            ACTUATOR STATE LIFECYCLE                                |
+-----------------------------------------------------------------------------------+

   [ Operator / Admin ]
           │
           ▼ (Web / API Request)
   +───────────────────────────────────+
   │ 1. SIMULATION STATE               │  <-- DITERAPKAN PADA PHASE 13
   │    - Disimpan di DB (actuators)   │
   │    - Nilai: 'ON' | 'OFF'          │
   │    - Menggambarkan state logikal   │
   +───────────────────────────────────+
           │
           │  (Fase Integrasi Mendatang / Phase 14)
           ▼
   +───────────────────────────────────+
   │ 2. DESIRED HARDWARE STATE         │  <-- BELUM DIAKTIFKAN PADA PHASE 13
   │    - Target instruksi ke ESP32    │
   │    - Antrean perintah / MQTT msg   │
   +───────────────────────────────────+
           │
           │  (Feedback Telemetri dari Sensor/Relay Fisik)
           ▼
   +───────────────────────────────────+
   │ 3. REPORTED HARDWARE STATE        │  <-- BELUM DIAKTIFKAN PADA PHASE 13
   │    - Status aktual pin relay      │
   │    - Dikirimkan via telemetri     │
   +───────────────────────────────────+
```

Pada Phase 13, **hanya Simulation State yang beroperasi**. Perangkat tidak memiliki dependensi terhadap ada atau tidaknya koneksi ESP32.

---

## 3. STRUKTUR ENTITAS BASIS DATA

### A. Tabel `actuators`
Menyimpan data master perangkat otomasi yang beroperasi di lingkungan greenhouse melon.

| Nama Kolom | Tipe Data | Nullable | Keterangan |
| :--- | :--- | :---: | :--- |
| `id` | BIGINT UNSIGNED (PK) | No | Auto increment primary key |
| `code` | VARCHAR(50) (UNIQUE) | No | Kode unik identifikasi perangkat (contoh: `PUMP-01`, `FAN-01`) |
| `name` | VARCHAR(150) | No | Nama deskriptif perangkat (contoh: `Pompa Irigasi Utama (Greenhouse A)`) |
| `type` | VARCHAR(50) | No | Jenis perangkat: `irrigation_pump`, `exhaust_fan` |
| `simulation_state` | VARCHAR(10) | No | Status simulasi saat ini: `ON`, `OFF` (Default: `OFF`) |
| `mode` | VARCHAR(30) | No | Mode operasi perangkat: `simulation` (Default: `simulation`) |
| `is_enabled` | BOOLEAN | No | Status aktifitas perangkat (Default: `true`) |
| `last_changed_at` | TIMESTAMP | Yes | Waktu terakhir perubahan status simulasi terjadi |
| `last_user_id` | BIGINT UNSIGNED (FK) | Yes | Relasi ke `users.id` operator yang terakhir mengubah status |
| `location` | VARCHAR(150) | Yes | Informasi lokasi fisik perangkat dalam greenhouse |
| `description` | TEXT | Yes | Deskripsi fungsional perangkat |
| `created_at` | TIMESTAMP | Yes | Waktu pencatatan record |
| `updated_at` | TIMESTAMP | Yes | Waktu pembaruan record |

*Catatan Hubungan Fisik:* Aktuator tidak dipasangi foreign key `plant_id` karena pompa fertigasi dan kipas sirkulasi melayani satu zona greenhouse melon secara kolektif, bukan spesifik per satu tanaman.

### B. Tabel `actuator_command_logs`
Menyimpan riwayat lengkap seluruh eksekusi instruksi kontrol (Audit Trail).

| Nama Kolom | Tipe Data | Nullable | Keterangan |
| :--- | :--- | :---: | :--- |
| `id` | BIGINT UNSIGNED (PK) | No | Auto increment primary key |
| `actuator_id` | BIGINT UNSIGNED (FK) | No | Relasi ke `actuators.id` (Cascade on delete) |
| `user_id` | BIGINT UNSIGNED (FK) | Yes | Relasi ke `users.id` operator pengeksekusi (Null on delete) |
| `command` | VARCHAR(30) | No | Instruksi perintah: `TURN_ON`, `TURN_OFF`, `TOGGLE` |
| `previous_state` | VARCHAR(10) | No | Status simulasi sebelum perintah dieksekusi |
| `resulting_state` | VARCHAR(10) | No | Status simulasi setelah perintah dieksekusi |
| `execution_status`| VARCHAR(20) | No | Hasil eksekusi: `SUCCESS`, `FAILED`, `REJECTED` |
| `source` | VARCHAR(50) | No | Kanal pemicu: `web_simulation`, `api_simulation` |
| `executed_at` | TIMESTAMP | No | Waktu pasti eksekusi perintah dijalankan |
| `notes` | TEXT | Yes | Catatan audit, alasan eksekusi, atau tanda idempotensi |
| `created_at` | TIMESTAMP | Yes | Waktu pencatatan log |
| `updated_at` | TIMESTAMP | Yes | Waktu pembaruan log |

---

## 4. ALUR LOGIKA BISNIS (ACTUATOR SIMULATION SERVICE)

Semua operasi kontrol dikonsentrasikan pada satu service layer tunggal:  
`App\Services\ActuatorSimulationService`.

### Diagram Alur Eksekusi Perintah:

```
[ POST /actuators/{id}/control ]
               │
               ▼
   [ 1. Cek Hak Akses (RBAC) ] ────────── Bukan Admin? ───► [ 403 Forbidden ]
               │
           Admin Valid
               │
               ▼
   [ 2. Validasi Perintah ] ───────────── Tidak Valid? ────► [ 422 Validation Error ]
   (TURN_ON | TURN_OFF | TOGGLE)
               │
           Perintah Valid
               │
               ▼
   [ 3. Validasi Keberadaan Perangkat ] ─ Tidak Ada? ──────► [ 404 Not Found ]
               │
               ▼
   [ 4. Cek is_enabled ] ──────────────── Non-Aktif? ──────► [ Catat REJECTED Log ] ─► [ 422 Error ]
               │
           Perangkat Aktif
               │
               ▼
   [ 5. DB::transaction & lockForUpdate() ]
               │
               ▼
   [ 6. Evaluasi State Baru ]
   - TURN_ON  --> 'ON'
   - TURN_OFF --> 'OFF'
   - TOGGLE   --> Nilai inverse
               │
               ▼
   [ 7. Cek Idempotensi ]
   - Jika previous_state == target_state:
       * Lewati update tabel actuators
       * Tandai flag is_idempotent = true
       * Siapkan catatan audit: "Permintaan idempotent..."
   - Jika previous_state != target_state:
       * Update actuators: simulation_state, last_changed_at, last_user_id
               │
               ▼
   [ 8. Catat ActuatorCommandLog ] (SUCCESS)
               │
               ▼
   [ 9. Commit Transaksi Database ]
               │
               ▼
   [ 10. Kembalikan Response Konsisten ]
   (hardware_dispatched: false, mode: 'simulation')
```

---

## 5. KEAMANAN AKSES (ROLE-BASED ACCESS CONTROL)

Sistem menerapkan prinsip *defense-in-depth* 3 lapis untuk melindungi eksekusi kontrol aktuator:

1. **Lapis 1 - Route Middleware:**  
   Rute `POST /actuators/{actuator}/control` dilindungi oleh `middleware(['auth', 'role:admin'])`.
2. **Lapis 2 - Controller Gate Authorization:**  
   Di dalam method `ActuatorController::control()`, dilakukan pengecekan eksplisit:  
   `abort_if(Auth::user()->role !== 'admin', 403, 'Akses ditolak...');`
3. **Lapis 3 - Domain Service Level Enforcement:**  
   Di dalam `ActuatorSimulationService::executeCommand()`, dilakukan validasi peran sebelum transaksi dibuka:
   ```php
   if ($user && $user->role !== 'admin') {
       throw new HttpException(403, 'Akses ditolak: Hanya Administrator yang berwenang...');
   }
   ```

Pengguna dengan peran `pengamat` dapat mengakses halaman `/actuators` dan `/actuators/history` untuk memantau status secara transparan, namun tombol manipulasi dinonaktifkan di antarmuka dan seluruh *request* manipulasi ditolak secara permanen di backend.

---

## 6. JAMINAN NON-KOMUNIKASI PERANGKAT KERAS

Dokumen ini secara formal menjamin bahwa:
- Tidak terdapat kode `curl`, `Guzzle`, `Http::post`, `fsockopen`, atau panggilan soket TCP/UDP yang diarahkan ke perangkat mikrokontroler.
- Tidak terdapat pustaka MQTT client yang mempublikasikan topik kontrol relay pada Phase 13.
- Perubahan simulasi tidak dapat memicu kerusakan beban listrik, kegagalan motor pompa, atau lonjakan arus di lapangan.
