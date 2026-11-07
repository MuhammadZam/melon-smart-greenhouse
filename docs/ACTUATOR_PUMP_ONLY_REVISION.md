# Revisi aktuator — PUMP-01 saja

Tanggal: 7 November 2026. Perangkat aktif: **PUMP-01, pompa air**. Status implementasi: **simulasi database**, belum terintegrasi ESP32/relay fisik.

FAN-01 tidak lagi ditampilkan pada dashboard, daftar perangkat, pilihan filter riwayat aktif, log ringkas, seeder perangkat baru, atau rekomendasi fuzzy. Record dan log fan lama tidak dihapus. Riwayat mentah tetap tersedia di database untuk audit.

`ActuatorSimulationService` memfilter perangkat berdasarkan kode PUMP-01. Perintah pada record FAN-01 lama tetap ditolak 422 walaupun is_enabled masih true dan URL diketahui. Pemeriksaan ulang kode/status aktif dilakukan setelah row lock, sebelum perubahan state. Admin tetap diperlukan pada middleware/controller/service; pengamat hanya membaca. TURN_ON, TURN_OFF, TOGGLE, transaksi, locking, audit log, dan penanganan perintah idempotent dipertahankan.

Respons simulasi tetap menyatakan `mode=simulation` dan `hardware_dispatched=false`. Status `reported_pump_state` dari Google Sheets adalah laporan eksternal; tidak mengubah simulation_state dan tidak membuktikan relay menerima perintah. Tidak ada MQTT publish, request relay, driver GPIO, kontrol otomatis berbasis fuzzy, atau dispatch hardware baru.

Seeder hanya menyiapkan pompa untuk database baru. Tidak dijalankan untuk menonaktifkan atau menghapus record fan pada database proyek. Guard runtime langsung menutup alur aktif fan setelah kode diterapkan.

Validasi tim Elektro yang belum selesai: protokol ESP32, autentikasi perintah, mekanisme ACK/status aktual, interlock, batas durasi/debit, kondisi gagal aman, dan uji relay/perangkat nyata. Fitur tersebut belum diklaim tersedia.
