# Mini Inventory — PHP + SQLite

Proof of concept untuk menguji alur kerja GitHub → Android → PHP → SQLite → browser preview.

## Fitur
- Tambah barang (kode, nama, stok awal).
- Lihat daftar barang dan ringkasan stok.
- Tambah atau kurangi stok.
- Data disimpan permanen di `database/inventory.sqlite` setelah aplikasi pertama dijalankan.

## Kebutuhan
- PHP 8.1 atau lebih baru.
- Ekstensi PHP `pdo_sqlite` dan `sqlite3`.
- Browser modern.

## Menjalankan di Android (Termux)

1. Clone repository dan masuk ke foldernya:
   ```sh
   git clone https://github.com/ihsanmaximus/phptest.git
   cd phptest
   ```
2. Pastikan PHP dan ekstensi SQLite tersedia:
   ```sh
   php -v
   php -m | grep -i sqlite
   ```
3. Jalankan server:
   ```sh
   php -S 127.0.0.1:8080
   ```
4. Buka browser Android ke `http://127.0.0.1:8080`.

Jika menggunakan editor yang memiliki terminal, jalankan perintah server dari root repository yang sama.

## Database
- Struktur tabel berada di `database/schema.sql`.
- `config/database.php` membuat file SQLite dan menerapkan schema secara otomatis.
- File database aktual `database/inventory.sqlite` dibuat saat aplikasi pertama dibuka dan tidak disimpan ke Git.

## Alur revisi
Setelah perubahan di-push ke branch yang sedang digunakan:
```sh
git pull
```
Refresh browser untuk melihat perubahan. Jika server berhenti, jalankan kembali perintah PHP server.

## Catatan
Ini aplikasi demo untuk pengujian alur kerja, bukan aplikasi farmasi siap produksi. Belum mencakup autentikasi, audit log, backup terjadwal, atau kontrol akses.
