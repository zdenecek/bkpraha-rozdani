<?php
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/posts.php';
session_write_close();
$count=weekly_publication_count();
?>
<!doctype html>
<html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Zajímavá rozdání – novinky</title>
<style>
*{box-sizing:border-box}body{margin:0;background:transparent;color:#1a881a;font:16px/1.5 system-ui,-apple-system,Segoe UI,sans-serif}.card{display:block;padding:22px 24px;background:#f4fcf7;border:1px solid #c9ddcf;border-radius:12px;color:inherit;text-decoration:none}h2{margin:0 0 10px;font-size:26px;line-height:1.2}p{margin:0 0 12px}.count{font-weight:700;color:#1a881a}.action{display:inline-block;color:#1a881a;font-weight:650;text-decoration:underline;text-underline-offset:3px}.card:focus-visible{outline:3px solid #1a881a;outline-offset:-4px}@media(max-width:400px){.card{padding:18px}h2{font-size:24px}}
</style></head><body><a class="card" href="https://rozdani.bkpraha.cz/" target="_blank" rel="noopener noreferrer"><h2>Zajímavá rozdání</h2><p>Příběhy od bridžového stolu, otázky, ankety a diskuse.</p><p class="count"><?=h(weekly_publication_label($count))?></p><span class="action">Prohlédnout rozdání →</span></a></body></html>
