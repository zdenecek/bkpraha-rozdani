<?php
require __DIR__.'/../app/community.php';
function check(bool $v,string $label):void{if(!$v)throw new RuntimeException($label);}
function rejects(callable $fn,string $label):void{try{$fn();}catch(InvalidArgumentException $e){return;}throw new RuntimeException($label);}
check(validate_poll(null)===null,'optional poll');
$p=validate_poll(['question'=>' Váš výnos? ','options'=>[' Pik ',' Srdce ']]);
check($p['question']==='Váš výnos?'&&$p['options']===['Pik','Srdce'],'normalize poll');
check(poll_key($p)!==poll_key(['question'=>$p['question'],'options'=>array_reverse($p['options'])]),'changed option order starts a new poll');
rejects(fn()=>validate_poll(['question'=>'Q','options'=>['A']]),'minimum options');
rejects(fn()=>validate_poll(['question'=>'Q','options'=>array_fill(0,9,'A')]),'maximum options');
rejects(fn()=>validate_poll(['question'=>'Q','options'=>[' A','A ']]),'duplicate trimmed options');
rejects(fn()=>validate_poll(['question'=>'Q','options'=>['A',[]]]),'option types');
rejects(fn()=>validate_poll(['question'=>'Q','options'=>['A',str_repeat('ž',201)]]),'option length');
rejects(fn()=>validate_comment(['author'=>'','body'=>'X']),'required name');
rejects(fn()=>validate_comment(['author'=>'A','body'=>str_repeat('x',3001)]),'comment limit');
check(validate_comment(['author'=>' A ','body'=>' <script>alert(1)</script> '])['body']==='<script>alert(1)</script>','text retained for safe escaped rendering');
echo "PASS: poll versions and comment validation\n";
