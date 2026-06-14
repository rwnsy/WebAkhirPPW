USE perpustakaan_db;

CREATE TABLE IF NOT EXISTS community_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('published','hidden') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_community_posts_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    INDEX idx_community_posts_status (status),
    INDEX idx_community_posts_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO community_posts (user_id, title, content, status)
SELECT u.id,
       'Ritual Membaca yang Membuat Buku Lebih Hidup',
       'Saya mulai mencatat satu kalimat favorit setiap selesai membaca satu bab. Kebiasaan kecil ini membuat buku terasa lebih personal dan membantu saya mengingat alasan kenapa sebuah cerita meninggalkan kesan.',
       'published'
FROM users u
WHERE u.email = 'user@pustakata.test'
AND NOT EXISTS (SELECT 1 FROM community_posts WHERE title = 'Ritual Membaca yang Membuat Buku Lebih Hidup');

INSERT INTO community_posts (user_id, title, content, status)
SELECT u.id,
       'Rekomendasi Buku untuk Mulai Membaca Sastra Klasik',
       'Untuk teman-teman yang baru mulai membaca sastra klasik, saya sarankan memilih buku dengan konflik yang dekat dengan kehidupan sehari-hari. Setelah itu baru naik ke karya yang bahasanya lebih padat.',
       'published'
FROM users u
WHERE u.email = 'admin@pustakata.test'
AND NOT EXISTS (SELECT 1 FROM community_posts WHERE title = 'Rekomendasi Buku untuk Mulai Membaca Sastra Klasik');

INSERT INTO community_posts (user_id, title, content, status)
SELECT u.id,
       'Kenapa Saya Suka Membaca Review Sebelum Membeli Buku',
       'Review pembaca membantu saya memahami suasana buku tanpa harus terkena spoiler besar. Di Pustakata, rating dan ulasan membuat proses memilih buku terasa lebih tenang.',
       'published'
FROM users u
WHERE u.email = 'user@pustakata.test'
AND NOT EXISTS (SELECT 1 FROM community_posts WHERE title = 'Kenapa Saya Suka Membaca Review Sebelum Membeli Buku');
