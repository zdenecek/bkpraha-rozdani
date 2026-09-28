# BK Praha – Zajímavá rozdání

Balíček pro Zdeňka Tomise. PHP 8.3+, MariaDB, PDO MySQL, HTTPS.

## Co aplikace dělá

- Samostatný formulář s kartami, dražbou a živým náhledem, v odsouhlaseném vzhledu.
- Návrhy se ukládají do prohlížeče a dají se exportovat do JSON.
- Import PBN s výběrem rozdání: karty, rozdávající, stav her a případná běžná dražba. Zachovává rozepsaný název, autora i text. Soubor se zpracovává v prohlížeči, neposílá se celý na server.
- Odeslaný příspěvek má stav „čeká na schválení“. Veřejnost ho nevidí.
- Správce ho otevře ve stejném editoru, upraví a zvolí zveřejnění nebo odmítnutí.
- Zveřejněné příspěvky se zobrazují od nejnovějšího. Odmítnutím lze zveřejněný příspěvek skrýt.
- Zároveň zůstává původní datum zveřejnění při pouhé úpravě článku.
- Neúplná rozdání jsou povolená; prázdnou barvu značí dlouhá pomlčka —. Duplicity, neplatné karty, více než 13 karet v ruce a neplatná dražba se odmítnou i na serveru.
- Opakování stejného odeslání v téže relaci nevytváří další příspěvek.
- Není zde odesílání e-mailů, registrace autorů ani komentáře. Potvrzení o přijetí se zobrazí ve formuláři. Správce sleduje čekající příspěvky ve správě.

## Instalace

1. Vytvořit samostatný web, například `rozdani.bkpraha.cz` (název je návrh, nikoli již existující služba).
2. Rozbalit celý balíček. Kořen webu MUSÍ směřovat pouze do složky `public/`. `app/`, `schema.sql` a tento návod musejí zůstat mimo veřejný kořen. Neumisťovat celý balíček do veřejně přístupné složky.
3. Vytvořit prázdnou databázi MariaDB a importovat `schema.sql`. Pro aplikaci vytvořit účet s právy SELECT, INSERT, UPDATE a DELETE jen pro tuto databázi.
4. Zkopírovat `app/config.example.php` do `app/config.php`. Doplnit DSN, jméno a heslo databáze. Soubor zpřístupnit jen správci serveru a PHP procesu.
5. Vygenerovat hash silného hesla správce a náhodný tajný klíč podle příkazů v konfiguraci. Nepoužívat uvedené zástupné hodnoty. Heslo předat Ondřejovi soukromě; balíček žádné heslo neobsahuje.
6. Zapnout HTTPS (je nutné pro zabezpečené cookies), přesměrování z HTTP a PHP sessions. U reverzní proxy zajistit odpovídající `REMOTE_ADDR` pro omezení počtu požadavků. Aplikace důvěřuje pouze této hodnotě, nikoli klientským X-Forwarded-For hlavičkám.
7. Zakázat výpis souborů v adresářích, zapnout běžné serverové logování chyb bez výpisu návštěvníkům. Nastavit zálohování databáze. Databázové časy jsou v UTC.
8. Otevřít `editor.php`, odeslat test; otevřít `admin.php`, přihlásit se, otevřít test, vybrat „Zveřejnit“ a uložit. Zkontrolovat úvodní stránku `index.php`. Nakonec test odmítnout. Postup ověřit v Chromu na PC a skutečném mobilu.

Správa vyžaduje heslo, trvá nejvýše 8 hodin a odhlašuje se tlačítkem. Souběžné úpravy se kontrolují číslem verze, aby se nepřepisovaly. Po chybě sítě lze odeslání zopakovat. Automatický limit je 15 nových odeslání a 20 pokusů o přihlášení z jedné IP za kalendářní hodinu. Při veřejném provozu sledovat spam; pro vyšší zátěž lze později přidat další ochranu.

## Samostatný web a odkaz z BKP

Cílová adresa je `https://rozdani.bkpraha.cz/` (doménu a hosting teprve nastaví Zdeněk). Úvodní `index.php` zobrazuje schválené příspěvky od nejnovějšího a tlačítko „Přidat rozdání“. Formulář je v `editor.php`, chráněná správa v `admin.php`.

Na hlavním webu BKP stačí odkaz v menu nebo tlačítko na tuto adresu. Do Webnode se nevkládá formulář ani seznam. Existující stránku ve Webnode lze ponechat jako rozcestník nebo později nahradit přímým odkazem. Balíček nijak nemění hlavní web.

Záhlaví a zápatí používají karetní motiv z výsledkového webu BKP. Obrázek je součástí balíčku (`public/suits.webp`), takže se při provozu nenačítá z cizí aplikace. Jeho zdroj: https://vysledky.bkpraha.cz/assets/suits-7baa86a1.webp . Světle zelené pozadí a odsouhlasené diagramy zůstávají zachované.

Kořen webu musí mít jako výchozí dokument `index.php`. Starý `feed.php` pouze přesměrovává na úvodní stránku. Ve správě se příspěvky otevírají v `editor.php?edit=ID`.

## Ověření před ostrým spuštěním

Tento balíček nebyl nasazen na Zdeňkův server. Zejména připojení MariaDB, konfigurace PHP, sessions přes HTTPS a fungování veřejné adresy vyžadují integrační kontrolu po instalaci. Nenasazovat jako hotovou ostrou službu bez ní.

Zkontrolovat: nepublikovaný příspěvek není v seznamu; uložení změn; zveřejnění; odmítnutí; opakované odeslání; odmítnutí chybné dražby a duplikované karty; odhlášení; chráněná správa. Veřejné texty se vykreslují jako text, nikoli HTML. Při chybě se zachovává rozepsaný formulář.

Pro ověření serverových kontrol lze před nasazením spustit `php tests/validate.php` a kontrolu syntaxe všech PHP souborů. JavaScript editoru a veřejného vykreslování prošel lokální kontrolou syntaxe a funkčními testy; PHP a MariaDB zde nebyly k dispozici, tyto testy je tedy nutné provést na hostingu nebo v jeho testovacím prostředí.


## Import PBN

V editoru kliknout na „Importovat PBN“, vybrat soubor, poté číslo rozdání a „Načíst vybrané rozdání“. Nahrazení již zadaných karet/dražby vyžaduje potvrzení. Zrušením se rozepsané zadání nemění. Maximum souboru je 5 MB.

Ověřeno na dodaném souboru skupinovky A: všech 28 rozdání obsahuje 52 jedinečných karet a načítá se se správnou orientací. Dodaný soubor nemá dražbu. Na dalších testovacích příkladech ověřeny dražby Pass/X/XX/AP, začátek tabulky u Westu, otočení pořadí rukou a převzetí hodnot označených # z předchozího záznamu.

Import nezahrnuje sehrávku, výsledky, double-dummy analýzy, poznámky ani alerty. Neplatná nebo nepodporovaná dražba se nahradí prázdnou s viditelným upozorněním, karty lze načíst samostatně. Chybné karty zablokují dané rozdání, ostatní zůstávají volitelné. Neúplná rozdání se načtou s upozorněním. Nejde o úplný editor všech rozšíření PBN.

Referenční specifikace: https://www.tistis.nl/pbn/pbn_v21.txt
