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
-- Spustit na MariaDB PŘED nasazením části s anketami a komentáři.
-- Stávající příspěvky ani data se nemění. Lze spustit opakovaně.
CREATE TABLE IF NOT EXISTS poll_votes (
 post_id BIGINT UNSIGNED NOT NULL,
 poll_key CHAR(64) NOT NULL,
 voter_key CHAR(64) NOT NULL,
 option_index TINYINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(post_id,poll_key,voter_key),
 CONSTRAINT poll_votes_post FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS comments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 post_id BIGINT UNSIGNED NOT NULL,
 request_key CHAR(64) NOT NULL UNIQUE,
 author VARCHAR(100) NOT NULL,
 body TEXT NOT NULL,
 status ENUM('pending','published','rejected') NOT NULL DEFAULT 'pending',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX comments_public(post_id,status,id),
 INDEX comments_moderation(status,id),
 CONSTRAINT comments_post FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Doplněk k již odeslané migraci ankety-komentare.
-- Spustit před nasazením hlavního vypínače diskusí.
CREATE TABLE IF NOT EXISTS app_settings (
 setting_key VARCHAR(64) PRIMARY KEY,
 setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Výchozí stav: diskuse vypnuté. Opakované spuštění zachová volbu správce.
INSERT INTO app_settings(setting_key,setting_value)
VALUES('discussions_enabled','0')
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);
