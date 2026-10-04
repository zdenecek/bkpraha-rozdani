<?php
// SQLite exercises the portable SELECT queries without production credentials.
require __DIR__.'/../app/posts.php';
function db(): PDO {static $p;return $p??=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}
function check(bool $condition,string $label): void {if(!$condition)throw new RuntimeException($label);}
db()->exec('CREATE TABLE posts(id INTEGER PRIMARY KEY,payload TEXT,status TEXT,published_at TEXT)');
$q=db()->prepare('INSERT INTO posts VALUES(?,?,?,?)');
foreach([[1,'published','2026-10-01 12:00:00'],[2,'pending','2026-10-02 12:00:00'],[3,'published','2026-10-03 12:00:00'],[4,'published','2026-10-03 12:00:00'],[5,'rejected','2026-10-04 12:00:00']] as [$id,$status,$date])$q->execute([$id,'{}',$status,$date]);
check(published_post(2)===false && published_post(5)===false && published_post(99)===false,'unpublished/missing inaccessible');
check(post_neighbour(published_post(3),true)['id']===4,'equal date uses id');
check(post_neighbour(published_post(3),false)['id']===1,'skip unpublished neighbours');
check(post_neighbour(published_post(4),true)===false,'newest boundary');
check(post_neighbour(published_post(1),false)===false,'oldest boundary');
check(post_date('2026-10-03 23:00:00')==='4. 10. 2026','Prague date');
echo "PASS: published visibility, navigation and dates\n";
