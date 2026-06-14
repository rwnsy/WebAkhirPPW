# Pustakata

Pustakata adalah aplikasi toko buku online berbasis PHP Native dan MySQL. Aplikasi ini dibuat untuk kebutuhan pembelajaran Pemrograman Web, tetapi struktur fiturnya disusun agar tetap rapi, mudah diuji, dan siap dipindahkan ke shared hosting seperti Hostinger.

Fokus utama project ini adalah katalog buku, keranjang belanja berbasis session, autentikasi user, dashboard admin, upload cover buku, komunitas pembaca, ulasan, dan newsletter.

## Ringkasan

| Area | Keterangan |
| --- | --- |
| Jenis aplikasi | Toko buku online dan komunitas pembaca |
| Backend | PHP Native |
| Database | MySQL atau MariaDB |
| Frontend | HTML, CSS custom, JavaScript vanilla |
| Autentikasi | PHP session dengan role `admin` dan `user` |
| Upload | Cover buku JPG, PNG, WEBP, dan AVIF maksimal 2 MB |
| Status pembayaran | Simulasi checkout, belum memakai payment gateway |

## Fitur Utama

### Pengunjung dan User

- Melihat beranda dengan data buku dan kategori dari database.
- Menelusuri katalog buku dengan pencarian, filter kategori, dan pagination.
- Membuka detail buku, termasuk stok, harga, deskripsi, rating, dan rekomendasi buku sejenis.
- Membuat akun dan login sebagai user.
- Menambahkan buku ke keranjang berbasis session.
- Melakukan checkout setelah login.
- Membaca dan membuat tulisan komunitas.
- Mengirim email ke daftar newsletter.

### Admin

- Login sebagai administrator.
- Melihat dashboard ringkasan data.
- Mengelola data buku melalui fitur tambah, edit, dan hapus.
- Mengunggah dan mengganti cover buku.
- Melihat badge stok buku.
- Mengelola profil admin.
- Memoderasi tulisan komunitas dengan status publish, hide, dan delete.

### Keamanan dan Pengalaman Pengguna

- CSRF token pada form penting.
- Password disimpan dengan hash.
- Role-based access untuk halaman admin.
- Validasi upload cover di server dan browser.
- Proteksi `.htaccess` untuk folder sensitif.
- Toast feedback, modal konfirmasi, preview cover, counter karakter, toggle password, dan scroll reveal.
- Layout responsif untuk desktop, tablet, dan mobile.

## Teknologi

| Layer | Teknologi |
| --- | --- |
| Backend | PHP Native |
| Database | MySQL atau MariaDB dengan `mysqli` |
| Frontend | HTML5, CSS3, JavaScript vanilla |
| Session | PHP session |
| Styling | CSS custom properties dan responsive layout |
| Server target | Apache, LiteSpeed, Devilbox, XAMPP, Laragon, atau Hostinger |

## Struktur Project

```text
htdocs/
├── index.php
├── community.php
├── community-create.php
├── community-detail.php
├── newsletter-subscribe.php
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
│   ├── conn.php
│   └── config.example.php
├── includes/
│   └── partials/
│       ├── public_navbar.php
│       ├── footer.php
│       └── admin_sidebar.php
├── assets/
│   ├── css/
│   ├── js/
│   ├── img/
│   └── uploads/
└── database/
    ├── schema.sql
    ├── hostinger_import.sql
    ├── seed_responsi.sql
    ├── seed_admin.sql
    └── migrations/
```

## Database

Tabel utama yang digunakan:

| Tabel | Fungsi |
| --- | --- |
| `users` | Data akun user dan admin |
| `buku2` | Data katalog buku |
| `reviews` | Rating dan ulasan buku |
| `community_posts` | Tulisan komunitas pembaca |
| `newsletter_subscribers` | Data email newsletter |

File database:

- `database/schema.sql` untuk instalasi lokal baru.
- `database/hostinger_import.sql` untuk import fresh di Hostinger.
- `database/seed_responsi.sql` untuk menambah data demo tanpa menghapus data lama.
- `database/seed_admin.sql` untuk memastikan akun admin tersedia.
- `database/migrations/` untuk memperbarui database lama.

## Instalasi Lokal

### Prasyarat

- PHP 8.x.
- MySQL atau MariaDB.
- Apache/LiteSpeed, Devilbox, XAMPP, Laragon, atau web server lokal lain.
- Ekstensi PHP `mysqli` aktif.

### Langkah Instalasi

1. Letakkan project di document root web server.

   Contoh Devilbox:

   ```text
   ~/devilbox/data/www/pustakata/htdocs/
   ```

2. Import database lokal.

   ```bash
   mysql --host=mysql --user=root < database/schema.sql
   ```

   Jika memakai phpMyAdmin, buat database atau pilih database sesuai kebutuhan lalu import `database/schema.sql`.

3. Sesuaikan koneksi database.

   Aplikasi membaca credential dari environment variable berikut:

   ```text
   PUSTAKATA_DB_HOST
   PUSTAKATA_DB_USER
   PUSTAKATA_DB_PASS
   PUSTAKATA_DB_NAME
   ```

   Jika environment variable tidak tersedia, ubah fallback di `config/conn.php`.

   Contoh lokal:

   ```text
   DB Host     : mysql
   DB User     : root
   DB Password : kosong
   DB Name     : perpustakaan_db
   ```

4. Buka aplikasi melalui browser.

   Contoh Devilbox:

   ```text
   http://pustakata.dvl.to
   ```

## Akun Demo

Semua akun demo memakai password:

```text
password
```

| Role | Email |
| --- | --- |
| Admin | `admin@pustakata.test` |
| User | `user@pustakata.test` |
| User | `nadia@pustakata.test` |
| User | `raka@pustakata.test` |

