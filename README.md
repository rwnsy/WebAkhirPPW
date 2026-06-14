# Pustakata - PHP Native

Pustakata adalah website toko buku online sederhana untuk UAS/Responsi Pemrograman Web 1. Project memakai HTML, CSS custom, JavaScript vanilla, PHP Native, dan MySQL/MariaDB.

## Fitur

- Beranda dengan buku dan kategori dinamis dari database
- Section buku terbaru dan CTA menuju katalog toko
- Katalog toko dengan search, filter kategori, pagination, rating, dan stok
- Detail buku dengan data review
- Rekomendasi buku sejenis di detail buku
- Komunitas pembaca dengan posting user dari database
- Detail tulisan komunitas dan admin moderasi publish/hide/delete
- Feed ulasan pembaca dari relasi review dan buku
- Newsletter subscriber tersimpan ke database
- Cart sederhana berbasis session
- Checkout keranjang untuk alur belanja WEB1 tanpa payment gateway
- Login, logout, signup user
- Role `admin` dan `user`
- Dashboard admin
- CRUD buku
- Upload cover JPG, PNG, WEBP, AVIF maksimal 2 MB
- Preview cover sebelum upload
- Character counter deskripsi buku
- CSRF token pada form penting
- Hapus buku via POST dengan modal konfirmasi custom
- Toast feedback sukses/error
- Badge stok: Habis, Stok Menipis, Tersedia
- Scroll to top dan feedback pencarian
- Responsive untuk mobile, tablet, dan desktop

## Data Responsi

Fresh import `database/schema.sql` berisi data awal untuk memperkuat responsi:

- 4 user, termasuk 1 admin
- 8 buku dari kategori Novel, Pengembangan Diri, Teknologi, Fantasi, Akademik, dan Sejarah
- 10 review yang terhubung ke buku valid
- 5 tulisan komunitas yang terhubung ke user valid
- Tabel `newsletter_subscribers` dengan 2 email awal

Jika database lokal sudah terlanjur berisi data lama, import:

```text
database/seed_responsi.sql
```

File tersebut menambah data responsi secara idempotent tanpa menghapus data lama.

## Query Responsi

Query 1 tabel:

```sql
SELECT COUNT(*) AS total FROM buku2;
```

Dipakai di dashboard admin untuk menghitung total buku.

Query JOIN 2 tabel:

```sql
SELECT b.*, COUNT(r.id) AS review_count, COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating
FROM buku2 b
LEFT JOIN reviews r ON r.buku_id = b.id
GROUP BY b.id, b.judul, b.penulis, b.penerbit, b.tahun_terbit, b.kategori, b.harga, b.stok, b.gambar, b.deskripsi, b.created_at;
```

Dipakai pada beranda, toko, detail buku, dan dashboard admin untuk menampilkan rating rata-rata serta jumlah review.

Query JOIN komunitas:

```sql
SELECT cp.*, u.nama
FROM community_posts cp
INNER JOIN users u ON u.id = cp.user_id
WHERE cp.status = 'published'
ORDER BY cp.created_at DESC;
```

Dipakai pada halaman Komunitas untuk menampilkan tulisan pembaca, nama penulis, tanggal posting, dan isi tulisan.

Query JOIN review komunitas:

```sql
SELECT r.*, b.judul, b.penulis
FROM reviews r
INNER JOIN buku2 b ON b.id = r.buku_id;
```

Dipakai pada bagian Ulasan Pembaca di halaman Komunitas.

Query newsletter:

```sql
INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?);
```

Dipakai pada form newsletter footer dan halaman Komunitas. Email duplikat tidak disimpan ulang.

## Cara Menjalankan Lokal

1. Letakkan project di web server lokal, misalnya Devilbox:

```text
~/devilbox/data/www/pustakata/htdocs/
```

2. Buat/import database lewat phpMyAdmin atau CLI:

```text
database/schema.sql
```

Jika database lama sudah ada dan tidak ingin drop data buku, import file migration ini:

```text
database/migrations/migration_add_role_reviews.sql
```

