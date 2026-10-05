<?php
declare(strict_types=1);
function validate_poll(mixed $poll): ?array {
    if($poll===null)return null;
    if(!is_array($poll)||!is_string($poll['question']??null)||!trim($poll['question'])||preg_match_all('/./us',$poll['question'])>300)throw new InvalidArgumentException('Anketa potřebuje otázku do 300 znaků.');
    $options=$poll['options']??null;
    if(!is_array($options)||!array_is_list($options)||count($options)<2||count($options)>8)throw new InvalidArgumentException('Anketa potřebuje 2 až 8 možností.');
    $out=[];foreach($options as $option){
        if(!is_string($option)||!trim($option)||preg_match_all('/./us',$option)>200)throw new InvalidArgumentException('Možnost ankety musí mít 1 až 200 znaků.');
        $out[]=trim($option);
    }
    if(count(array_unique($out))!==count($out))throw new InvalidArgumentException('Možnosti ankety se nesmějí opakovat.');
    return ['question'=>trim($poll['question']),'options'=>$out];
}
function poll_key(array $poll): string {return hash('sha256',json_encode($poll,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));}
function validate_comment(mixed $input): array {
    if(!is_array($input))throw new InvalidArgumentException('Neplatný komentář.');
    $out=[];foreach(['author'=>100,'body'=>3000] as $field=>$max){
        $s=$input[$field]??null;
        if(!is_string($s)||!trim($s)||preg_match_all('/./us',$s)>$max)throw new InvalidArgumentException('Vyplňte jméno do 100 znaků a komentář do 3 000 znaků.');
        $out[$field]=trim($s);
    }return $out;
}
function reader_key(bool $create=false): ?string {
    global $config;
    $id=$_COOKIE['bkp_reader']??'';
    if(!is_string($id)||!preg_match('/^[a-f0-9]{64}$/D',$id)){
        if(!$create)return null;
        $id=bin2hex(random_bytes(32));
        setcookie('bkp_reader',$id,['expires'=>time()+365*86400,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
        $_COOKIE['bkp_reader']=$id;
    }
    return hash_hmac('sha256',$id,$config['secret']);
}

// Missing/invalid setting is closed by default. Comment writes lock this row
// until commit, so disabling discussions waits for any in-flight write.
function discussions_enabled(bool $lock=false): bool {
    $q=db()->prepare("SELECT setting_value FROM app_settings WHERE setting_key='discussions_enabled'".($lock?' FOR UPDATE':''));
    $q->execute();return $q->fetchColumn()==='1';
}
function change_discussions(bool $enabled,bool $previous): bool {
    $q=db()->prepare("UPDATE app_settings SET setting_value=? WHERE setting_key='discussions_enabled' AND setting_value=?");
    $q->execute([$enabled?'1':'0',$previous?'1':'0']);return $q->rowCount()===1;
}

function archive_sort(mixed $input,bool $enabled): string {
    return $enabled&&is_string($input)&&in_array($input,['comments','activity'],true)?$input:'newest';
}
function community_archive_posts(?int $year,string $sort,bool $enabled): array {
    $rows=archive_posts($year);
    if(!$enabled)return $rows;
    $stats=[];
    foreach(db()->query("SELECT c.post_id,COUNT(*) AS comment_count,MAX(c.created_at) AS last_comment FROM comments c JOIN posts p ON p.id=c.post_id WHERE c.status='published' AND p.status='published' GROUP BY c.post_id") as $stat)$stats[(int)$stat['post_id']]=$stat;
    foreach($rows as &$row){
        $deal=json_decode($row['payload'],true);
        $stat=!empty($deal['commentsEnabled'])?($stats[(int)$row['id']]??[]):[];
        $row['comment_count']=(int)($stat['comment_count']??0);
        $row['last_comment']=$stat['last_comment']??null;
    }unset($row);
    if(in_array($sort,['comments','activity'],true))usort($rows,static function(array $a,array $b)use($sort):int{
        $comparison=$sort==='comments'?($b['comment_count']<=>$a['comment_count']):strcmp($b['last_comment']??'',$a['last_comment']??'');
        return $comparison?:strcmp($b['published_at'],$a['published_at'])?:((int)$b['id']<=>(int)$a['id']);
    });
    return $rows;
}
