USE perpustakaan_db;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role ENUM('admin', 'user') NOT NULL DEFAULT 'user' AFTER password;

UPDATE users
SET role = 'admin'
WHERE email = 'admin@pustakata.test';

INSERT INTO users (nama, email, password, role)
SELECT 'Admin Pustakata', 'admin@pustakata.test', '$2y$10$HQQmCs35QqI3oa4nNSMj3eVLnjGEFwzxE0iPDQ0uf9hDmgZfzeruW', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@pustakata.test');

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buku_id INT NOT NULL,
    reviewer_name VARCHAR(100) NOT NULL,
    rating TINYINT NOT NULL,
    komentar TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_buku
        FOREIGN KEY (buku_id) REFERENCES buku2(id)
        ON DELETE CASCADE,
    INDEX idx_reviews_buku (buku_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO reviews (buku_id, reviewer_name, rating, komentar)
SELECT id, 'Raka', 5, 'Bacaan kuat, rapi, dan meninggalkan banyak bahan renungan.'
FROM buku2
WHERE judul = 'Bumi Manusia'
AND NOT EXISTS (SELECT 1 FROM reviews WHERE reviewer_name = 'Raka');

INSERT INTO reviews (buku_id, reviewer_name, rating, komentar)
SELECT id, 'Maya', 4, 'Bahasanya indah dan tetap relevan untuk pembaca modern.'
FROM buku2
WHERE judul = 'Layar Terkembang'
AND NOT EXISTS (SELECT 1 FROM reviews WHERE reviewer_name = 'Maya');

INSERT INTO reviews (buku_id, reviewer_name, rating, komentar)
SELECT id, 'Dian', 5, 'Alurnya tenang tetapi emosinya terasa dekat.'
FROM buku2
WHERE judul = 'The Silent Echo'
AND NOT EXISTS (SELECT 1 FROM reviews WHERE reviewer_name = 'Dian');

INSERT INTO reviews (buku_id, reviewer_name, rating, komentar)
SELECT id, 'Nadia', 4, 'Cocok untuk pembaca yang suka misteri atmosferik.'
FROM buku2
WHERE judul = 'Moonlight Shadows'
AND NOT EXISTS (SELECT 1 FROM reviews WHERE reviewer_name = 'Nadia');

INSERT INTO reviews (buku_id, reviewer_name, rating, komentar)
SELECT id, 'Bagas', 5, 'Ringkas, praktis, dan mudah diterapkan.'
FROM buku2
WHERE judul = 'Master the Mindset'
AND NOT EXISTS (SELECT 1 FROM reviews WHERE reviewer_name = 'Bagas');

INSERT INTO reviews (buku_id, reviewer_name, rating, komentar)
SELECT id, 'Laras', 4, 'Puisinya sederhana tetapi terasa personal.'
FROM buku2
WHERE judul = 'Fragile Verses'
AND NOT EXISTS (SELECT 1 FROM reviews WHERE reviewer_name = 'Laras');
