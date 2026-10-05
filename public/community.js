(()=>{
const toggle=document.getElementById('show-passwords');if(!toggle)return;
toggle.addEventListener('change',()=>{for(const id of ['current-password','new-password','repeat-password'])document.getElementById(id).type=toggle.checked?'text':'password';});
})();
(()=>{
if(!document.getElementById('toggle-password'))return;
const passwordInput=document.getElementById('password');
const passwordToggle=document.getElementById('toggle-password');
passwordToggle.hidden=false;
passwordToggle.addEventListener('click',()=>{
    const visible=passwordInput.type==='password';
    passwordInput.type=visible?'text':'password';
    passwordToggle.textContent=visible?'Skrýt heslo':'Zobrazit heslo';
    passwordToggle.setAttribute('aria-pressed',String(visible));
});
})();
(()=>{
const root=document.getElementById('community');if(!root)return;
const id=Number(root.dataset.id),poll=JSON.parse(root.dataset.poll),byId=id=>document.getElementById(id);
let pollState=null,after=0,showResults=false;
const message=byId('community-message');
async function request(action,data){const r=await fetch('community.php?action='+action,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({id,...data})});const result=await r.json();if(!r.ok)throw Error(result.error||'Odeslání se nezdařilo.');return result;}
function renderPoll(){if(!pollState)return;byId('vote-submit').disabled=pollState.mine!==null;byId('vote-options').disabled=pollState.mine!==null;byId('show-results').disabled=false;
const box=byId('poll-results');box.hidden=!(showResults||pollState.mine!==null);box.replaceChildren();
if(pollState.mine!==null){const p=document.createElement('p');p.textContent='Váš hlas: '+poll.options[pollState.mine];box.append(p);}
poll.options.forEach((option,i)=>{const row=document.createElement('div');row.className='poll-result';const label=document.createElement('div');const votes=pollState.counts[i];const pct=pollState.total?Math.round(100*votes/pollState.total):0;label.textContent=option+' — '+votes+' ('+pct+' %)';const bar=document.createElement('progress');bar.max=100;bar.value=pct;bar.setAttribute('aria-label',option);row.append(label,bar);box.append(row);});const total=document.createElement('p');total.textContent='Celkem hlasů: '+pollState.total;box.append(total);}
async function load(append=false){byId('community-retry').hidden=true;try{const r=await fetch('community.php?id='+id+'&after='+(append?after:0));const data=await r.json();if(!r.ok)throw Error(data.error||'Nepodařilo se načíst anketu a diskusi.');if(poll&&(!data.poll||JSON.stringify(data.poll.definition)!==JSON.stringify(poll)))throw Error('Anketa se mezitím změnila. Obnovte stránku.');pollState=data.poll;renderPoll();const discussion=byId('discussion');if(discussion)discussion.hidden=!data.discussionEnabled;const list=byId('comments');if(list){if(!append){list.replaceChildren();after=0;}for(const c of data.comments){const article=document.createElement('article');article.className='comment';const author=document.createElement('strong');author.textContent=c.author;const date=document.createElement('span');date.className='small';date.textContent=' · '+new Date(c.created_at.replace(' ','T')+'Z').toLocaleDateString('cs-CZ',{timeZone:'Europe/Prague'});const body=document.createElement('p');body.textContent=c.body;article.append(author,date,body);list.append(article);after=Number(c.id);}if(!after){const empty=document.createElement('p');empty.textContent='Zatím žádné schválené komentáře.';list.append(empty);}byId('comments-more').hidden=!data.hasMore;}message.textContent='';}catch(e){message.textContent=e.message;byId('community-retry').hidden=false;} }
byId('community-retry').onclick=()=>load();
if(poll){byId('show-results').onclick=()=>{showResults=true;renderPoll();};byId('vote-form').onsubmit=async e=>{e.preventDefault();if(!pollState)return;const choice=new FormData(e.target).get('option');if(choice===null)return;byId('vote-submit').disabled=true;try{await request('vote',{pollKey:pollState.key,option:Number(choice)});showResults=true;await load();}catch(err){message.textContent=err.message;byId('vote-submit').disabled=false;}};}
if(byId('comment-form')){byId('comments-more').onclick=async()=>{const b=byId('comments-more');b.disabled=true;await load(true);b.disabled=false;};byId('comment-form').onsubmit=async e=>{e.preventDefault();const b=e.target.querySelector('button');b.disabled=true;try{await request('comment',{author:byId('comment-author').value,body:byId('comment-body').value,website:byId('comment-website').value});byId('comment-body').value='';byId('comment-message').textContent='Děkujeme. Komentář čeká na schválení správcem.';}catch(err){byId('comment-message').textContent=err.message;}finally{b.disabled=false;}};}
load();
})();

