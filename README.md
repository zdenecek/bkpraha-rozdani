# rozdani.bkpraha.cz

Zajímavá rozdání – BK Praha. PHP 8.3 + MariaDB. See [INSTALACE.md](INSTALACE.md).

## Deploy

Push to `main` runs lint + tests and uploads `app/` and `public/` via FTP. The web root must point to `public/`.

`app/config.php` is generated from secrets during deploy and is never committed.

Secrets: `FTP_SERVER`, `FTP_LOGIN`, `FTP_PASSWORD`, `DB_DSN`, `DB_USER`, `DB_PASSWORD`, `ADMIN_PASSWORD_HASH`, `APP_SECRET`.
Optional variable: `FTP_SERVER_DIR` (default `./`).

```sh
php -r 'echo password_hash(readline("Heslo: "), PASSWORD_DEFAULT), PHP_EOL;'   # ADMIN_PASSWORD_HASH
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'                              # APP_SECRET
```

Database schema (`schema.sql`) is imported manually once.
