const EDIT_ID=Number(document.body.dataset.edit||0);

'use strict';
const seats=['N','E','S','W'],names={N:'North',E:'East',S:'South',W:'West'},suits=['S','H','D','C'],symbols={S:'♠',H:'♥',D:'♦',C:'♣'},ranks='AKQJT98765432',bidSuits=['C','D','H','S','NT'],key='bkp-rozdani-prototyp-v1';
const $=id=>document.getElementById(id), esc=x=>String(x).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const empty=()=>({version:1,hands:Object.fromEntries(seats.map(s=>[s,Object.fromEntries(suits.map(t=>[t,'']))])),dealer:'N',vul:'none',auction:[],title:'',author:'',body:'',solution:''});
let state=empty(),selected='N',level=1,storageOK=true;
function parse(v){let t=v.toUpperCase().replace(/10/g,'T').replace(/[\s,;–—-]/g,'');return {cards:[...t].filter(c=>ranks.includes(c)),invalid:[...t].filter(c=>!ranks.includes(c))};}
function count(s){return suits.reduce((n,t)=>n+parse(state.hands[s][t]).cards.length,0)}
function issues(){let errors=[],used={};for(const s of seats){if(count(s)>13)errors.push(names[s]+': více než 13 karet.');for(const t of suits){let p=parse(state.hands[s][t]);if(p.invalid.length)errors.push(names[s]+' '+symbols[t]+': neplatné znaky '+p.invalid.join('')+'.');for(const r of p.cards){let card=t+r;if(used[card])errors.push('Karta '+symbols[t]+r+' je zadaná vícekrát.');used[card]=s}}}return [...new Set(errors)]}
function auctionState(a=state.auction,dealer=state.dealer){let high=-1,lastBidder=-1,doubled=0,passes=0,closed=false;for(let i=0;i<a.length;i++){let x=a[i],seat=(seats.indexOf(dealer)+i)%4;if(x==='P')passes++;else{passes=0;if(x==='X')doubled=1;else if(x==='XX')doubled=2;else{high=(Number(x[0])-1)*5+bidSuits.indexOf(x.slice(1));lastBidder=seat;doubled=0}}closed=(high<0?passes>=4:passes>=3)}return {high,lastBidder,doubled,closed,next:(seats.indexOf(dealer)+a.length)%4}}
function allowed(x,a=state.auction,dealer=state.dealer){let z=auctionState(a,dealer);if(z.closed)return false;if(x==='P')return true;if(x==='X')return z.high>=0&&z.doubled===0&&z.next%2!==z.lastBidder%2;if(x==='XX')return z.high>=0&&z.doubled===1&&z.next%2===z.lastBidder%2;if(!/^[1-7](C|D|H|S|NT)$/.test(x))return false;return (Number(x[0])-1)*5+bidSuits.indexOf(x.slice(1))>z.high}
function checkImport(v){if(!v||v.version!==1||!seats.includes(v.dealer)||!['none','NS','EW','both'].includes(v.vul)||!Array.isArray(v.auction)||v.auction.length>400)throw Error('Nerozpoznaný formát souboru.');let n=empty();for(let s of seats)for(let t of suits){let x=v.hands?.[s]?.[t];if(typeof x!=='string'||x.length>100)throw Error('Neplatné údaje karet.');n.hands[s][t]=x}for(let x of v.auction){if(!allowed(x,n.auction,v.dealer))throw Error('Soubor obsahuje neplatnou dražbu.');n.auction.push(x)}for(let [f,max]of [['title',140],['author',100],['body',20000]]){if(typeof v[f]!=='string'||v[f].length>max)throw Error('Neplatný text příspěvku.');n[f]=v[f]}if(v.solution!==undefined&&(typeof v.solution!=='string'||v.solution.length>20000))throw Error('Neplatný rozbor.');n.solution=v.solution||'';n.dealer=v.dealer;n.vul=v.vul;return n}
try{const v=EDIT_ID?null:localStorage.getItem(key);if(v)state=checkImport(JSON.parse(v))}catch(e){storageOK=false}
function save(){if(EDIT_ID)return;try{localStorage.setItem(key,JSON.stringify(state));storageOK=true;$('saved').textContent='✓ Uloženo v tomto prohlížeči'}catch(e){storageOK=false;$('saved').textContent='Automatické uložení není dostupné. Použijte soubor.'}}
function syncFields(){for(let f of ['dealer','vul','title','author','body','solution'])$(f).value=state[f];renderEntries()}
function renderEntries(){ $('entries').innerHTML=suits.map(t=>`<div class="suit-entry"><label class="suit ${t==='H'||t==='D'?'red':''}" for="hand-${t}" aria-label="${{S:'Piky',H:'Srdce',D:'Kára',C:'Trefy'}[t]}">${symbols[t]}</label><input id="hand-${t}" maxlength="100" autocomplete="off" autocapitalize="characters" spellcheck="false" aria-label="${names[selected]} ${symbols[t]}" value="${esc(state.hands[selected][t])}" placeholder="—"></div>`).join('');for(let t of suits)$('hand-'+t).addEventListener('input',e=>{state.hands[selected][t]=e.target.value;update()})}
function fmt(x){if(x==='P')return 'Pass';if(x==='X')return '<span class="call-double">X</span>';if(x==='XX')return '<span class="call-redouble">XX</span>';let t=x.slice(1);return `${x[0]}<span class="${t==='H'||t==='D'?'red':''}">${symbols[t]||'NT'}</span>`}
function auctionTable(){if(!state.auction.length)return '<p class="small">Dražba zatím není zadaná.</p>';let z=auctionState(),offset=seats.indexOf(state.dealer),cells=Array(offset).fill(null).concat(state.auction);while(cells.length%4)cells.push(null);let rows='';for(let i=0;i<cells.length;i+=4)rows+='<tr>'+cells.slice(i,i+4).map(x=>`<td>${x===null?'·':fmt(x)}</td>`).join('')+'</tr>';return '<table class="auction"><thead><tr>'+seats.map((s,i)=>`<th class="${!z.closed&&z.next===i?'turn':''}">${names[s]}</th>`).join('')+'</tr></thead><tbody>'+rows+'</tbody></table>'}
function render(){ $('preview-solution').hidden=!state.solution.trim();$('preview-solution-body').textContent=state.solution; $('hands').innerHTML=seats.map(s=>`<button data-seat="${s}" class="${selected===s?'active':''}" aria-pressed="${selected===s}">${names[s]}<small>${count(s)} / 13</small></button>`).join('');let err=issues(),total=seats.reduce((n,s)=>n+count(s),0);$('validation').className='feedback'+(err.length?' error':'');$('validation').innerHTML=err.length?'<ul>'+err.map(e=>'<li>'+esc(e)+'</li>').join('')+'</ul>':total===52?'✓ Všech 52 karet je zadáno správně.':`Zadáno ${total} z 52 karet. Zbývá ${52-total}.`;$('fill').disabled=err.length>0||!seats.filter(s=>s!==selected).every(s=>count(s)===13);$('deck').innerHTML=suits.map(t=>`<div class="deck-row"><div class="${t==='H'||t==='D'?'red':''}">${symbols[t]}</div><div class="cards">${[...ranks].map(r=>{let owner=seats.find(s=>parse(state.hands[s][t]).cards.includes(r)),own=owner===selected;return `<button data-card="${t+r}" ${owner&&!own?'disabled':''} class="${own?'owned':''}" aria-pressed="${own}" aria-label="${symbols[t]} ${r}${owner?' – '+names[owner]:''}">${r}</button>`}).join('')}</div></div>`).join('');
$('levels').innerHTML=Array.from({length:7},(_,i)=>`<button data-level="${i+1}" class="${level===i+1?'active':''}" aria-pressed="${level===i+1}">${i+1}</button>`).join('');$('strains').innerHTML=bidSuits.map(t=>`<button data-bid="${level+t}" class="${t==='H'||t==='D'?'red':''}" ${allowed(level+t)?'':'disabled'} aria-label="${level} ${symbols[t]||'bez trumfů'}">${symbols[t]||'NT'}</button>`).join('');for(let [id,x]of [['pass','P'],['double','X'],['redouble','XX']])$(id).disabled=!allowed(x);$('undo').disabled=!state.auction.length;let z=auctionState();$('turn').textContent=z.closed?(z.high<0?'Rozdání bylo odpasováno.':'Dražba je ukončená.'):'Na řadě: '+names[seats[z.next]];$('auction-editor').innerHTML=auctionTable();$('preview-auction').innerHTML=state.auction.length?auctionTable():'';
$('preview-title').textContent=state.title||'Název vašeho příspěvku';$('preview-author').textContent=state.author?'Autor: '+state.author:'Jméno autora';$('preview-body').textContent=state.body||'Zde se objeví váš příběh, komentář nebo otázka k rozdání.';$('preview-body').classList.toggle('placeholder',!state.body);
$('board').innerHTML=seats.map(s=>`<div class="hand ${s.toLowerCase()}"><b><span class="${state.vul==='both'||state.vul.includes(s)?'vul':''}">${names[s]}</span>${state.dealer===s?' •':''}</b>${suits.map(t=>{let p=parse(state.hands[s][t]);let sorted=[...p.cards].sort((a,b)=>ranks.indexOf(a)-ranks.indexOf(b)).join('');return `<div class="holding"><span class="symbol ${t==='H'||t==='D'?'red':''}">${symbols[t]}</span><span class="ranks">${sorted||'—'}</span></div>`}).join('')}</div>`).join('')+`<div class="board-info"><strong>Dealer ${names[state.dealer]}</strong><span>${{none:'none',NS:'NS',EW:'EW',both:'all'}[state.vul]}</span></div>`}
function update(){$('toast').textContent='';save();render()}
function add(x){if(allowed(x)){state.auction.push(x);update()}}
$('hands').addEventListener('click',e=>{let b=e.target.closest('[data-seat]');if(b){selected=b.dataset.seat;renderEntries();render()}});$('levels').addEventListener('click',e=>{let b=e.target.closest('[data-level]');if(b){level=Number(b.dataset.level);render()}});$('strains').addEventListener('click',e=>{let b=e.target.closest('[data-bid]');if(b)add(b.dataset.bid)});
$('deck').addEventListener('click',e=>{let b=e.target.closest('[data-card]');if(!b||b.disabled)return;let [t,r]=b.dataset.card,p=parse(state.hands[selected][t]);if(p.invalid.length){$('toast').textContent='Nejdřív opravte neplatné znaky v této barvě.';return}if(p.cards.includes(r))p.cards=p.cards.filter(x=>x!==r);else if(count(selected)<13)p.cards.push(r);else{$('toast').textContent='V této ruce už je 13 karet.';return}state.hands[selected][t]=p.cards.sort((a,b)=>ranks.indexOf(a)-ranks.indexOf(b)).join('');$('toast').textContent='';renderEntries();update()});
$('fill').onclick=()=>{if($('fill').disabled)return;for(let t of suits)state.hands[selected][t]=[...ranks].filter(r=>!seats.filter(s=>s!==selected).some(s=>parse(state.hands[s][t]).cards.includes(r))).join('');renderEntries();update()};
for(let [id,x]of [['pass','P'],['double','X'],['redouble','XX']])$(id).onclick=()=>add(x);$('undo').onclick=()=>{state.auction.pop();update()};
$('dealer').onchange=e=>{if(state.auction.length&&!confirm('Změnou rozdávajícího se smaže zadaná dražba. Pokračovat?')){e.target.value=state.dealer;return}state.dealer=e.target.value;state.auction=[];update()};for(let f of ['vul','title','author','body','solution'])$(f).addEventListener('input',e=>{state[f]=e.target.value;update()});
function replace(n){state=n;selected='N';level=1;syncFields();update();$('toast').textContent=''}
function hasData(){return seats.some(s=>count(s)>0)||state.auction.length||state.title||state.author||state.body||state.solution}
$('reset').onclick=()=>{if(!hasData()||confirm('Vymazat rozpracované rozdání? Pokud ho chcete zachovat, nejprve ho uložte do souboru.'))replace(empty())};
$('sample').onclick=()=>{if(hasData()&&!confirm('Nahradit rozpracované rozdání ukázkou?'))return;let n=empty(),hands=['AKQJ.842.KQ3.762','987.AKQJ.842.KQ3','T65.T97.AJT9.AJ8','432.653.765.T954'];seats.forEach((s,i)=>suits.forEach((t,j)=>n.hands[s][t]=hands[i].split('.')[j]));n.auction=['1NT','P','3NT','P','P','P'];n.title='Kde hledat devátý zdvih?';n.author='Ukázkový příspěvek';n.body='North otevřel 1 NT a South zvýšil na 3 NT.\n\nJak byste si naplánovali sehrávku? Tady je prostor pro vlastní rozbor, zajímavý okamžik od stolu nebo otázku ostatním hráčům.';replace(n)};
$('export').onclick=()=>{let blob=new Blob([JSON.stringify(state,null,2)],{type:'application/json'}),a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download='BKP-rozdani.json';a.click();setTimeout(()=>URL.revokeObjectURL(a.href),1000);$('toast').textContent='Soubor obsahuje karty, dražbu i text. Zpět ho otevřete přes „Načíst ze souboru“.'};$('import').onclick=()=>$('file').click();$('file').onchange=async e=>{try{let file=e.target.files[0];if(!file)return;if(file.size>100000)throw Error('Soubor je příliš velký.');let n=checkImport(JSON.parse(await file.text()));if(!hasData()||confirm('Nahradit rozpracované rozdání obsahem souboru?'))replace(n)}catch(err){$('toast').textContent='Soubor nelze načíst: '+err.message}finally{e.target.value=''}};$('jump').onclick=()=>$('preview').scrollIntoView({behavior:'smooth'});
syncFields();render();$('saved').textContent=storageOK?'Rozpracované zadání se ukládá v tomto prohlížeči.':'Automatické uložení není dostupné. Použijte soubor.';
// PBN import: cards, dealer, vulnerability and ordinary legal auctions.
// Annotations/play/analysis are not imported; unusual auctions are reported.
function stripPbnComments(text){
 let out='',quoted=false,brace=false,line=false,escape=false;
 for(const c of text.replace(/^\uFEFF/,'')){
  if(line){if(c==='\n'){line=false;out+='\n'}continue}
  if(brace){if(c==='}')brace=false;else if(c==='\n')out+='\n';continue}
  if(quoted){out+=c;if(escape)escape=false;else if(c==='\\')escape=true;else if(c==='"')quoted=false;continue}
  if(c==='"'){quoted=true;out+=c}else if(c==='{'){brace=true;out+=' '}else if(c===';'||c==='%'){line=true;out+=' '}else out+=c;
 }
 if(quoted||brace)throw Error('Soubor obsahuje neukončený řetězec nebo komentář.');
 return out;
}
function pbnAuction(text,start,dealer){
 const a=[];let leading=0;
 const tokens=text.replace(/=\d+=|\$\d+/g,' ').trim().split(/\s+/).filter(Boolean);
 for(let token of tokens){
  if(token==='*'||token==='+')break;
  if(/^[!?]+$/.test(token))continue;
  token=token.replace(/[!?]+$/,'').toUpperCase();
  if(token==='-'&&!a.length){leading++;continue}
  if(!a.length&&(seats.indexOf(start)+leading)%4!==seats.indexOf(dealer))throw Error('Začátek dražby neodpovídá rozdávajícímu.');
  if(token==='AP'){
   if(auctionState(a,dealer).closed)throw Error('Dražba pokračuje po ukončení.');
   while(!auctionState(a,dealer).closed)a.push('P');
  }else{
   const call=token==='PASS'||token==='P'?'P':token;
   if(!allowed(call,a,dealer))throw Error('Nepodporovaná nebo neplatná hláška: '+token);
   a.push(call);
  }
  if(a.length>400)throw Error('Dražba je příliš dlouhá.');
 }
 return a;
}
function parsePbn(text){
 const clean=stripPbnComments(text),tag=/\[\s*([A-Za-z][A-Za-z0-9_]*)\s+"((?:\\.|[^"\\])*)"\s*\]/g;
 const games=[];let current={tags:{},auction:''},previous={},lastEnd=0,lastTag='',match;
 const starts=new Set(['event','site','date','round','board','west','north','east','south','dealer','vulnerable','deal']);
 function finish(){if(current.tags.deal!==undefined){games.push(current);previous={...previous,...current.tags};}current={tags:{},auction:''};}
 while((match=tag.exec(clean))){
  const name=match[1].toLowerCase(),between=clean.slice(lastEnd,match.index);
  if(lastTag==='auction')current.auction+=between;
  if(current.tags.deal!==undefined&&((name!=='note'&&current.tags[name]!==undefined)||(starts.has(name)&&/\n\s*\n/.test(between))))finish();
  let value=match[2].replace(/\\(["\\])/g,'$1');
  if(value==='#')value=previous[name]??'';
  current.tags[name]=value;lastTag=name;lastEnd=tag.lastIndex;
  if(games.length>2000)throw Error('Soubor má příliš mnoho rozdání.');
 }
 if(lastTag==='auction')current.auction+=clean.slice(lastEnd);
 finish();if(!games.length)throw Error('V souboru nejsou rozdání se značkou Deal.');
 return games.map((g,i)=>{
  const t=g.tags,result={label:'Rozdání '+(t.board&&t.board!=='?'?t.board:i+1),deal:null,warnings:[],error:''};
  try{
   const n=empty();n.dealer=(t.dealer||'').toUpperCase();
   if(!seats.includes(n.dealer))throw Error('Chybí platný rozdávající (Dealer).');
   const vul={NONE:'none',LOVE:'none',NS:'NS',EW:'EW',ALL:'both',BOTH:'both'}[(t.vulnerable||'').toUpperCase()];
   if(vul===undefined)throw Error('Chybí platný stav her (Vulnerable).');n.vul=vul;
   const m=/^([NESW]):\s*(.*)$/i.exec(t.deal.trim());if(!m)throw Error('Neplatný zápis Deal.');
   const hands=m[2].trim().split(/\s+/);if(hands.length!==4)throw Error('Deal musí obsahovat čtyři ruce.');
   const used=new Set();let total=0;
   hands.forEach((hand,j)=>{
    const seat=seats[(seats.indexOf(m[1].toUpperCase())+j)%4];
    if(hand==='-'){result.warnings.push(names[seat]+': neznámá ruka.');return}
    const parts=hand.toUpperCase().split('.');if(parts.length!==4)throw Error('Ruka nemá čtyři barvy oddělené tečkami.');
    let count=0;
    parts.forEach((part,k)=>{
     if(!/^[AKQJT98765432]*$/.test(part))throw Error('Neplatná karta v ruce '+names[seat]+'.');
     for(const rank of part){const card=suits[k]+rank;if(used.has(card))throw Error('Karta '+symbols[suits[k]]+rank+' je zadaná vícekrát.');used.add(card);count++;total++}
     n.hands[seat][suits[k]]=[...part].sort((a,b)=>ranks.indexOf(a)-ranks.indexOf(b)).join('');
    });
    if(count>13)throw Error(names[seat]+': více než 13 karet.');
   });
   if(total<52)result.warnings.push('Neúplné rozdání: '+total+' z 52 karet.');
   if(t.auction!==undefined){
    try{const start=t.auction.toUpperCase();if(!seats.includes(start))throw Error('Neplatný začátek dražby.');n.auction=pbnAuction(g.auction,start,n.dealer)}
    catch(e){result.warnings.push('Dražba se nenačte: '+e.message);n.auction=[];}
    if(/=\d+=|\$\d+|[!?]/.test(g.auction)||t.note)result.warnings.push('Poznámky a alerty k dražbě se nepřenášejí.');
   }
   result.deal=checkImport(n);
  }catch(e){result.error=e.message}
  return result;
 });
}
let pbnChoices=[];
$('import-pbn').onclick=()=>$('pbn-file').click();
$('pbn-file').onchange=async e=>{
 try{
  const file=e.target.files[0];if(!file)return;if(file.size>5000000)throw Error('Soubor je příliš velký (maximum 5 MB).');
  const bytes=await file.arrayBuffer();let text;
  try{text=new TextDecoder('utf-8',{fatal:true}).decode(bytes)}catch(e){text=new TextDecoder('windows-1250').decode(bytes)}
  pbnChoices=parsePbn(text);
  $('pbn-select').innerHTML=pbnChoices.map((r,i)=>'<option value="'+i+'"'+(r.error?' disabled':'')+'>'+esc(r.label+(r.error?' — nelze načíst':' — '+names[r.deal.dealer]+', '+({none:'none',NS:'NS',EW:'EW',both:'all'}[r.deal.vul])))+'</option>').join('');
  const first=pbnChoices.findIndex(r=>!r.error);$('pbn-select').value=String(first);$('pbn-load').disabled=first<0;
  $('pbn-panel').hidden=false;
  const invalid=pbnChoices.filter(r=>r.error);
  $('pbn-summary').textContent='Soubor: '+file.name+'. Nalezeno '+pbnChoices.length+' rozdání.'+(invalid.length?' Nelze načíst: '+invalid.map(r=>r.label+' ('+r.error+')').join('; '):'');
  $('pbn-select').onchange();$('pbn-panel').scrollIntoView({behavior:'smooth',block:'nearest'});
 }catch(err){$('toast').textContent='PBN nelze načíst: '+err.message;pbnChoices=[];$('pbn-panel').hidden=true}
 finally{e.target.value=''}
};
$('pbn-select').onchange=()=>{const r=pbnChoices[Number($('pbn-select').value)];$('pbn-warning').textContent=r?r.warnings.join(' '):'';};
$('pbn-cancel').onclick=()=>{$('pbn-panel').hidden=true;};
$('pbn-load').onclick=()=>{
 const r=pbnChoices[Number($('pbn-select').value)];if(!r?.deal)return;
 const hasCards=seats.some(s=>count(s)>0)||state.auction.length;
 if(hasCards&&!confirm('Nahradit karty a dražbu vybraným rozdáním z PBN? Název, autor a text příspěvku zůstanou zachované.'))return;
 const n=checkImport(r.deal);for(const f of ['title','author','body','solution'])n[f]=state[f];
 replace(n);$('pbn-panel').hidden=true;$('toast').textContent=r.label+' načteno. '+(r.warnings.join(' ')||'Karty, rozdávající a stav her jsou připravené.')+(n.auction.length?' Dražba je také načtená.':'');
};


const submitButton=$('submit-post');
let revision=0,editingReady=!EDIT_ID;
async function api(action,data){
 const response=await fetch('api.php?action='+action,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(data)});
 const result=await response.json();if(!response.ok)throw Error(result.error||'Odeslání se nezdařilo.');return result;
}
if(EDIT_ID){
 submitButton.disabled=true;
 fetch('api.php?action=edit&id='+EDIT_ID).then(async r=>{const d=await r.json();if(!r.ok)throw Error(d.error||'Příspěvek nelze načíst.');state=checkImport(d.deal);revision=d.revision;syncFields();render();editingReady=true;submitButton.disabled=false;$('post-status').value=d.status;$('saved').textContent='Úprava příspěvku ve správě.';}).catch(e=>$('submission-result').textContent=e.message);
}
submitButton.onclick=async()=>{
 if(!editingReady)return;
 const errors=issues();
 if(errors.length){$('submission-result').textContent=errors.join(' ');return;}
 if(!state.title.trim()||!state.author.trim()||!state.body.trim()){$('submission-result').textContent='Vyplňte název, jméno autora a text příspěvku.';return;}
 submitButton.disabled=true;$('submission-result').textContent='Ukládám…';
 try{
  const result=await api(EDIT_ID?'save':'submit',{deal:state,website:$('website').value,id:EDIT_ID,revision,status:EDIT_ID?$('post-status').value:undefined});
  if(EDIT_ID){revision=result.revision;$('submission-result').textContent='Změny jsou uložené.';}
  else $('submission-result').textContent='Děkujeme. Příspěvek byl přijat a čeká na schválení. Číslo příspěvku: '+result.id+'.';
 }catch(e){$('submission-result').textContent=e.message+' Vaše zadání zůstalo zachované. Odeslání můžete zopakovat.';}
 finally{submitButton.disabled=false;}
};
