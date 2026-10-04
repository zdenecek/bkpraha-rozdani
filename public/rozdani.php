<?php
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/posts.php';
$post=published_post(max(0,(int)($_GET['id']??0)));
if(!$post)http_response_code(404);
$deal=$post?json_decode($post['payload'],true):null;
$newer=$post?post_neighbour($post,true):false;$older=$post?post_neighbour($post,false):false;
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($deal?$deal['title'].' – Zajímavá rozdání':'Rozdání není dostupné')?> – BK Praha</title><link rel="stylesheet" href="style.css"></head><body>
<?php require __DIR__.'/../app/header.php'; ?>
<main class="home deal-page"><a class="back-link" href="index.php">← Seznam rozdání</a>
<?php if(!$post):?><h1>Rozdání není dostupné</h1><p>Příspěvek nebyl zveřejněn nebo už není dostupný.</p>
<?php else:?>
<article class="panel preview" id="deal" data-deal="<?=h(json_encode($deal,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))?>"><h1><?=h($deal['title'])?></h1><p class="post-meta"><span>Autor: <?=h($deal['author'])?></span><time datetime="<?=h(str_replace(' ','T',$post['published_at']).'Z')?>"><?=h(post_date($post['published_at']))?></time></p><div class="board" id="board"></div><div id="auction"></div><noscript><p>Pro zobrazení diagramu a dražby zapněte JavaScript.</p></noscript><div class="prose"><?=h($deal['body'])?></div>
<?php if(trim($deal['solution']??'')!==''):?><details class="solution"><summary>Zobrazit rozbor / řešení</summary><div class="prose"><?=h($deal['solution'])?></div></details><?php endif;?>
</article>
<nav class="deal-navigation" aria-label="Listování rozdáními">
<?php if($newer):$n=json_decode($newer['payload'],true);?><a href="rozdani.php?id=<?=(int)$newer['id']?>"><small>← Novější rozdání</small><span><?=h($n['title'])?></span></a><?php endif;?>
<?php if($older):$n=json_decode($older['payload'],true);?><a href="rozdani.php?id=<?=(int)$older['id']?>"><small>Starší rozdání →</small><span><?=h($n['title'])?></span></a><?php endif;?></nav><p><a href="index.php">Zpět na seznam rozdání</a></p>
<?php endif;?></main><?php require __DIR__.'/../app/footer.php'; ?><script src="deal.js" defer></script></body></html>
