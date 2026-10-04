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

foreach([[6,'published','2025-12-31 22:59:59'],[7,'published','2025-12-31 23:00:00'],[8,'published','2026-12-31 23:00:00'],[9,'pending','2024-06-01 12:00:00']] as [$id,$status,$date])$q->execute([$id,'{}',$status,$date]);
check(publication_years(2026)===[2027,2026,2025],'years include Prague boundary and exclude pending');
check(array_column(archive_posts(2026),'id')===[4,3,1,7],'Prague year uses inclusive start and exclusive end');
check(array_column(archive_posts(2025),'id')===[6],'previous year boundary');
check(count(archive_posts(null))===6,'all years are not limited to five');
check(archive_year('all',[2026],2026)===null,'all years selection');
check(archive_year(['2025'],[2026,2025],2026)===2026,'array input falls back safely');
check(archive_year('2025',[2026,2025],2026)===2025,'existing year selected');
check(archive_year('1900',[2026,2025],2026)===2026,'unknown year falls back');
echo "PASS: yearly archive, all years and timezone boundaries\n";
