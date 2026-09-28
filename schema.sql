CREATE TABLE posts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 request_key CHAR(64) NOT NULL UNIQUE,
 payload LONGTEXT NOT NULL,
 status ENUM('pending','published','rejected') NOT NULL DEFAULT 'pending',
 revision INT UNSIGNED NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 published_at DATETIME NULL,
 INDEX public_order(status,published_at,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE rate_limits (
 bucket CHAR(64) PRIMARY KEY,
 hits INT UNSIGNED NOT NULL,
 expires_at DATETIME NOT NULL,
 INDEX expiry(expires_at)
) ENGINE=InnoDB;