Jika database sudah memakai schema lama dan belum memiliki fitur komunitas posting user, import file migration ini:

```text
database/migrations/migration_add_community_posts.sql
```

Jika ingin menambah data responsi dan tabel newsletter ke database lama, import:

```text
database/seed_responsi.sql
```

3. Pastikan nama database adalah:

```text
perpustakaan_db
```

4. Sesuaikan credential database di `config/conn.php` jika host/user/password lokal berbeda.

Default lokal:

```text
Host     : mysql
User     : root
Password : kosong
Database : perpustakaan_db
```

5. Buka aplikasi:

```text
http://pustakata.dvl.to
```

## Akun Responsi

```text
Admin:
Email    : admin@pustakata.test
Password : password

User:
Email    : user@pustakata.test
Password : password

User tambahan:
Email    : nadia@pustakata.test
Password : password

Email    : raka@pustakata.test
Password : password
```

Signup publik hanya membuat akun `user`, bukan `admin`.

Catatan checkout: fitur checkout mengosongkan cart untuk alur belanja WEB1, tetapi belum ada pembayaran, order, invoice, atau pengurangan stok otomatis.

## Struktur Folder

Struktur project sengaja dibuat sederhana agar mudah dijelaskan saat responsi, tetapi tetap rapi untuk hosting PHP Native.

```text
htdocs/
├── index.php
├── community.php
├── community-create.php
├── community-detail.php
├── newsletter-subscribe.php
├── .htaccess
├── .deployignore
├── README.md
├── admin/
│   ├── dashboard.php
│   ├── books.php
│   ├── book-form.php
│   ├── book-delete.php
│   ├── community-posts.php
│   └── profile.php
├── auth/
│   ├── login.php
│   ├── signup.php
│   └── logout.php
├── shop/
│   ├── index.php
│   ├── detail.php
│   └── cart.php
├── config/
│   └── conn.php
├── includes/
│   └── partials/
│       ├── public_navbar.php
│       ├── footer.php
│       └── admin_sidebar.php
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   ├── components/
│   │   └── pages/
│   ├── js/
│   │   └── script.js
│   ├── img/
│   └── uploads/
└── database/
    ├── schema.sql
    ├── hostinger_import.sql
    ├── seed_responsi.sql
    ├── seed_admin.sql
    └── migrations/
```

File yang aman dirapikan hanya file dokumentasi, seed, migration, dan asset statis yang tidak dipanggil langsung oleh route publik. File halaman seperti `index.php`, `shop/detail.php`, `admin/books.php`, `config/conn.php`, dan partial di `includes/partials/` sebaiknya tidak dipindahkan lagi karena semua path, form action, helper `url()`, `asset()`, `upload_url()`, dan `partial_path()` sudah mengikuti struktur ini.

## Deploy Hostinger

### A. Checklist Sebelum Deploy

- Backup project final, termasuk `assets/uploads/`.
- Backup database lokal melalui phpMyAdmin atau `mysqldump`.
- Jalankan project di lokal dan tes beranda, toko, detail buku, cart, login, admin, komunitas, newsletter, dan upload cover.
- Pastikan data responsi cukup: minimal 8 buku, beberapa review, beberapa tulisan komunitas, admin user, dan data newsletter bila diperlukan.
- Pastikan file SQL yang akan diimport sudah final: gunakan `database/hostinger_import.sql` untuk deploy fresh Hostinger, atau export database lokal jika ingin membawa data lokal persis.
- Pastikan `.htaccess` ikut upload karena file ini memblokir akses langsung ke `config/`, `database/`, `docs/`, file `.sql`, ZIP, backup, dan folder dotfile sensitif.

### B. Langkah Deploy Database

1. Masuk hPanel Hostinger.
2. Buka menu MySQL Databases.
3. Buat database baru.
4. Catat credential database:

```text
DB Host     : biasanya localhost, tetap cek detail di hPanel
DB Name     : biasanya memakai prefix, contoh u123456789_pustakata
DB User     : biasanya memakai prefix, contoh u123456789_userdb
DB Password : password database dari Hostinger
```

