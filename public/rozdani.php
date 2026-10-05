<?php
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/posts.php';
require __DIR__.'/../app/community.php';
$post=published_post(max(0,(int)($_GET['id']??0)));
if(!$post)http_response_code(404);
$deal=$post?json_decode($post['payload'],true):null;
$discussionEnabled=$deal&&!empty($deal['commentsEnabled'])&&discussions_enabled();
if($deal&&!empty($deal['poll']))reader_key(true);
$newer=$post?post_neighbour($post,true):false;$older=$post?post_neighbour($post,false):false;
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($deal?$deal['title'].' – Zajímavá rozdání':'Rozdání není dostupné')?> – BK Praha</title><link rel="stylesheet" href="style.css?v=20261005"><meta name="csrf-token" content="<?=h($_SESSION['csrf'])?>"></head><body>
<?php require __DIR__.'/../app/header.php'; ?>
<main class="home deal-page"><a class="back-link" href="index.php">← Seznam rozdání</a>
<?php if(!$post):?><h1>Rozdání není dostupné</h1><p>Příspěvek nebyl zveřejněn nebo už není dostupný.</p>
<?php else:?>
<article class="panel preview" id="deal" data-deal="<?=h(json_encode($deal,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))?>"><h1><?=h($deal['title'])?></h1><p class="post-meta"><span>Autor: <?=h($deal['author'])?></span><time datetime="<?=h(str_replace(' ','T',$post['published_at']).'Z')?>"><?=h(post_date($post['published_at']))?></time></p><div class="board" id="board"></div><div id="auction"></div><noscript><p>Pro zobrazení diagramu a dražby zapněte JavaScript.</p></noscript><div class="prose"><?=h($deal['body'])?></div>
<?php if(trim($deal['solution']??'')!==''):?><details class="solution"><summary>Zobrazit rozbor / řešení</summary><div class="prose"><?=h($deal['solution'])?></div></details><?php endif;?>
</article>
<?php if(!empty($deal['poll'])||$discussionEnabled):?>
<section id="community" data-id="<?=(int)$post['id']?>" data-poll="<?=h(json_encode($deal['poll']??null,JSON_UNESCAPED_UNICODE))?>">
<?php if(!empty($deal['poll'])):?><section class="panel"><h2><?=h($deal['poll']['question'])?></h2><form id="vote-form"><fieldset id="vote-options"><legend>Vyberte jednu možnost</legend><?php foreach($deal['poll']['options'] as $i=>$option):?><label class="check-label"><input type="radio" name="option" value="<?=$i?>" required> <?=h($option)?></label><?php endforeach;?></fieldset><div class="actions"><button class="primary" id="vote-submit" disabled>Hlasovat</button><button type="button" id="show-results" disabled>Zobrazit výsledky</button></div></form><div id="poll-results" hidden aria-live="polite"></div><p class="hint">Jeden hlas z tohoto prohlížeče. Hlasování si pamatujeme pomocí cookie po dobu jednoho roku; nejde o ověření totožnosti.</p></section><?php endif;?>
<?php if($discussionEnabled):?><details id="discussion" class="panel discussion"><summary>Diskuse k rozdání</summary><p class="hint">Komentáře mohou obsahovat řešení. Zobrazují se až po schválení.</p><div id="comments"></div><button id="comments-more" type="button" hidden>Další komentáře</button><h3>Přidat komentář</h3><form id="comment-form"><label for="comment-author">Jméno</label><input id="comment-author" maxlength="100" autocomplete="name" required><label for="comment-body">Komentář</label><textarea id="comment-body" maxlength="3000" required></textarea><div class="trap" aria-hidden="true"><label for="comment-website">Web</label><input id="comment-website" tabindex="-1" autocomplete="off"></div><p class="hint">Odesláním souhlasíte se zveřejněním komentáře a uvedeného jména po schválení správcem.</p><button class="primary">Odeslat ke schválení</button><p id="comment-message" role="status"></p></form></details><?php endif;?>
<p id="community-message" role="status"></p><button id="community-retry" type="button" hidden>Zkusit načíst znovu</button></section>
<?php endif;?>
<nav class="deal-navigation" aria-label="Listování rozdáními">
<?php if($newer):$n=json_decode($newer['payload'],true);?><a href="rozdani.php?id=<?=(int)$newer['id']?>"><small>← Novější rozdání</small><span><?=h($n['title'])?></span></a><?php endif;?>
<?php if($older):$n=json_decode($older['payload'],true);?><a href="rozdani.php?id=<?=(int)$older['id']?>"><small>Starší rozdání →</small><span><?=h($n['title'])?></span></a><?php endif;?></nav><p><a href="index.php">Zpět na seznam rozdání</a></p>
<?php endif;?></main><?php require __DIR__.'/../app/footer.php'; ?><script src="deal.js?v=20261005" defer></script><script src="community.js?v=20261005" defer></script></body></html>
