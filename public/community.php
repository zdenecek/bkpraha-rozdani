<?php
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/posts.php';
require __DIR__.'/../app/community.php';
header('Content-Type: application/json; charset=utf-8');
$method=$_SERVER['REQUEST_METHOD'];$action=$_GET['action']??'';
if($method==='GET'){
    $post=published_post(max(0,(int)($_GET['id']??0)));if(!$post)fail('Rozdání není dostupné.',404);
    $deal=json_decode($post['payload'],true);$out=['poll'=>null,'comments'=>[],'hasMore'=>false,'discussionEnabled'=>!empty($deal['commentsEnabled'])&&discussions_enabled()];
    if(!empty($deal['poll'])){
        $poll=$deal['poll'];$key=poll_key($poll);$counts=array_fill(0,count($poll['options']),0);
        $q=db()->prepare('SELECT option_index,COUNT(*) AS votes FROM poll_votes WHERE post_id=? AND poll_key=? GROUP BY option_index');$q->execute([$post['id'],$key]);
        foreach($q as $row){$i=(int)$row['option_index'];if(isset($counts[$i]))$counts[$i]=(int)$row['votes'];}
        $mine=false;$reader=reader_key();
        if($reader){$q=db()->prepare('SELECT option_index FROM poll_votes WHERE post_id=? AND poll_key=? AND voter_key=?');$q->execute([$post['id'],$key,$reader]);$mine=$q->fetchColumn();}
        $out['poll']=['definition'=>$poll,'key'=>$key,'counts'=>$counts,'total'=>array_sum($counts),'mine'=>$mine===false?null:(int)$mine];
    }
    if($out['discussionEnabled']){
        $after=max(0,(int)($_GET['after']??0));
        $q=db()->prepare("SELECT id,author,body,created_at FROM comments WHERE post_id=? AND status='published' AND id>? ORDER BY id ASC LIMIT 21");$q->execute([$post['id'],$after]);$rows=$q->fetchAll();$out['hasMore']=count($rows)>20;$out['comments']=array_slice($rows,0,20);
    }
    echo json_encode($out,JSON_UNESCAPED_UNICODE);exit;
}
if($method!=='POST')fail('Nepodporovaná metoda.',405);
csrf($_SERVER['HTTP_X_CSRF_TOKEN']??'');
if(!in_array($action,['vote','comment'],true))fail('Neznámá akce.',404);
if($action==='comment'&&!discussions_enabled())fail('Správce dočasně vypnul diskuse na webu.',403);
$raw=file_get_contents('php://input',false,null,0,20001);if(strlen($raw)>20000)fail('Příliš dlouhý příspěvek.',413);
$v=json_decode($raw,true);if(!is_array($v))fail('Neplatná data.');
if(!empty($v['website']))fail('Příspěvek nebyl přijat.');
if($action==='comment'){
    try{$comment=validate_comment($v);}catch(InvalidArgumentException $e){fail($e->getMessage());}
}
limit($action,$action==='vote'?40:10);
$pdo=db();$pdo->beginTransaction();
if($action==='comment'&&!discussions_enabled(true)){$pdo->rollBack();fail('Správce dočasně vypnul diskuse na webu.',403);}
$q=$pdo->prepare("SELECT id,payload FROM posts WHERE id=? AND status='published' FOR UPDATE");$q->execute([max(0,(int)($v['id']??0))]);$post=$q->fetch();
if(!$post){$pdo->rollBack();fail('Rozdání není dostupné.',404);}
$deal=json_decode($post['payload'],true);
if($action==='vote'){
    $poll=$deal['poll']??null;
    if(!$poll||!is_string($v['pollKey']??null)||!hash_equals(poll_key($poll),$v['pollKey'])){$pdo->rollBack();fail('Anketa se změnila. Obnovte stránku.',409);}
    $option=$v['option']??null;
    if(!is_int($option)||!isset($poll['options'][$option])){$pdo->rollBack();fail('Vyberte možnost ankety.');}
    $reader=reader_key(true);
    $q=$pdo->prepare('INSERT INTO poll_votes(post_id,poll_key,voter_key,option_index) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE voter_key=VALUES(voter_key)');$q->execute([$post['id'],poll_key($poll),$reader,$option]);
}else{
    if(empty($deal['commentsEnabled'])){$pdo->rollBack();fail('Diskuse není zapnutá.',403);}
    $key=hash('sha256',$_SESSION['csrf'].'|'.$post['id'].'|'.json_encode($comment));
    $q=$pdo->prepare('INSERT INTO comments(post_id,request_key,author,body) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');$q->execute([$post['id'],$key,$comment['author'],$comment['body']]);
}
$pdo->commit();echo json_encode(['ok'=>true]);
