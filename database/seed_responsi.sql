USE perpustakaan_db;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(160) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_newsletter_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO newsletter_subscribers (email)
VALUES
    ('pembaca@pustakata.test'),
    ('komunitas@pustakata.test');

INSERT INTO users (nama, email, password, role)
VALUES
    ('Admin Pustakata', 'admin@pustakata.test', '$2y$10$HQQmCs35QqI3oa4nNSMj3eVLnjGEFwzxE0iPDQ0uf9hDmgZfzeruW', 'admin'),
    ('Pembaca Pustakata', 'user@pustakata.test', '$2y$10$HQQmCs35QqI3oa4nNSMj3eVLnjGEFwzxE0iPDQ0uf9hDmgZfzeruW', 'user'),
    ('Nadia Literasi', 'nadia@pustakata.test', '$2y$10$HQQmCs35QqI3oa4nNSMj3eVLnjGEFwzxE0iPDQ0uf9hDmgZfzeruW', 'user'),
    ('Raka Buku', 'raka@pustakata.test', '$2y$10$HQQmCs35QqI3oa4nNSMj3eVLnjGEFwzxE0iPDQ0uf9hDmgZfzeruW', 'user')
ON DUPLICATE KEY UPDATE
    nama = VALUES(nama),
    role = VALUES(role);

INSERT INTO buku2 (judul, penulis, penerbit, tahun_terbit, kategori, harga, stok, gambar, deskripsi)
SELECT seed.judul, seed.penulis, seed.penerbit, seed.tahun_terbit, seed.kategori, seed.harga, seed.stok, seed.gambar, seed.deskripsi
FROM (
    SELECT 'Bumi Manusia' AS judul, 'Pramoedya Ananta Toer' AS penulis, 'Lentera Dipantara' AS penerbit, 1980 AS tahun_terbit, 'Sejarah' AS kategori, 148000 AS harga, 12 AS stok, NULL AS gambar, 'Novel berlatar masa kolonial yang kuat untuk membahas martabat, pendidikan, dan keberanian berpikir.' AS deskripsi
    UNION ALL SELECT 'Laut Bercerita', 'Leila S. Chudori', 'Kepustakaan Populer Gramedia', 2017, 'Novel', 115000, 14, NULL, 'Kisah keluarga, ingatan, dan keberanian yang dekat dengan sejarah sosial Indonesia modern.'
    UNION ALL SELECT 'Atomic Habits', 'James Clear', 'Gramedia Pustaka Utama', 2018, 'Pengembangan Diri', 135000, 16, NULL, 'Panduan praktis membangun kebiasaan kecil yang menghasilkan perubahan besar secara bertahap.'
    UNION ALL SELECT 'Seni Berpikir Jernih', 'Rolf Dobelli', 'Kepustakaan Populer Gramedia', 2011, 'Pengembangan Diri', 99000, 9, NULL, 'Kumpulan esai ringkas tentang bias berpikir dan cara mengambil keputusan dengan lebih sadar.'
    UNION ALL SELECT 'Dasar Pemrograman Web', 'Tim Pustakata Edu', 'Pustakata Press', 2025, 'Teknologi', 89000, 20, NULL, 'Buku pengantar HTML, CSS, JavaScript, PHP Native, dan MySQL untuk membangun website dinamis.'
    UNION ALL SELECT 'Algoritma dan Struktur Data', 'Dian Pratama', 'Nusa Akademik', 2023, 'Akademik', 122000, 11, NULL, 'Materi dasar algoritma, array, stack, queue, sorting, dan pencarian dengan latihan yang mudah diikuti.'
    UNION ALL SELECT 'Gerbang Awan Utara', 'Sagara Nata', 'Nusa Fantasi', 2024, 'Fantasi', 99000, 10, NULL, 'Petualangan fantasi tentang penjaga perpustakaan tua, peta rahasia, dan kota di atas awan.'
    UNION ALL SELECT 'Sejarah Kota-Kota Tua', 'Maya Lestari', 'Ruang Arsip', 2022, 'Sejarah', 108000, 7, NULL, 'Catatan populer tentang kota tua, arsip, dan perubahan ruang urban dari masa ke masa.'
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM buku2 b WHERE b.judul = seed.judul
);

