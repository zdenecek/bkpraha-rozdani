<?php
declare(strict_types=1);
require_once __DIR__.'/settings.php';
function whatsapp_notification(string $message,array $settings,?callable $sender=null): bool {
    $key=unseal_wa_key($settings['key']);if($key==='')return false;
    try{
        if($sender!==null)return (bool)$sender($settings['phone'],$key,$message);
        if(!function_exists('curl_init'))return false;
        $url='https://api.callmebot.com/whatsapp.php?'.http_build_query(['phone'=>$settings['phone'],'text'=>$message,'apikey'=>$key],'','&',PHP_QUERY_RFC3986);
        $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>8,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
        $body=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
        return $code===200&&is_string($body)&&!preg_match('/\berror\b|invalid|not activated|failed/i',$body)&&preg_match('/queued|sent|success/i',$body)===1;
    }catch(Throwable $e){return false;}
}
function deliver_notification(string $subject,string $body,string $waBody,array $settings,?callable $mailSender=null,?callable $waSender=null): bool {
    $results=[];
    if($settings['email_enabled']){
        $headers="From: BK Praha <vybor@bkpraha.cz>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64";
        try{
            $send=$mailSender??static fn($to,$subject,$body,$headers)=>mail($to,$subject,$body,$headers);
            $email=$settings['email'];
            $results[]=filter_var($email,FILTER_VALIDATE_EMAIL)&&!preg_match('/[\r\n]/',$email)&&(bool)$send($email,'=?UTF-8?B?'.base64_encode($subject).'?=',chunk_split(base64_encode($body)),$headers);
        }catch(Throwable $e){$results[]=false;}
    }
    if($settings['wa_enabled'])$results[]=whatsapp_notification($waBody,$settings,$waSender);
    return $results!==[]&&!in_array(false,$results,true);
}
// Visitor values appear only in the email body, never in headers or WhatsApp.
function moderation_notification(string $kind,int $postId,array $deal,?array $comment=null,?callable $sender=null,?array $settings=null,?callable $waSender=null): bool {
    if(!in_array($kind,['post','comment'],true)||$postId<1)throw new InvalidArgumentException('Invalid notification');
    $settings??=notification_settings();
    if(!$settings['email_enabled']&&!$settings['wa_enabled'])return true;
    $subject=$kind==='post'?'BKP: nové rozdání ke schválení':'BKP: nový komentář ke schválení';
    $url=$kind==='post'?'https://rozdani.bkpraha.cz/editor.php?edit='.$postId:'https://rozdani.bkpraha.cz/komentare.php';
    $body="Rozdání: ".($deal['title']??'')."\nAutor rozdání: ".($deal['author']??'')."\n";
    if($comment!==null)$body.="Autor komentáře: ".$comment['author']."\n\n".$comment['body']."\n";
    $body.="\nČeká na schválení ve správě:\n".$url."\n\nBKP – Zajímavá rozdání\n";
    $ok=deliver_notification($subject,$body,$subject."\n".$url,$settings,$sender,$waSender);
    if(!$ok)error_log('BKP rozdani: moderation notification failed for '.$kind.' #'.$postId);
    return $ok;
}
function send_notification_test(): bool {
    return deliver_notification('BKP: test upozornění',"Test upozornění z BKP.\nhttps://rozdani.bkpraha.cz/admin.php", "BKP: test upozornění\nhttps://rozdani.bkpraha.cz/admin.php",notification_settings());
}
