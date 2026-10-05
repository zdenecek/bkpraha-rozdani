<?php
declare(strict_types=1);
function app_setting(string $key,?string $default=null): ?string {
    $q=db()->prepare('SELECT setting_value FROM app_settings WHERE setting_key=?');$q->execute([$key]);$value=$q->fetchColumn();return $value===false?$default:$value;
}
function put_setting(string $key,string $value): void {
    if(strlen($value)>255)throw new InvalidArgumentException('Nastavení je příliš dlouhé.');
    $q=db()->prepare('UPDATE app_settings SET setting_value=? WHERE setting_key=?');$q->execute([$value,$key]);
    if(app_setting($key)===null){$q=db()->prepare('INSERT INTO app_settings(setting_key,setting_value) VALUES(?,?)');$q->execute([$key,$value]);}
}
function lock_settings(): void {
    $q=db()->query("SELECT setting_value FROM app_settings WHERE setting_key='discussions_enabled'".(db()->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':''));
    if($q->fetchColumn()===false)throw new RuntimeException('Missing settings anchor');
}
function admin_password_hash(): string {global $config;return app_setting('admin_password_hash',$config['admin_password_hash']);}
function admin_session_tag(?string $hash=null): string {global $config;return hash_hmac('sha256',$hash??admin_password_hash(),$config['secret']);}
function admin_session_valid(array $session,int $now): bool {
    if(($session['admin_until']??0)<=$now)return false;
    if(isset($session['admin_tag']))return hash_equals(admin_session_tag(),$session['admin_tag']);
    return app_setting('admin_password_hash')===null;
}
function change_admin_password(string $current,string $new,string $repeat): void {
    if($new!==$repeat)throw new InvalidArgumentException('Nová hesla se neshodují.');
    if(!trim($new)||preg_match_all('/./us',$new)<14||strlen($new)>72)throw new InvalidArgumentException('Nové heslo musí mít alespoň 14 znaků a nejvýše 72 bajtů. Znaky s diakritikou zabírají více bajtů.');
    if($new===$current)throw new InvalidArgumentException('Zvolte jiné heslo než současné.');
    db()->beginTransaction();
    try{
        lock_settings();
        if(!password_verify($current,admin_password_hash()))throw new InvalidArgumentException('Současné heslo není správné.');
        $hash=password_hash($new,PASSWORD_DEFAULT);
        put_setting('admin_password_hash',$hash);db()->commit();
    }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
}
function notification_settings(): array {
    return ['email_enabled'=>app_setting('notify_email_enabled','1')==='1','email'=>app_setting('notify_email','vybor@bkpraha.cz'),
        'wa_enabled'=>app_setting('notify_wa_enabled','0')==='1','phone'=>app_setting('notify_phone','+420776145813'),
        'key'=>app_setting('notify_wa_key',''),'version'=>app_setting('notify_version','')];
}
function seal_wa_key(string $value): string {
    global $config;$iv=random_bytes(12);$tag='';$key=hash_hmac('sha256','whatsapp-key',$config['secret'],true);
    $cipher=openssl_encrypt($value,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);
    if($cipher===false)throw new RuntimeException('Encryption unavailable');return base64_encode($iv.$tag.$cipher);
}
function unseal_wa_key(string $value): string {
    global $config;$raw=base64_decode($value,true);if($raw===false||strlen($raw)<29)return '';
    $plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',hash_hmac('sha256','whatsapp-key',$config['secret'],true),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16));return $plain===false?'':$plain;
}
function save_notification_settings(array $input): void {
    $email=trim((string)($input['email']??''));$phone=preg_replace('/[\s()-]/','',(string)($input['phone']??''));$newKey=trim((string)($input['wa_key']??''));
    $emailOn=isset($input['email_enabled']);$waOn=isset($input['wa_enabled']);
    if(($email!==''||$emailOn)&&(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254||preg_match('/[\r\n]/',$email)))throw new InvalidArgumentException('Zadejte platnou e-mailovou adresu.');
    if(($phone!==''||$waOn)&&!preg_match('/^\+[1-9][0-9]{7,14}$/D',$phone))throw new InvalidArgumentException('Telefon zadejte v mezinárodním formátu, například +420776145813.');
    if($newKey!==''&&!preg_match('/^[A-Za-z0-9_-]{4,64}$/D',$newKey))throw new InvalidArgumentException('Aktivační klíč má neplatný formát.');
    db()->beginTransaction();
    try{
        lock_settings();$old=notification_settings();
        if(!hash_equals($old['version'],(string)($input['version']??'')))throw new InvalidArgumentException('Nastavení mezitím někdo změnil. Obnovte stránku.');
        $key=($phone===$old['phone']&&!isset($input['clear_key']))?$old['key']:'';
        if($newKey!=='')$key=seal_wa_key($newKey);
        if($waOn&&unseal_wa_key($key)==='')throw new InvalidArgumentException('Nejdřív aktivujte své číslo u CallMeBot a vložte aktivační klíč.');
        foreach(['notify_email_enabled'=>$emailOn?'1':'0','notify_email'=>$email,'notify_wa_enabled'=>$waOn?'1':'0','notify_phone'=>$phone,'notify_wa_key'=>$key,'notify_version'=>bin2hex(random_bytes(16))] as $name=>$value)put_setting($name,$value);
        db()->commit();
    }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
}