5. Buka phpMyAdmin Hostinger.
6. Pilih database yang baru dibuat.
7. Import salah satu file SQL:

```text
database/hostinger_import.sql
```

atau export database lokal jika ingin membawa semua data lokal.

Jika memakai `database/schema.sql` atau export lokal yang berisi perintah berikut, hapus dulu sebelum import ke Hostinger:

```sql
CREATE DATABASE IF NOT EXISTS perpustakaan_db;
USE perpustakaan_db;
```

Database Hostinger sudah dibuat lewat hPanel, jadi phpMyAdmin cukup mengimport tabel dan data ke database yang sedang dipilih.

Setelah import, pastikan tabel berikut muncul:

```text
users
buku2
reviews
community_posts
newsletter_subscribers
```

Pastikan data buku, review, tulisan komunitas, dan akun admin ada.

### C. Langkah Upload File

Upload isi project ke `public_html`, terutama:

```text
index.php
community.php
community-create.php
community-detail.php
newsletter-subscribe.php
.htaccess
auth/
shop/
admin/
config/
includes/
assets/
```

Pastikan folder ini ikut:

```text
assets/uploads/
```

Folder `assets/uploads/` dipakai untuk cover buku hasil upload admin.

### D. Konfigurasi `conn.php`

File koneksi ada di:

```text
config/conn.php
```

Credential lokal seperti ini tidak boleh dipakai di Hostinger:

```php
$servername = 'mysql';
$username = 'root';
$password = '';
$database = 'perpustakaan_db';
```

Gunakan credential Hostinger, contoh:

```php
$servername = 'localhost';
$username = 'u123456789_userdb';
$password = 'PASSWORD_HOSTINGER';
$database = 'u123456789_pustakata';
```

Project juga mendukung environment variable:

```text
PUSTAKATA_DB_HOST=localhost
PUSTAKATA_DB_USER=u123456789_userdb
PUSTAKATA_DB_PASS=PASSWORD_HOSTINGER
PUSTAKATA_DB_NAME=u123456789_pustakata
```

Jika shared hosting tidak menyediakan environment variable, ubah fallback di `config/conn.php` sebelum upload. Host database Hostinger biasanya `localhost`, tetapi tetap cek detail di hPanel.

### E. File Yang Tidak Boleh Ikut Upload

Jangan upload file/folder berikut ke `public_html`:

```text
.git/
.codex/
.agents/
node_modules/
htdocs.zip
*.zip
*.bak
*.backup
*.old
*.log
database/ setelah SQL selesai diimport
docs/ jika tidak dibutuhkan
file laporan
file LaTeX
screenshot tugas
file catatan pribadi
file dev lain
```

Gunakan `.deployignore` sebagai checklist manual saat memilih file yang akan diupload.

### F. Permission Upload

Folder berikut harus bisa ditulis oleh server:

```text
assets/uploads/
```

Jika upload cover gagal, cek permission folder. Umumnya folder cukup `755`. Jangan langsung memakai `777` kecuali benar-benar terpaksa dan paham risikonya.

### G. Cek Keamanan Setelah Upload

Coba akses URL sensitif:

```text
https://domain.com/database/schema.sql
https://domain.com/config/conn.php
https://domain.com/.git/
```

Hasil aman adalah `403`, `404`, blank, atau tidak bisa diakses. Jika file SQL bisa didownload, hapus folder `database/` dari `public_html`.

### H. Checklist Tes Setelah Online

- Beranda tampil dan data buku muncul.
- Katalog tampil.
- Search jalan.
- Filter kategori jalan.
- Pagination jalan.
- Detail buku tampil.
- Rating dan jumlah review tampil.
- Login admin jalan.
- Dashboard admin tampil.
- CRUD buku jalan.
- Upload cover jalan.
- Signup user jalan.
- Login user jalan.
- Cart session jalan.
- Checkout keranjang jalan sesuai alur WEB1.
- User bisa membuat tulisan komunitas.
- Detail tulisan komunitas tampil.
- Admin bisa moderasi komunitas.
- Newsletter subscribe tersimpan ke database.
- Toast, modal confirm, toggle password, loading state, character counter, preview cover, dan scroll reveal masih aktif.
- Responsive mobile tidak overflow.

