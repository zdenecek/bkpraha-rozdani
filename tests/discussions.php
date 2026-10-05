<?php
require __DIR__.'/../app/community.php';
function db(): PDO {static $p;return $p??=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);}
function check(bool $v,string $message):void {if(!$v)throw new RuntimeException($message);}
db()->exec('CREATE TABLE app_settings(setting_key TEXT PRIMARY KEY,setting_value TEXT)');
check(!discussions_enabled(),'missing setting defaults to closed');
db()->exec("INSERT INTO app_settings VALUES('discussions_enabled','0')");
check(!discussions_enabled(),'initially disabled');
check(change_discussions(true,false),'administrator enables');
check(discussions_enabled(),'enabled setting persists');
check(!change_discussions(true,false),'stale admin form rejected');
check(change_discussions(false,true),'administrator disables');
check(!discussions_enabled(),'disabled setting persists');
db()->exec("UPDATE app_settings SET setting_value='invalid'");
check(!discussions_enabled(),'invalid setting defaults to closed');
echo "PASS: global discussion default, toggle and stale-form protection\n";
