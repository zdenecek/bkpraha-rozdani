<?php
require __DIR__.'/../app/posts.php';
require __DIR__.'/../app/notifications.php';
function db(): PDO {static $p;return $p??=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}
function check(bool $v,string $label): void {if(!$v)throw new RuntimeException($label);}
db()->exec('PRAGMA foreign_keys=ON');
db()->exec('CREATE TABLE posts(id INTEGER PRIMARY KEY,payload TEXT,status TEXT,published_at TEXT,revision INTEGER)');
db()->exec('CREATE TABLE comments(id INTEGER PRIMARY KEY,post_id INTEGER REFERENCES posts(id) ON DELETE CASCADE,status TEXT)');
db()->exec('CREATE TABLE poll_votes(post_id INTEGER REFERENCES posts(id) ON DELETE CASCADE,option_index INTEGER)');
db()->exec("INSERT INTO posts VALUES(1,'{}','published','2026-10-05',3),(2,'{}','published','2026-10-05',1)");
db()->exec("INSERT INTO comments VALUES(1,1,'published'),(2,1,'pending'),(3,2,'published')");
db()->exec('INSERT INTO poll_votes VALUES(1,0),(2,1)');
check(!delete_post(1,2),'stale delete cannot remove edited post');
check(published_post(1)!==false,'stale delete leaves post intact');
check(delete_post(1,3),'matching revision deletes');
check(published_post(1)===false,'deleted post unavailable');
check((int)db()->query('SELECT COUNT(*) FROM comments WHERE post_id=1')->fetchColumn()===0,'all comments cascade');
check((int)db()->query('SELECT COUNT(*) FROM poll_votes WHERE post_id=1')->fetchColumn()===0,'votes cascade');
check((int)db()->query('SELECT COUNT(*) FROM comments WHERE post_id=2')->fetchColumn()===1,'other post comments intact');
check(!delete_post(1,3),'repeat delete rejected');
$messages=[];
$sender=function($to,$subject,$body,$headers)use(&$messages){$messages[]=[$to,$subject,base64_decode($body),$headers];return true;};
$deal=['title'=>"Název\r\nBcc: attacker@example.com",'author'=>'Autor'];
check(moderation_notification('post',2,$deal,null,$sender),'post notification accepted');
check(moderation_notification('comment',2,$deal,['author'=>'Komentátor','body'=>'Čekající text'],$sender),'comment notification accepted');
check($messages[0][0]==='vybor@bkpraha.cz','fixed recipient');
check(!str_contains($messages[0][1],"\r")&&!str_contains($messages[0][3],'attacker'),'visitor cannot inject headers');
check(str_contains($messages[0][2],'editor.php?edit=2'),'post moderation link');
check(str_contains($messages[1][2],'komentare.php')&&str_contains($messages[1][2],'Čekající text'),'comment link and body');
check(!moderation_notification('post',2,$deal,null,fn()=>false),'mail failure reported without throwing');
check(!moderation_notification('post',2,$deal,null,function(){throw new RuntimeException('mail unavailable');}),'mail exception does not lose submitted content');
echo "PASS: permanent delete, stale protection, cascade and notification routing/failure\n";
