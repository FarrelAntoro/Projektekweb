# Warehouse Management System

Aplikasi inventaris dan distribusi gudang menggunakan PHP Native (PDO), MySQL, HTML, CSS, dan JavaScript. Dirancang untuk Laravel Herd + MySQL lokal, tanpa framework Laravel.

## Fitur
- Guest: melihat katalog barang, register, login.
- User: dashboard, melihat katalog dan stok, membuat permintaan barang, melihat riwayat.
- Admin: dashboard, CRUD barang, kategori, gudang, stok, pengguna; memproses permintaan (approve/reject).
- Persetujuan permintaan dan pengurangan stok dilakukan dalam transaksi database.
- Password di-hash, query memakai PDO prepared statements, proteksi CSRF, dan role guard.

## Persyaratan
- PHP 8.1+ dengan ekstensi `pdo_mysql`.
- MySQL 8+ (atau MariaDB yang mendukung InnoDB).
- Laravel Herd atau web server PHP lain.

## Instalasi
1. Salin folder project ke folder yang dipantau Herd, misalnya `C:\Users\<user>\Herd\warehouse-management-system`.
2. Buat database MySQL bernama `warehouse_db`.
3. Import `database/warehouse.sql` melalui TablePlus, DBeaver, MySQL Workbench, atau phpMyAdmin.
4. Salin `config/database.example.php` menjadi `config/database.php`, lalu sesuaikan host, port, database, username, dan password MySQL lokal.
5. Buka `http://warehouse-management-system.test` (sesuaikan domain Herd yang muncul).
6. Buat akun admin dengan membuka sekali `http://warehouse-management-system.test/setup_admin.php`.
   - Email default: `admin@warehouse.local`
   - Password default: `Admin123!`
   - Segera login dan ganti password. Setelah berhasil membuat admin, hapus `setup_admin.php` dari folder project.
7. Register akun biasa melalui halaman Register.

## Alur penggunaan
- User mengajukan permintaan dengan memilih barang, gudang, dan jumlah.
- Permintaan masuk dengan status `pending`.
- Admin membuka menu Permintaan dan memilih Setujui atau Tolak.
- Saat disetujui, sistem mengecek stok terkini, mengurangi stok, dan mencatat transaksi keluar secara atomik.
- Jika stok tidak cukup, persetujuan dibatalkan dan stok tidak berubah.

## GitHub untuk kerja kelompok
- Jangan commit `config/database.php` (sudah masuk `.gitignore`).
- Commit `database/warehouse.sql` agar semua anggota memiliki struktur database sama.
- Setiap anggota gunakan database lokal masing-masing.
- Gunakan branch fitur dan Pull Request untuk menggabungkan perubahan.

## Catatan pengembangan
Project ini adalah fondasi akademik. Sebelum dipakai di lingkungan produksi, tambahkan HTTPS, rate limiting, audit log yang lebih lengkap, validasi kebijakan password, backup, dan pengujian keamanan.
