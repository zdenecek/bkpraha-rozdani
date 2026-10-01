# Jak přispívat

Repozitář: https://github.com/zdenecek/bkpraha-rozdani. Web: https://rozdani.bkpraha.cz/

## Nasazení

Každý push do větve `main`, který mění `app/`, `public/` nebo `deploy-root/`, se automaticky nasadí na web. Nasazení předchází kontrola syntaxe PHP a `php tests/validate.php`. Když neprojde, nic se nenahraje.

Průběh nasazení: záložka Actions v repozitáři nebo `gh run list`.

## Prostředí

- WEDOS webhosting, PHP 8.3, MariaDB 10.11, Apache.
- Kořen webu je celá složka `/www/domains/rozdani.bkpraha.cz`. Soubor `deploy-root/.htaccess` směruje všechny požadavky do `public/`, takže `app/` není z webu dostupná.
- `app/config.php` se generuje při nasazení z GitHub secrets. Nikdy ho necommitovat a nepřidávat do repozitáře hesla.
- Nové veřejné soubory patří do `public/`, serverový kód do `app/`.

## Pravidla

1. Před pushem spustit lokálně:
   ```sh
   find app public tests -name '*.php' -print0 | xargs -0 -n1 php -l
   php tests/validate.php
   ```
2. Malé, samostatné commity s popisem, co a proč se mění.
3. Změny databáze se nenasazují automaticky. Když je potřeba nová tabulka nebo sloupec, upravit `schema.sql`, přidat soubor `migrations/RRRR-MM-DD-popis.sql` s příkazy `ALTER`/`CREATE` a dát vědět Zdeňkovi, který je spustí na serveru. Kód, který migraci potřebuje, pushnout až po jejím spuštění.
4. Po nasazení ověřit https://rozdani.bkpraha.cz/ a dotčenou funkci (formulář, správa, přehled).
5. Když se něco rozbije, vrátit poslední commit (`git revert`) a pushnout; tím se nasadí předchozí verze.
