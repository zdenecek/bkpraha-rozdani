<?php
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/posts.php';
require __DIR__.'/../app/community.php';
if(!admin()){header('Location: admin.php');exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf($_POST['csrf']??'');$status=$_POST['status']??'';
    if(!in_array($status,['published','rejected'],true))fail('Neplatný stav.');
    $q=db()->prepare('UPDATE comments SET status=? WHERE id=? AND status=?');$q->execute([$status,(int)($_POST['id']??0),$_POST['previous']??'']);
    if($q->rowCount()!==1)fail('Komentář mezitím někdo změnil. Obnovte stránku.',409);
    header('Location: komentare.php');exit;
}
$status=$_GET['status']??'pending';if(!in_array($status,['pending','published','rejected'],true))$status='pending';
$page=max(0,min(20000,(int)($_GET['page']??0)));
$q=db()->prepare('SELECT c.*,p.payload,p.status AS post_status FROM comments c JOIN posts p ON p.id=c.post_id WHERE c.status=? ORDER BY c.id DESC LIMIT 21 OFFSET ?');$q->bindValue(1,$status);$q->bindValue(2,$page*20,PDO::PARAM_INT);$q->execute();$rows=$q->fetchAll();$more=count($rows)>20;$rows=array_slice($rows,0,20);
?><!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Schvalování komentářů – BK Praha</title><link rel="stylesheet" href="style.css?v=20261005"></head><body><?php require __DIR__.'/../app/header.php';?><main class="home"><h1>Komentáře</h1><?php if(!discussions_enabled()):?><p class="feedback">Diskuse jsou na celém webu vypnuté. Můžete moderovat komentáře, veřejně se ale zobrazí až po opětovném povolení diskusí ve správě.</p><?php endif;?><p><a href="admin.php">← Správa rozdání</a></p><nav class="toolbar"><a href="?status=pending">Čekající</a><a href="?status=published">Zveřejněné</a><a href="?status=rejected">Skryté</a></nav>
<?php foreach($rows as $row):$deal=json_decode($row['payload'],true);?><article class="panel"><h2><?=h($deal['title'])?></h2><p><?=h($row['author'])?> · <?=h(post_date($row['created_at']))?></p><div class="prose"><?=h($row['body'])?></div><p><a href="editor.php?edit=<?=(int)$row['post_id']?>">Otevřít příspěvek ve správě</a></p><form method="post" class="actions"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="id" value="<?=(int)$row['id']?>"><input type="hidden" name="previous" value="<?=h($row['status'])?>"><?php if($row['status']!=='published'):?><button name="status" value="published" class="primary">Schválit</button><?php endif;?><?php if($row['status']!=='rejected'):?><button name="status" value="rejected">Odmítnout / skrýt</button><?php endif;?></form></article><?php endforeach;if(!$rows):?><p>Žádné komentáře v této kategorii.</p><?php endif;?><nav class="pagination"><?php if($page):?><a href="?status=<?=h($status)?>&amp;page=<?=$page-1?>">← Předchozí</a><?php endif;?><?php if($more):?><a href="?status=<?=h($status)?>&amp;page=<?=$page+1?>">Další →</a><?php endif;?></nav></main><?php require __DIR__.'/../app/footer.php';?></body></html>
