<?php
require __DIR__.'/../app/validate.php';
$d=['version'=>1,'dealer'=>'N','vul'=>'both','title'=>'Test','author'=>'Autor','body'=>'Text','auction'=>['1NT','P','3NT','P','P','P']];
$hands=['AKQJ.842.KQ3.762','987.AKQJ.842.KQ3','T65.T97.AJT9.AJ8','432.653.765.T954'];
foreach(['N','E','S','W'] as $i=>$seat)$d['hands'][$seat]=array_combine(['S','H','D','C'],explode('.',$hands[$i]));
function ok(bool $v,string $label): void{if(!$v)throw new RuntimeException('FAIL: '.$label);}
function rejects(array $d,string $label):void{try{validate_deal($d);}catch(InvalidArgumentException $e){return;}throw new RuntimeException('FAIL: '.$label);}
ok(validate_deal($d)['hands']===$d['hands'],'complete deck');
$x=$d;$x['hands']['E']['S']='A87';rejects($x,'duplicate card');
$x=$d;$x['hands']['N']['H'].='T';rejects($x,'14 cards');
$x=$d;$x['hands']['N']['S']='Z';rejects($x,'invalid rank');
$x=$d;$x['auction']=['1S','1H'];rejects($x,'underbid');
$x=$d;$x['auction']=['X'];rejects($x,'double without contract');
$x=$d;$x['auction']=['1S','P','X'];rejects($x,'own-side double');
$x=$d;$x['auction']=['1S','X','XX','P','P','P'];ok(count(validate_deal($x)['auction'])===6,'legal redouble');
$x=$d;$x['auction'][]='P';rejects($x,'closed auction');
$x=$d;$x['title']='';rejects($x,'required title');
$x=$d;$x['hands']['N']['S']='A';ok(validate_deal($x)['hands']['N']['S']==='A','partial deal');
echo "PASS: server card and auction validation\n";

ok(validate_deal($d)['solution']==='', 'old articles remain valid');
$x=$d;$x['solution']=' Rozbor ';ok(validate_deal($x)['solution']==='Rozbor','solution preserved');
$x=$d;$x['solution']=[];rejects($x,'invalid solution type');
$x=$d;$x['solution']=str_repeat('a',20001);rejects($x,'solution length limit');
