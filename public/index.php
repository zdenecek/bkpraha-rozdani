<?php
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/posts.php';
$page=max(1,min(20000,(int)($_GET['page']??1)));
$q=db()->prepare("SELECT id,payload,published_at FROM posts WHERE status='published' ORDER BY published_at DESC,id DESC LIMIT 6 OFFSET ?");
$q->bindValue(1,($page-1)*5,PDO::PARAM_INT);$q->execute();$rows=$q->fetchAll();$more=count($rows)>5;$rows=array_slice($rows,0,5);
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Zajímavá rozdání – BK Praha</title><link rel="stylesheet" href="style.css"></head><body>
<?php require __DIR__.'/../app/header.php'; ?>
<main class="home"><section class="home-intro"><div class="eyebrow">Od bridžového stolu</div><h1>Každé rozdání má svůj příběh</h1><p>Zajímavé okamžiky, otázky a postřehy našich hráčů. Vyberte si rozdání a zkuste najít vlastní řešení.</p><a class="button" href="editor.php">Přidat rozdání</a></section>
<section aria-labelledby="list-title"><h2 id="list-title">Seznam rozdání</h2>
<?php foreach($rows as $row):$deal=json_decode($row['payload'],true); ?>
<article class="panel post-summary"><h2><a href="rozdani.php?id=<?=(int)$row['id']?>"><?=h($deal['title'])?></a></h2><p class="post-meta"><span><?=h($deal['author'])?></span><time datetime="<?=h(str_replace(' ','T',$row['published_at']).'Z')?>"><?=h(post_date($row['published_at']))?></time></p><a class="read-deal" href="rozdani.php?id=<?=(int)$row['id']?>">Otevřít rozdání →</a></article>
<?php endforeach;if(!$rows): ?><p><?=$page===1?'Zatím tu není žádné zveřejněné rozdání. Buďte první!':'Na této stránce nejsou žádná rozdání.'?></p><?php endif; ?>
</section><nav class="pagination" aria-label="Stránky seznamu"><?php if($page>1):?><a class="button secondary" href="?page=<?=$page-1?>">← Předchozí stránka</a><?php endif;?><span>Strana <?=$page?></span><?php if($more):?><a class="button secondary" href="?page=<?=$page+1?>">Další stránka →</a><?php endif;?></nav></main>
<?php require __DIR__.'/../app/footer.php'; ?></body></html>
