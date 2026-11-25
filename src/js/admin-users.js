/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
'use strict';
(() => {
 const root = document.querySelector('#users-manager'); if (!root) return;
 const get = id => root.querySelector('#users-'+id);
 const body = root.querySelector('tbody'), rows = Array.from(body.rows);
 const profiles = new Map(rows.map(r=>[r, JSON.parse(r.dataset.user)]));
 let selected = null, column = 0, ascending = true;
 const collator = new Intl.Collator('de', {numeric:true,sensitivity:'base'});
 function select(row) {
   selected = row;
   rows.forEach(r=>{r.classList.toggle('selected', r===row); r.querySelector('input').checked = r===row;});
   get('edit').disabled = !row;
   get('delete').disabled = !row || profiles.get(row).username===root.dataset.current;
 }
 function refresh() {
   const query=get('search').value.trim().toLocaleLowerCase('de'), role=get('role').value, status=get('status').value;
   let count=0;
   rows.forEach(r=>{const u=profiles.get(r); r.hidden=!(r.textContent.toLocaleLowerCase('de').includes(query)&&(!role||u.role===role)&&(!status||String(Number(u.active))===status)); if(!r.hidden) count++;});
   if(selected?.hidden) select(null);
   rows.sort((a,b)=> (ascending?1:-1)*collator.compare(a.cells[column+1].textContent,b.cells[column+1].textContent));
   rows.forEach(r=>body.append(r)); get('count').textContent=`${count} von ${rows.length} Benutzern`; get('empty').hidden=count!==0;
 }
 function edit(row) {
   const u=row?profiles.get(row):{}, f=get('form'), e=f.elements;
   f.reset(); e.action.value=row?'user-update':'user-create'; e.original_username.value=u.username||'';
   for(const key of ['username','first_name','last_name','email']) e[key].value=u[key]||'';
   e.role.value=u.role||'reader'; e.active.checked=row?u.active:true; e.password.required=!row;
   get('editor-title').querySelector('.hub-dialog-title-text').textContent=row?'Benutzer bearbeiten':'Benutzer erstellen'; get('editor').showModal(); e.username.focus();
 }
 rows.forEach(r=>{r.addEventListener('click',()=>select(r)); r.querySelector('input').addEventListener('change',()=>select(r)); r.addEventListener('dblclick',()=>edit(r));});
 for(const id of ['search','role','status']) get(id).addEventListener(id==='search'?'input':'change',refresh);
 root.querySelectorAll('[data-sort]').forEach(b=>b.addEventListener('click',()=>{const next=Number(b.dataset.sort); ascending=column===next?!ascending:true;column=next;root.querySelectorAll('th[aria-sort]').forEach(th=>th.setAttribute('aria-sort',th===b.parentElement?(ascending?'ascending':'descending'):'none'));refresh();}));
 get('new').addEventListener('click',()=>edit(null)); get('edit').addEventListener('click',()=>{if(selected)edit(selected);});
 get('delete').addEventListener('click',()=>{if(!selected||get('delete').disabled)return;const name=profiles.get(selected).username;get('confirm-text').textContent=`Benutzer „${name}“ endgültig löschen?`;get('confirm').querySelector('[name=original_username]').value=name;get('confirm').showModal();});
 root.querySelectorAll('[data-user-close]').forEach(b=>b.addEventListener('click',()=>b.closest('dialog').close())); refresh();
})();
