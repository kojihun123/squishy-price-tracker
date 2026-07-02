CREATE TABLE products (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(255) NOT NULL,
    brand        VARCHAR(100),
    image_url    VARCHAR(500),
    is_published TINYINT(1) DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO products (name, brand) VALUES
    ('젤리 대왕 말랑이', '스퀴시코'),
    ('복숭아 말랑이',   '몰랑'),
    ('곰돌이 슬로우라이징', '토이랜드');
