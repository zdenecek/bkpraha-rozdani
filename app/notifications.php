<?php
declare(strict_types=1);

// No visitor-supplied value is used in mail headers or moderation URLs.
function moderation_notification(string $kind,int $postId,array $deal,?array $comment=null,?callable $sender=null): bool {
    if(!in_array($kind,['post','comment'],true)||$postId<1)throw new InvalidArgumentException('Invalid notification');
    $subject=$kind==='post'?'BKP: nové rozdání ke schválení':'BKP: nový komentář ke schválení';
    $url=$kind==='post'?'https://rozdani.bkpraha.cz/editor.php?edit='.$postId:'https://rozdani.bkpraha.cz/komentare.php';
    $body="Rozdání: ".($deal['title']??'')."\nAutor rozdání: ".($deal['author']??'')."\n";
    if($comment!==null)$body.="Autor komentáře: ".$comment['author']."\n\n".$comment['body']."\n";
    $body.="\nČeká na schválení ve správě:\n".$url."\n\nBKP – Zajímavá rozdání\n";
    $headers="From: BK Praha <vybor@bkpraha.cz>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64";
    try {
        $send=$sender??static fn($to,$subject,$body,$headers)=>mail($to,$subject,$body,$headers);
        $ok=(bool)$send('vybor@bkpraha.cz','=?UTF-8?B?'.base64_encode($subject).'?=',chunk_split(base64_encode($body)),$headers);
    }catch(Throwable $e){$ok=false;}
    if(!$ok)error_log('BKP rozdani: moderation notification failed for '.$kind.' #'.$postId);
    return $ok;
}
