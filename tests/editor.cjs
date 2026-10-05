const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const source=fs.readFileSync('public/editor.js','utf8').split('try{const v=')[0];
const c=vm.createContext({document:{body:{dataset:{}}}});vm.runInContext(source,c);
vm.runInContext(`const original=empty();original.title='T';original.author='A';original.body='Text';original.solution='Rozbor';original.poll={question:'Otázka?',options:['A','B']};original.commentsEnabled=true;const roundtrip=checkImport(JSON.parse(JSON.stringify(original)));if(JSON.stringify(roundtrip)!==JSON.stringify(original))throw Error('Lost extended fields');delete original.solution;delete original.poll;delete original.commentsEnabled;const old=checkImport(original);if(old.solution!==''||old.poll!==null||old.commentsEnabled!==false)throw Error('Old JSON incompatible');`,c);
assert(source.includes("solution"));console.log('PASS: editor JSON roundtrip and old drafts');
