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
