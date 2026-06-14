USE perpustakaan_db;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role ENUM('admin', 'user') NOT NULL DEFAULT 'user' AFTER password;

INSERT INTO users (nama, email, password, role)
SELECT 'Admin Pustakata', 'admin@pustakata.test', '$2y$10$HQQmCs35QqI3oa4nNSMj3eVLnjGEFwzxE0iPDQ0uf9hDmgZfzeruW', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@pustakata.test');

UPDATE users
SET role = 'admin'
WHERE email = 'admin@pustakata.test';
