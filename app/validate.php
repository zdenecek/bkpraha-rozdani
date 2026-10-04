<?php
declare(strict_types=1);
// Authoritative validation: never trust the browser's checks.
function validate_deal(mixed $v): array {
    if (!is_array($v) || ($v['version']??null)!==1 || !in_array($v['dealer']??null,['N','E','S','W'],true) || !in_array($v['vul']??null,['none','NS','EW','both'],true)) throw new InvalidArgumentException('Neplatný formát rozdání.');
    $out=['version'=>1,'dealer'=>$v['dealer'],'vul'=>$v['vul'],'hands'=>[]];$used=[];
    foreach(['N','E','S','W'] as $seat){
        $count=0;
        foreach(['S','H','D','C'] as $suit){
            $text=$v['hands'][$seat][$suit]??null;
            if(!is_string($text)||strlen($text)>400)throw new InvalidArgumentException('Neplatné karty.');
            $text=preg_replace('/[\s,;–—-]/u','',str_replace('10','T',strtoupper($text)));
            if($text===null||preg_match('/[^AKQJT98765432]/',$text))throw new InvalidArgumentException('Karty obsahují neplatný znak.');
            foreach(str_split($text) as $rank){
                if(isset($used[$suit.$rank]))throw new InvalidArgumentException('Některá karta je zadaná vícekrát.');
                $used[$suit.$rank]=true; $count++;
            }
            $cards=str_split($text);usort($cards,fn($a,$b)=>strpos('AKQJT98765432',$a)<=>strpos('AKQJT98765432',$b));
            $out['hands'][$seat][$suit]=implode('',$cards);
        }
        if($count>13)throw new InvalidArgumentException('V jedné ruce je více než 13 karet.');
    }
    if(!$used)throw new InvalidArgumentException('Zadejte alespoň část rozdání.');
    foreach(['title'=>140,'author'=>100,'body'=>20000] as $field=>$max){
        $s=$v[$field]??null;
        if(!is_string($s)||!trim($s)||preg_match_all('/./us',$s)>$max)throw new InvalidArgumentException('Vyplňte název, jméno autora a text v povolené délce.');
        $out[$field]=trim($s);
    }
    $solution=$v['solution']??'';
    if(!is_string($solution)||preg_match_all('/./us',$solution)>20000)throw new InvalidArgumentException('Rozbor může mít nejvýše 20 000 znaků.');
    $out['solution']=trim($solution);
    $a=$v['auction']??null;
    if(!is_array($a)||!array_is_list($a)||count($a)>400)throw new InvalidArgumentException('Neplatná dražba.');
    $high=-1;$bidder=-1;$doubled=0;$passes=0;$closed=false;
    $dealer=array_search($v['dealer'],['N','E','S','W'],true);
    foreach($a as $i=>$call){
        if(!is_string($call)||$closed)throw new InvalidArgumentException('Neplatná dražba.');
        $seat=($dealer+$i)%4;
        if($call==='P')$passes++;
        else{
            $passes=0;
            if($call==='X'){
                if($high<0||$doubled!==0||$seat%2===$bidder%2)throw new InvalidArgumentException('Neplatné kontra.');
                $doubled=1;
            }elseif($call==='XX'){
                if($high<0||$doubled!==1||$seat%2!==$bidder%2)throw new InvalidArgumentException('Neplatné rekontra.');
                $doubled=2;
            }else{
                if(!preg_match('/^([1-7])(C|D|H|S|NT)$/D',$call,$m))throw new InvalidArgumentException('Neplatná hláška.');
                $value=((int)$m[1]-1)*5+array_search($m[2],['C','D','H','S','NT'],true);
                if($value<=$high)throw new InvalidArgumentException('Nedostatečný závazek.');
                $high=$value;$bidder=$seat;$doubled=0;
            }
        }
        $closed=$high<0?$passes>=4:$passes>=3;
    }
    $out['auction']=$a;return $out;
}