INSERT INTO reviews (buku_id, reviewer_name, rating, komentar)
SELECT b.id, seed.reviewer_name, seed.rating, seed.komentar
FROM (
    SELECT 'Bumi Manusia' AS judul, 'Raka' AS reviewer_name, 5 AS rating, 'Bacaan kuat, rapi, dan sangat cocok untuk menjelaskan hubungan novel dengan sejarah.' AS komentar
    UNION ALL SELECT 'Bumi Manusia', 'Maya', 4, 'Bahasanya padat, tetapi konflik dan latarnya membuat buku ini berkesan.'
    UNION ALL SELECT 'Laut Bercerita', 'Nadia', 5, 'Emosinya terasa dekat dan membuat pembaca ingin mencari tahu konteks sejarahnya.'
    UNION ALL SELECT 'Atomic Habits', 'Bagas', 5, 'Panduannya konkret dan mudah dipakai untuk membangun kebiasaan kecil.'
    UNION ALL SELECT 'Seni Berpikir Jernih', 'Sinta', 4, 'Ringkas, enak dibaca, dan membantu memahami jebakan cara berpikir.'
    UNION ALL SELECT 'Dasar Pemrograman Web', 'Fajar', 5, 'Materinya cocok untuk pemula WEB1 karena alurnya dari frontend sampai database.'
    UNION ALL SELECT 'Algoritma dan Struktur Data', 'Dimas', 4, 'Strukturnya jelas dan latihan terarahnya membantu memahami logika dasar.'
    UNION ALL SELECT 'Gerbang Awan Utara', 'Laras', 5, 'Dunia fantasinya hangat, visual, dan cocok untuk pembaca yang ingin cerita ringan.'
    UNION ALL SELECT 'Sejarah Kota-Kota Tua', 'Ayu', 4, 'Pembahasannya populer tetapi tetap informatif untuk mengenal sejarah kota.'
    UNION ALL SELECT 'Laut Bercerita', 'Wulan', 4, 'Alurnya emosional dan diskusinya menarik untuk komunitas pembaca.'
) AS seed
INNER JOIN buku2 b ON b.judul = seed.judul
WHERE NOT EXISTS (
    SELECT 1
    FROM reviews r
    WHERE r.buku_id = b.id
      AND r.reviewer_name = seed.reviewer_name
);

INSERT INTO community_posts (user_id, title, content, status)
SELECT u.id, seed.title, seed.content, 'published'
FROM (
    SELECT 'user@pustakata.test' AS email,
           'Ritual Membaca yang Membuat Buku Lebih Hidup' AS title,
           'Saya mulai mencatat satu kalimat favorit setiap selesai membaca satu bab. Kebiasaan kecil ini membuat buku terasa lebih personal dan membantu saya mengingat alasan kenapa sebuah cerita meninggalkan kesan.' AS content
    UNION ALL SELECT 'admin@pustakata.test',
           'Rekomendasi Buku untuk Mulai Membaca Sejarah',
           'Untuk teman-teman yang baru mulai membaca buku sejarah, saya sarankan memilih buku dengan cerita manusia yang kuat. Setelah nyaman, baru naik ke bacaan yang lebih akademik dan penuh arsip.'
    UNION ALL SELECT 'nadia@pustakata.test',
           'Kenapa Review Pembaca Membantu Sebelum Membeli Buku',
           'Review pembaca membantu saya memahami suasana buku tanpa harus terkena spoiler besar. Rating dan ulasan membuat proses memilih buku terasa lebih tenang.'
    UNION ALL SELECT 'raka@pustakata.test',
           'Catatan Kecil Setelah Membaca Buku Teknologi',
           'Buku teknologi paling enak dibaca sambil langsung mencoba praktik kecil. Saat konsep dipraktikkan, istilah yang awalnya terasa berat jadi jauh lebih masuk akal.'
    UNION ALL SELECT 'nadia@pustakata.test',
           'Fantasi Sebagai Tempat Istirahat',
           'Saya suka membaca fantasi setelah hari yang panjang. Dunia imajinatif memberi jarak dari rutinitas, tetapi tetap menyisakan keberanian dan harapan yang bisa dibawa kembali.'
) AS seed
INNER JOIN users u ON u.email = seed.email
WHERE NOT EXISTS (
    SELECT 1 FROM community_posts cp WHERE cp.title = seed.title
);
