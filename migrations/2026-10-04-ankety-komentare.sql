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
