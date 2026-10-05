<?php
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/posts.php';
require __DIR__.'/../app/community.php';
$currentYear=(int)(new DateTimeImmutable('now',new DateTimeZone('Europe/Prague')))->format('Y');
$years=publication_years($currentYear);
$year=archive_year($_GET['year']??null,$years,$currentYear);
$discussions=discussions_enabled();
$sort=archive_sort($_GET['sort']??null,$discussions);
$sortLabels=['newest'=>'Nejnovější rozdání','comments'=>'Nejvíce komentářů','activity'=>'Poslední aktivita v diskusi'];
$rows=community_archive_posts($year,$sort,$discussions);
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Zajímavá rozdání – BK Praha</title><link rel="stylesheet" href="style.css?v=20261005"></head><body>
<?php require __DIR__.'/../app/header.php'; ?>
<main class="home"><section class="home-intro"><div class="eyebrow">Od bridžového stolu</div><h1>Každé rozdání má svůj příběh</h1><p>Zajímavé okamžiky, otázky a postřehy našich hráčů. Vyberte si rozdání a zkuste najít vlastní řešení.</p><a class="button" href="editor.php">Přidat rozdání</a></section>
<section aria-labelledby="list-title"><h2 id="list-title">Seznam rozdání</h2>
<form method="get" class="archive-filters"><label>Rok<select name="year"><?php foreach($years as $option):?><option value="<?=$option?>" <?=$year===$option?'selected':''?>><?=$option?></option><?php endforeach;?><option value="all" <?=$year===null?'selected':''?>>Všechny roky</option></select></label><?php if($discussions):?><label>Seřadit podle<select name="sort"><?php foreach($sortLabels as $value=>$label):?><option value="<?=$value?>" <?=$sort===$value?'selected':''?>><?=h($label)?></option><?php endforeach;?></select></label><?php endif;?><button type="submit">Zobrazit</button></form>
<p class="post-meta"><?=$year===null?'Všechny roky':'Rok '.$year?> · Počet rozdání: <?=count($rows)?> · <?=h($sortLabels[$sort])?></p>
<?php foreach($rows as $row):$deal=json_decode($row['payload'],true); ?>
<article class="panel post-summary"><h2><a href="rozdani.php?id=<?=(int)$row['id']?>"><?=h($deal['title'])?></a></h2><p class="post-meta"><span><?=h($deal['author'])?></span><time datetime="<?=h(str_replace(' ','T',$row['published_at']).'Z')?>"><?=h(post_date($row['published_at']))?></time><?php if($discussions&&!empty($deal['commentsEnabled'])):?><span>Komentáře: <?=(int)$row['comment_count']?></span><?php if($row['last_comment']):?><span>Poslední komentář: <?=h(post_date($row['last_comment']))?></span><?php endif;endif;?></p><a class="read-deal" href="rozdani.php?id=<?=(int)$row['id']?>">Otevřít rozdání →</a></article>
<?php endforeach;if(!$rows): ?><p><?=$year===null?'Zatím tu není žádné zveřejněné rozdání. Buďte první!':'V tomto roce zatím není žádné zveřejněné rozdání.'?></p><?php endif; ?>
</section></main>
<?php require __DIR__.'/../app/footer.php'; ?></body></html>
