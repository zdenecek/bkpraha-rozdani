<?php require __DIR__.'/../app/bootstrap.php'; ?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Zajímavá rozdání – BK Praha</title><link rel="stylesheet" href="style.css"></head><body>
<?php require __DIR__.'/../app/header.php'; ?>
<main class="home"><section class="home-intro"><h1>Každé rozdání má svůj příběh</h1><p>Zaujalo vás rozdání, které jste hráli nebo sledovali? Podělte se o něj s ostatními. Zadejte karty nebo je načtěte z PBN, případně přidejte dražbu a připojte svůj komentář nebo otázku.</p><p>Příspěvek se po schválení zobrazí na této stránce.</p><a class="button" href="editor.php">Přidat rozdání</a></section><div id="posts"></div><p id="message" role="status"></p><button id="more">Načíst další</button></main>
<?php require __DIR__.'/../app/footer.php'; ?>
<script src="feed.js"></script></body></html>