### I. Ganti Password Admin

Jika masih memakai akun responsi:

```text
admin@pustakata.test
password
```

Ganti password admin setelah deploy sebelum link dibagikan.

### J. Troubleshooting

Website blank:
Cek PHP error log Hostinger, versi PHP, dan pastikan `config/conn.php` tidak typo.

Database connection failed:
Cek DB host, DB name, DB user, DB password, dan pastikan database sudah diimport.

CSS/JS tidak terbaca:
Pastikan folder `assets/` ikut upload dan helper `asset()` tidak diubah.

Gambar cover broken:
Pastikan `assets/uploads/` ikut upload dan nama file di database sama dengan file di folder.

Upload cover gagal:
Cek permission `assets/uploads/`, ukuran file maksimal 2 MB, dan format JPG/PNG/WEBP/AVIF.

Login gagal:
Cek tabel `users`, password hash, session PHP, dan credential akun.

SQL import error:
Pastikan database dipilih di phpMyAdmin. Hapus `CREATE DATABASE` dan `USE` jika Hostinger menolak.

500 Internal Server Error:
Cek `.htaccess`, versi PHP, ekstensi `mysqli`, dan error log.

Permission denied:
Cek permission folder dan file. Mulai dari folder `755` dan file `644`.

Halaman admin redirect terus:
Pastikan login sebagai user role `admin`, bukan role `user`.

`.htaccess` tidak berjalan:
Pastikan file `.htaccess` ikut upload dan hosting Apache/LiteSpeed mengizinkan aturan `.htaccess`.

### K. Checklist Final Sebelum Link Dikirim

- Database Hostinger sudah diimport.
- Credential `config/conn.php` sudah benar.
- Folder `database/`, `.git/`, `.codex/`, `.agents/`, ZIP backup, dan file laporan tidak ada di public.
- `assets/uploads/` ada dan writable.
- Password admin sudah diganti.
- Semua fitur utama sudah dites.
- Website dicek di mobile dan desktop.
- URL sensitif sudah tidak bisa diakses publik.

## Pengujian Manual

- Buka beranda dan pastikan buku populer muncul dari database.
- Buka toko, coba search, filter kategori, pagination.
- Pastikan badge stok tampil di toko dan admin.
- Buka detail buku, pastikan rating/review tampil.
- Cek rekomendasi buku sejenis di detail.
- Tambahkan buku ke cart, update jumlah, hapus item, lalu checkout.
- Subscribe newsletter dari footer atau halaman Komunitas, lalu cek tabel `newsletter_subscribers`.
- Login admin.
- Tambah buku dengan cover.
- Coba counter deskripsi dan preview cover sebelum simpan.
- Edit buku dan ganti cover.
- Hapus buku dan pastikan cover lama ikut terhapus.
- Update profil admin.
- Signup user, lalu pastikan user tidak bisa membuka halaman admin.
- Login sebagai user, buka Komunitas, klik Tulis Cerita, lalu publish tulisan.
- Buka detail tulisan komunitas dan pastikan nama penulis muncul dari tabel users.
- Login admin, buka Moderasi Komunitas, coba hide/publish/delete tulisan via tombol POST.

## Alur Responsi Komunitas

1. Buka halaman Komunitas.
2. Tunjukkan bagian Tulisan Komunitas yang berasal dari JOIN `community_posts` dan `users`.
3. Login sebagai user.
4. Buat tulisan baru lewat tombol Tulis Cerita.
5. Pastikan tulisan muncul di daftar dan detail tulisan.
6. Login sebagai admin.
7. Buka Moderasi Komunitas, lalu tunjukkan aksi hide/publish/delete.
8. Tunjukkan bagian Ulasan Pembaca yang tetap berasal dari JOIN `reviews` dan `buku2`.
