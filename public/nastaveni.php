<?php
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/notifications.php';
if(!admin()){header('Location: admin.php');exit;}
$error='';$notice=$_SESSION['settings_notice']??'';unset($_SESSION['settings_notice']);
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf($_POST['csrf']??'');
    try{
        $action=$_POST['action']??'';
        if($action==='notifications'){
            save_notification_settings($_POST);$_SESSION['settings_notice']='Nastavení upozornění je uložené.';
        }elseif($action==='password'){
            limit('password-change',10);
            change_admin_password((string)($_POST['current_password']??''),(string)($_POST['new_password']??''),(string)($_POST['repeat_password']??''));
            session_regenerate_id(true);$_SESSION['admin_tag']=admin_session_tag();$_SESSION['csrf']=bin2hex(random_bytes(32));
            $_SESSION['settings_notice']='Heslo bylo změněno. Ostatní přihlášení správce jsou odhlášena.';
        }elseif($action==='test'){
            limit('notification-test',5);$result=send_notification_test();
            $_SESSION['settings_notice']=$result?'Test byl předán k odeslání. Doručení ověřte na telefonu nebo v e-mailu.':'Test se nepodařilo předat k odeslání. Zkontrolujte nastavení a dostupnost služby.';
        }else throw new InvalidArgumentException('Neplatná akce.');
        header('Location: nastaveni.php');exit;
    }catch(InvalidArgumentException $e){$error=$e->getMessage();}
}
$s=notification_settings();
?><!doctype html><html lang="cs"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Nastavení správy – BK Praha</title><link rel="stylesheet" href="style.css?v=20261005-settings"><body><?php require __DIR__.'/../app/header.php';?><main><h1>Nastavení správy</h1><nav class="toolbar"><a href="admin.php">← Zpět do administrace</a></nav>
<?php if($error):?><p class="feedback error" role="alert"><?=h($error)?></p><?php endif;?>
<?php if($notice):?><p class="feedback" role="status"><?=h($notice)?></p><?php endif;?>
<section class="panel settings-panel"><h2>Upozornění na nové příspěvky</h2><p>Vyberte, kam chcete dostávat upozornění na nové rozdání nebo komentář čekající na schválení.</p>
<form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="action" value="notifications"><input type="hidden" name="version" value="<?=h($s['version'])?>">
<label class="check-label"><input type="checkbox" name="email_enabled" <?=$s['email_enabled']?'checked':''?>> Zasílat na e-mail</label><div class="field"><label for="email">E-mailová adresa</label><input type="email" id="email" name="email" autocomplete="email" maxlength="254" value="<?=h($s['email'])?>"></div>
<label class="check-label"><input type="checkbox" name="wa_enabled" <?=$s['wa_enabled']?'checked':''?>> Zasílat na WhatsApp</label><div class="field"><label for="phone">Telefon pro WhatsApp</label><input type="tel" id="phone" name="phone" autocomplete="tel" placeholder="+420776145813" value="<?=h($s['phone'])?>"></div>
<details><summary>Aktivace WhatsAppu a změna správce</summary><p>Osobní upozornění na vlastní telefon posílá bezplatná služba CallMeBot. Služba obdrží vaše telefonní číslo a stručné upozornění s odkazem do správy; texty příspěvků ani jména autorů jí neposíláme. Doručení závisí na dostupnosti služby.</p><ol><li>Na <a href="https://www.callmebot.com/blog/free-api-whatsapp-messages/" target="_blank" rel="noopener noreferrer">stránce CallMeBot</a> ověřte aktuální aktivační číslo a podmínky.</li><li>Ze svého WhatsAppu odešlete botovi zprávu <strong>I allow callmebot to send me messages</strong>.</li><li>Klíč z odpovědi vložte do pole níže, zaškrtněte WhatsApp a uložte nastavení.</li></ol><p>Nový správce aktivuje své vlastní číslo. Při změně telefonu je nutný nový klíč.</p></details>
<div class="field"><label for="wa-key">Aktivační klíč CallMeBot</label><input type="password" id="wa-key" name="wa_key" autocomplete="off" maxlength="64" placeholder="<?=$s['key']!==''?'Klíč je uložený; prázdné pole ho ponechá':'Vložte klíč z aktivační zprávy'?>"><p class="hint">Uložený klíč se nikdy nezobrazuje. Nový vložte jen při aktivaci nebo změně čísla.</p></div><label class="check-label"><input type="checkbox" name="clear_key"> Odstranit uložený aktivační klíč</label><button class="primary">Uložit upozornění</button></form>
<form method="post" style="margin-top:18px"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="action" value="test"><button>Odeslat test podle uloženého nastavení</button></form></section>
<section class="panel settings-panel"><h2>Změnit heslo</h2><p>Použijte alespoň 14 znaků; vhodná je delší heslová fráze. Změna odhlásí ostatní přihlášení správce.</p><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="action" value="password">
<div class="field"><label for="current-password">Současné heslo</label><input type="password" id="current-password" name="current_password" autocomplete="current-password" required></div>
<div class="field"><label for="new-password">Nové heslo</label><input type="password" id="new-password" name="new_password" autocomplete="new-password" minlength="14" required></div>
<div class="field"><label for="repeat-password">Nové heslo znovu</label><input type="password" id="repeat-password" name="repeat_password" autocomplete="new-password" minlength="14" required></div>
<label class="check-label"><input type="checkbox" id="show-passwords"> Zobrazit zadávaná hesla</label><button class="primary">Změnit heslo</button></form></section>
</main><?php require __DIR__.'/../app/footer.php';?><script src="community.js?v=20261005-settings" defer></script></body></html>