Signup publik selalu membuat akun dengan role `user`. Role `admin` harus dibuat melalui data seed, migration, atau update database secara terkontrol.

## Alur Aplikasi

### Alur Belanja

```text
Buka katalog -> pilih buku -> tambah ke cart -> login -> checkout -> cart dikosongkan
```

Checkout pada project ini masih berupa simulasi untuk kebutuhan pembelajaran. Fitur tersebut belum membuat invoice, belum mengurangi stok otomatis, dan belum terhubung ke payment gateway.

### Alur Komunitas

```text
Login user -> buka Komunitas -> tulis cerita -> publish -> admin dapat moderasi
```

Tulisan komunitas disimpan di tabel `community_posts` dan terhubung dengan tabel `users`.

### Alur Admin Buku

```text
Login admin -> dashboard -> kelola buku -> upload cover -> data tampil di katalog
```

Cover yang diunggah disimpan di `assets/uploads/`. Format yang diterima adalah JPG, PNG, WEBP, dan AVIF dengan ukuran maksimal 2 MB.

## Query Penting

Beberapa query yang bisa dijelaskan saat review atau responsi:

```sql
SELECT COUNT(*) AS total FROM buku2;
```

Menghitung total buku untuk ringkasan dashboard.

```sql
SELECT b.*, COUNT(r.id) AS review_count, COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating
FROM buku2 b
LEFT JOIN reviews r ON r.buku_id = b.id
GROUP BY b.id, b.judul, b.penulis, b.penerbit, b.tahun_terbit, b.kategori, b.harga, b.stok, b.gambar, b.deskripsi, b.created_at;
```

Mengambil data buku beserta jumlah review dan rata-rata rating.

```sql
SELECT cp.*, u.nama
FROM community_posts cp
INNER JOIN users u ON u.id = cp.user_id
WHERE cp.status = 'published'
ORDER BY cp.created_at DESC;
```

Menampilkan tulisan komunitas yang sudah dipublikasikan bersama nama penulisnya.

## Deploy ke Hostinger

1. Buat database MySQL dari hPanel.
2. Catat host, nama database, user, dan password database.
3. Import `database/hostinger_import.sql` melalui phpMyAdmin Hostinger.
4. Upload source code ke `public_html`.
5. Pastikan folder `assets/uploads/` ikut terupload dan writable.
6. Ubah konfigurasi database di `config/conn.php` atau gunakan environment variable jika hosting mendukung.
7. Jangan upload folder dan file lokal yang tidak diperlukan.

File dan folder yang sebaiknya tidak ikut ke `public_html`:

```text
.git/
.codex/
.agents/
node_modules/
database/
docs/
*.zip
*.bak
*.backup
*.old
*.log
laporan*
```

Setelah deploy, coba akses URL sensitif seperti:

```text
https://domain.com/database/schema.sql
https://domain.com/config/conn.php
https://domain.com/.git/
```

Hasil yang aman adalah tidak bisa diakses publik, misalnya `403`, `404`, blank, atau redirect tertutup.

## Checklist Pengujian

Gunakan daftar ini setelah instalasi lokal atau deploy:

- Beranda tampil dan data buku muncul.
- Katalog dapat dicari dan difilter.
- Pagination katalog berjalan.
- Detail buku menampilkan data lengkap.
- Rating dan jumlah review tampil.
- Rekomendasi buku sejenis tampil.
- Signup user berjalan.
- Login dan logout berjalan.
- User tidak bisa membuka halaman admin.
- Cart dapat tambah, update, hapus, dan kosongkan item.
- Checkout meminta login jika user belum masuk.
- Admin dapat membuka dashboard.
- Admin dapat tambah, edit, dan hapus buku.
- Upload cover JPG, PNG, WEBP, dan AVIF berjalan.
- Preview cover tampil sebelum submit.
- Admin dapat moderasi tulisan komunitas.
- Newsletter tersimpan ke database.
- Tampilan mobile tidak overflow.

## Troubleshooting

| Masalah | Pemeriksaan |
| --- | --- |
| Website blank | Cek PHP error log, versi PHP, dan konfigurasi `conn.php` |
| Database gagal terhubung | Cek host, username, password, nama database, dan import SQL |
| CSS atau JS tidak terbaca | Pastikan folder `assets/` ikut terupload |
| Cover buku tidak tampil | Pastikan file ada di `assets/uploads/` dan nama file sesuai database |
| Upload cover gagal | Cek permission `assets/uploads/`, ukuran file, dan format gambar |
| Login gagal | Cek tabel `users`, password hash, dan session PHP |
| Admin redirect terus | Pastikan akun memiliki role `admin` |
| SQL import error di Hostinger | Hapus `CREATE DATABASE` dan `USE` jika memakai file SQL lokal |
| Akses file sensitif terbuka | Pastikan `.htaccess` ikut terupload atau hapus folder sensitif dari public |

## Catatan Keamanan

- Ganti password admin sebelum aplikasi dibagikan secara publik.
- Jangan simpan credential produksi di repository publik.
- Hindari upload folder `.git`, file SQL, backup, atau laporan ke `public_html`.
- Pastikan `assets/uploads/` hanya menerima file gambar yang valid.
- Pertahankan proteksi `.htaccess` untuk `config/`, `database/`, `docs/`, dan dotfile sensitif.

## Lisensi dan Penggunaan

Project ini dibuat untuk kebutuhan pembelajaran dan evaluasi Pemrograman Web. Source code dapat digunakan sebagai referensi belajar, dengan tetap menyesuaikan credential, data, dan konfigurasi keamanan sebelum dipakai di lingkungan produksi.
