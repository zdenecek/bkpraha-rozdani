<?php
require __DIR__.'/../app/posts.php';
require __DIR__.'/../app/notifications.php';
function db(): PDO {static $p;return $p??=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}
function check(bool $v,string $label): void {if(!$v)throw new RuntimeException($label);}
db()->exec('PRAGMA foreign_keys=ON');
$config=['secret'=>str_repeat('test-secret-',4),'admin_password_hash'=>password_hash('original-test-password',PASSWORD_DEFAULT)];
db()->exec('CREATE TABLE app_settings(setting_key TEXT PRIMARY KEY,setting_value TEXT)');
db()->exec("INSERT INTO app_settings VALUES('discussions_enabled','1')");
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

$oldSession=['admin_until'=>time()+3600,'admin_tag'=>admin_session_tag()];
check(admin_session_valid($oldSession,time()),'original session valid');
try{change_admin_password('wrong','replacement-test-password','replacement-test-password');throw new RuntimeException('wrong password accepted');}catch(InvalidArgumentException $e){}
check(password_verify('original-test-password',admin_password_hash()),'failed change preserves old password');
try{change_admin_password('original-test-password','short','short');throw new RuntimeException('short password accepted');}catch(InvalidArgumentException $e){}
try{change_admin_password('original-test-password','replacement-test-password','different-password');throw new RuntimeException('mismatch accepted');}catch(InvalidArgumentException $e){}
change_admin_password('original-test-password','replacement-test-password','replacement-test-password');
check(password_verify('replacement-test-password',admin_password_hash()),'new password accepted');
check(!password_verify('original-test-password',admin_password_hash()),'old password rejected');
check(!admin_session_valid($oldSession,time()),'old sessions invalidated');
check(!admin_session_valid(['admin_until'=>time()+3600],time()),'legacy session invalid after change');
check(admin_session_valid(['admin_until'=>time()+3600,'admin_tag'=>admin_session_tag()],time()),'current session preserved');
$sealed=seal_wa_key('123456789');check(unseal_wa_key($sealed)==='123456789'&&!str_contains($sealed,'123456789'),'key encrypted');
check(unseal_wa_key(substr($sealed,0,-4).'AAAA')==='','tampered key rejected');
$settings=notification_settings();
save_notification_settings(['version'=>$settings['version'],'email_enabled'=>'on','email'=>'new-admin@example.com','phone'=>'+420 776 145 813','wa_enabled'=>'on','wa_key'=>'123456789']);
$settings=notification_settings();check($settings['wa_enabled']&&$settings['email_enabled']&&$settings['phone']==='+420776145813','choices and phone normalization saved');
$wa=[];$messages=[];
$waSender=function($phone,$key,$text)use(&$wa){$wa[]=[$phone,$key,$text];return true;};
check(moderation_notification('comment',2,$deal,['author'=>'Private name','body'=>'Private comment'],$sender,$settings,$waSender),'both channels sent');
check($messages[0][0]==='new-admin@example.com','new recipient used');
check(count($wa)===1&&$wa[0][0]==='+420776145813'&&!str_contains($wa[0][2],'Private')&&!str_contains($wa[0][2],'attacker'),'WA contains only generic notification');
$settings['email_enabled']=false;$messages=[];
check(moderation_notification('post',2,$deal,null,$sender,$settings,$waSender)&&$messages===[],'WA only does not send email');
$settings['wa_enabled']=false;$wa=[];
check(moderation_notification('post',2,$deal,null,$sender,$settings,$waSender)&&$wa===[]&&$messages===[],'disabled channels silent');
$before=notification_settings();
try{save_notification_settings(['version'=>'stale','email'=>'new-admin@example.com','phone'=>'+420776145813']);throw new RuntimeException('stale settings accepted');}catch(InvalidArgumentException $e){}
check(notification_settings()===$before,'stale settings leave choices intact');
try{save_notification_settings(['version'=>$before['version'],'email'=>"valid@example.com\r\nBcc: evil@example.com",'phone'=>'+420776145813']);throw new RuntimeException('header injection accepted');}catch(InvalidArgumentException $e){}
try{save_notification_settings(['version'=>$before['version'],'email'=>'new-admin@example.com','phone'=>'+420111222333','wa_enabled'=>'on']);throw new RuntimeException('old key reused with new phone');}catch(InvalidArgumentException $e){}
check(notification_settings()===$before,'phone change without key rolls back');
save_notification_settings(['version'=>$before['version'],'email'=>'new-admin@example.com','phone'=>'+420111222333']);
check(notification_settings()['key']===''&&!notification_settings()['wa_enabled'],'phone change clears key');
echo "PASS: password rotation, session invalidation, encrypted keys, contact settings and independent channels\n";
