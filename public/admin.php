<?php
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/community.php';
require __DIR__.'/../app/posts.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf($_POST['csrf']??'');
    if(($_POST['action']??'')==='delete'){
        if(!admin())fail('Přihlaste se do správy.',403);
        if(($_POST['confirm']??'')!=='delete')fail('Potvrďte trvalé smazání.');
        if(!delete_post((int)($_POST['id']??0),(int)($_POST['revision']??0)))fail('Rozdání mezitím někdo změnil nebo smazal. Obnovte stránku.',409);
        $_SESSION['admin_notice']='Rozdání bylo trvale smazáno včetně komentářů a hlasů v anketě.';
        header('Location: admin.php');exit;
    }
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
<form method="post" class="panel"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><label for="password">Heslo správce</label><input type="password" id="password" name="password" autocomplete="current-password" required><button type="button" id="toggle-password" aria-controls="password" aria-pressed="false" hidden>Zobrazit heslo</button><p><?=h($error)?></p><button class="primary">Přihlásit se</button></form>
<script>
const passwordInput=document.getElementById('password');
const passwordToggle=document.getElementById('toggle-password');
passwordToggle.hidden=false;
passwordToggle.addEventListener('click',()=>{
    const visible=passwordInput.type==='password';
    passwordInput.type=visible?'text':'password';
    passwordToggle.textContent=visible?'Skrýt heslo':'Zobrazit heslo';
    passwordToggle.setAttribute('aria-pressed',String(visible));
});
</script>
<?php else:
$status=$_GET['status']??'pending';if(!in_array($status,['pending','published','rejected'],true))$status='pending';
$page=max(0,(int)($_GET['page']??0));
$q=db()->prepare('SELECT id,payload,status,created_at FROM posts WHERE status=? ORDER BY id DESC LIMIT 20 OFFSET ?');$q->bindValue(1,$status);$q->bindValue(2,$page*20,PDO::PARAM_INT);$q->execute();$rows=$q->fetchAll();
$pendingPosts=(int)db()->query("SELECT COUNT(*) FROM posts WHERE status='pending'")->fetchColumn();
$pendingComments=(int)db()->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn();
$deleting=false;
if(isset($_GET['delete'])){$q=db()->prepare('SELECT id,payload,revision FROM posts WHERE id=?');$q->execute([(int)$_GET['delete']]);$deleting=$q->fetch();}
?>
<?php if(isset($_SESSION['admin_notice'])):?><p class="feedback" role="status"><?=h($_SESSION['admin_notice'])?></p><?php unset($_SESSION['admin_notice']);endif;?>
<?php if($deleting):$deleteDeal=json_decode($deleting['payload'],true);?>
<section class="panel"><h2>Trvale smazat rozdání?</h2><p><strong><?=h($deleteDeal['title'])?></strong> · <?=h($deleteDeal['author'])?></p><p>Smaže se celé rozdání, všechny jeho komentáře i hlasy v anketě. Smazání nelze vrátit; koš se nepoužívá.</p><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$deleting['id']?>"><input type="hidden" name="revision" value="<?=(int)$deleting['revision']?>"><button name="confirm" value="delete">Ano, trvale smazat rozdání</button> <a href="admin.php">Zrušit</a></form></section>
<?php endif;?>
<?php $discussions=discussions_enabled(); ?>
<section class="panel" aria-labelledby="discussion-setting"><h2 id="discussion-setting">Diskuse na celém webu</h2><p><strong><?=$discussions?'Povolené':'Vypnuté'?></strong> · Ankety fungují nezávisle.</p><p>Vypnutí skryje všechny diskuse a zastaví přijímání komentářů. Komentáře i nastavení jednotlivých rozdání zůstanou uložené. Opětovné povolení obnoví diskuse pouze u rozdání, která je mají zapnuté.</p><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="action" value="discussions"><input type="hidden" name="previous" value="<?=$discussions?'1':'0'?>"><input type="hidden" name="enabled" value="<?=$discussions?'0':'1'?>"><button class="primary"><?=$discussions?'Vypnout všechny diskuse':'Povolit diskuse na webu'?></button></form></section>
<section class="panel"><h2>Schvalování komentářů</h2><p>Čekají na schválení: <strong><?=$pendingComments?></strong>. Nový komentář neovlivní zveřejnění rozdání ani ostatních schválených komentářů.</p><a class="button" href="komentare.php">Otevřít schvalování komentářů</a></section>
<h2>Schvalování rozdání</h2><p>Čekají na schválení: <strong><?=$pendingPosts?></strong>. Otevřete rozdání, zvolte stav „Zveřejnit“ a uložte změny.</p><p class="hint">Upozornění na nová rozdání a komentáře se odesílají na vybor@bkpraha.cz.</p><nav class="toolbar" aria-label="Stav rozdání"><a href="?status=pending">Čekají na schválení</a> · <a href="?status=published">Zveřejněné</a> · <a href="?status=rejected">Odmítnuté</a></nav>
<?php foreach($rows as $r):$d=json_decode($r['payload'],true);?>
<article class="panel"><h2><?=h($d['title'])?></h2><p><?=h($d['author'])?></p><div class="actions"><a href="editor.php?edit=<?=(int)$r['id']?>">Otevřít, upravit a rozhodnout</a><a href="?delete=<?=(int)$r['id']?>">Trvale smazat rozdání</a></div></article>
<?php endforeach;if(!$rows):?><p>V této kategorii zatím žádný příspěvek není.</p><?php endif;?>
<nav><?php if($page):?><a href="?status=<?=h($status)?>&amp;page=<?=$page-1?>">Předchozí</a><?php endif;?> <?php if(count($rows)===20):?><a href="?status=<?=h($status)?>&amp;page=<?=$page+1?>">Další</a><?php endif;?></nav>
<form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="action" value="logout"><button>Odhlásit se</button></form>
<?php endif;?></main><?php require __DIR__.'/../app/footer.php'; ?></body></html>
