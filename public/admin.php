<?php
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/community.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf($_POST['csrf']??'');
    if(($_POST['action']??'')==='logout'){
        $_SESSION=[];session_regenerate_id(true);header('Location: admin.php');exit;
    }
    if(($_POST['action']??'')==='discussions'){
        if(!admin())fail('Přihlaste se do správy.',403);
        $enabled=$_POST['enabled']??'';$previous=$_POST['previous']??'';
        if(!in_array($enabled,['0','1'],true)||!in_array($previous,['0','1'],true)||$enabled===$previous)fail('Neplatné nastavení diskusí.');
        if(!change_discussions($enabled==='1',$previous==='1'))fail('Nastavení mezitím změnil jiný správce. Obnovte stránku.',409);
        header('Location: admin.php');exit;
    }
    limit('login',20);
    if(password_verify($_POST['password']??'',$config['admin_password_hash'])){
        session_regenerate_id(true);$_SESSION['admin_until']=time()+8*3600;
        header('Location: admin.php');exit;
    }
    $error='Nesprávné heslo.';
}
?><!doctype html><html lang="cs"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Správa rozdání – BK Praha</title><link rel="stylesheet" href="style.css?v=20261005"><body><?php require __DIR__.'/../app/header.php'; ?><main><h1>Správa příspěvků</h1>
<?php if(!admin()): ?>
<form method="post" class="panel"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><label for="password">Heslo správce</label><input type="password" id="password" name="password" autocomplete="current-password" required><p><?=h($error)?></p><button class="primary">Přihlásit se</button></form>
<?php else:
$status=$_GET['status']??'pending';if(!in_array($status,['pending','published','rejected'],true))$status='pending';
$page=max(0,(int)($_GET['page']??0));
$q=db()->prepare('SELECT id,payload,status,created_at FROM posts WHERE status=? ORDER BY id DESC LIMIT 20 OFFSET ?');$q->bindValue(1,$status);$q->bindValue(2,$page*20,PDO::PARAM_INT);$q->execute();$rows=$q->fetchAll();
?>
<?php $discussions=discussions_enabled(); ?>
<section class="panel" aria-labelledby="discussion-setting"><h2 id="discussion-setting">Diskuse na celém webu</h2><p><strong><?=$discussions?'Povolené':'Vypnuté'?></strong> · Ankety fungují nezávisle.</p><p>Vypnutí skryje všechny diskuse a zastaví přijímání komentářů. Komentáře i nastavení jednotlivých rozdání zůstanou uložené. Opětovné povolení obnoví diskuse pouze u rozdání, která je mají zapnuté.</p><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="action" value="discussions"><input type="hidden" name="previous" value="<?=$discussions?'1':'0'?>"><input type="hidden" name="enabled" value="<?=$discussions?'0':'1'?>"><button class="primary"><?=$discussions?'Vypnout všechny diskuse':'Povolit diskuse na webu'?></button></form></section>
<p><a class="button" href="komentare.php">Schvalování komentářů</a></p><nav class="toolbar"><a href="?status=pending">Čekají na schválení</a> · <a href="?status=published">Zveřejněné</a> · <a href="?status=rejected">Odmítnuté</a></nav>
<?php foreach($rows as $r):$d=json_decode($r['payload'],true);?>
<article class="panel"><h2><?=h($d['title'])?></h2><p><?=h($d['author'])?></p><a href="editor.php?edit=<?=(int)$r['id']?>">Otevřít, upravit a rozhodnout</a></article>
<?php endforeach;if(!$rows):?><p>V této kategorii zatím žádný příspěvek není.</p><?php endif;?>
<nav><?php if($page):?><a href="?status=<?=h($status)?>&amp;page=<?=$page-1?>">Předchozí</a><?php endif;?> <?php if(count($rows)===20):?><a href="?status=<?=h($status)?>&amp;page=<?=$page+1?>">Další</a><?php endif;?></nav>
<form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="action" value="logout"><button>Odhlásit se</button></form>
<?php endif;?></main><?php require __DIR__.'/../app/footer.php'; ?></body></html>
