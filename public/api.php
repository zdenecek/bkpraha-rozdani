<?php
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/validate.php';
require __DIR__.'/../app/notifications.php';
header('Content-Type: application/json; charset=utf-8');
$action=$_GET['action']??'';
if($_SERVER['REQUEST_METHOD']==='GET'){
    if($action==='list'){
        $offset=max(0,min(100000,(int)($_GET['offset']??0)));
        $q=db()->prepare("SELECT id,payload,published_at FROM posts WHERE status='published' ORDER BY published_at DESC,id DESC LIMIT 10 OFFSET ?");
        $q->bindValue(1,$offset,PDO::PARAM_INT);$q->execute();
        $items=[];foreach($q as $row)$items[]=['id'=>(int)$row['id'],'deal'=>json_decode($row['payload'],true),'published_at'=>$row['published_at']];
        echo json_encode(['items'=>$items],JSON_UNESCAPED_UNICODE);exit;
    }
    if($action==='edit'&&admin()){
        $q=db()->prepare('SELECT id,payload,revision,status FROM posts WHERE id=?');$q->execute([(int)($_GET['id']??0)]);$row=$q->fetch();
        if(!$row)fail('Příspěvek neexistuje.',404);
        echo json_encode(['deal'=>json_decode($row['payload'],true),'revision'=>(int)$row['revision'],'status'=>$row['status']],JSON_UNESCAPED_UNICODE);exit;
    }
    fail('Nedostupná akce.',404);
}
if($_SERVER['REQUEST_METHOD']!=='POST')fail('Nepodporovaná metoda.',405);
csrf($_SERVER['HTTP_X_CSRF_TOKEN']??'');
$raw=file_get_contents('php://input',false,null,0,150001);
if(strlen($raw)>150000)fail('Příspěvek je příliš velký.',413);
$v=json_decode($raw,true);
if(!is_array($v))fail('Neplatná data.');
if(!in_array($action,['submit','save'],true))fail('Neznámá akce.',404);
if($action==='save'&&!admin())fail('Přihlaste se do správy.',403);
try{$deal=validate_deal($v['deal']??null);}catch(InvalidArgumentException $e){fail($e->getMessage());}
$payload=json_encode($deal,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
if($action==='submit'){
    if(!empty($v['website']))fail('Příspěvek nebyl přijat.');
    $requestKey=hash('sha256',$_SESSION['csrf'].'|'.$payload);
    $q=db()->prepare('SELECT id FROM posts WHERE request_key=?');$q->execute([$requestKey]);$existing=$q->fetchColumn();
    if($existing){echo json_encode(['ok'=>true,'id'=>(int)$existing]);exit;}
    limit('submit',15);
    $q=db()->prepare('INSERT INTO posts(request_key,payload) VALUES(?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');$q->execute([$requestKey,$payload]);
    $id=(int)db()->lastInsertId();
    $notified=$q->rowCount()===1?moderation_notification('post',$id,$deal):null;
    echo json_encode(['ok'=>true,'id'=>$id,'notificationAccepted'=>$notified]);exit;
}
$status=$v['status']??'';
if(!in_array($status,['pending','published','rejected'],true))fail('Neplatný stav.');
$q=db()->prepare("UPDATE posts SET payload=?,status=?,published_at=CASE WHEN ?='published' THEN COALESCE(published_at,UTC_TIMESTAMP()) ELSE published_at END,revision=revision+1 WHERE id=? AND revision=?");
$q->execute([$payload,$status,$status,(int)($v['id']??0),(int)($v['revision']??0)]);
if($q->rowCount()!==1)fail('Příspěvek mezitím někdo upravil. Obnovte stránku, aby se změny nepřepsaly.',409);
echo json_encode(['ok'=>true,'revision'=>(int)$v['revision']+1]);
