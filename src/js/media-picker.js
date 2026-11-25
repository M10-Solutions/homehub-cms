/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
(() => {
  const dialog = document.getElementById('hub-media-picker');
  if (!dialog) return;
  const $ = id => document.getElementById(id);
  const folder = $('hub-media-folder'), search = $('hub-media-search'), grid = $('hub-media-items');
  const alt = $('hub-media-alt'), msg = $('hub-media-message'), insert = $('hub-media-insert');
  let target, selected = null, assets = [], folders = [], lastPosition = 0;
  const token = $('media-csrf')?.value || $('inline-csrf')?.value || '';
  const suggestion = () => {
    const slug = document.querySelector('input[name="slug"]')?.value || $('inline-slug')?.value || '';
    const parts = slug.split('/').filter(Boolean);
    return parts.slice(0, -1).join('/');
  };
  const setMessage = text => { msg.textContent = text; };
  async function request(form) {
    const response = await fetch('/media-api.php', {method:'POST', body:form, credentials:'same-origin'});
    const data = await response.json(); if (!response.ok) throw Error(data.error || 'Aktion fehlgeschlagen.'); return data;
  }
  async function load(preferred) {
    const response = await fetch('/media-api.php?folder=' + encodeURIComponent(preferred || ''), {credentials:'same-origin'});
    const data = await response.json(); if (!response.ok) throw Error(data.error || 'Medien konnten nicht geladen werden.');
    folders = data.folders; folder.replaceChildren();
    folders.forEach(f => { const opt = document.createElement('option'); opt.value=f; opt.textContent=f || 'Hauptordner'; folder.append(opt); });
    folder.value = folders.includes(preferred) ? preferred : '';
    if (folder.value !== (preferred || '')) return load(folder.value);
    assets = data.items.filter(x => x.image); selected=null; insert.disabled=true; render();
  }
  function render() {
    grid.replaceChildren();
    assets.filter(x => x.name.toLowerCase().includes(search.value.toLowerCase())).forEach(asset => {
      const button = document.createElement('button'); button.type='button'; button.className='hub-media-tile';
      if (selected?.path === asset.path) button.classList.add('selected');
      const img=document.createElement('img'); img.src=asset.url; img.alt=''; img.loading='lazy';
      const name=document.createElement('span'); name.textContent=asset.name;
      button.append(img,name); button.addEventListener('click',()=>{selected=asset;alt.value=asset.name.replace(/\.[^.]+$/,'').replace(/[-_]/g,' ');insert.disabled=false;render();});grid.append(button);
    });
    if (!grid.children.length) grid.textContent='Keine Bilder in diesem Ordner.';
  }
  document.querySelectorAll('[data-media-picker]').forEach(button => button.addEventListener('click', async () => {
    target = $(button.dataset.mediaTarget); if (!target) return;
    lastPosition=target.selectionStart; search.value=''; setMessage(''); dialog.showModal();
    try { await load(suggestion()); } catch(e) {setMessage(e.message);}
  }));
  document.querySelectorAll('[data-media-close]').forEach(b => b.addEventListener('click',()=>dialog.close()));
  folder.addEventListener('change',()=>load(folder.value).catch(e=>setMessage(e.message)));
  search.addEventListener('input',render);
  $('hub-media-upload').addEventListener('submit',async e=>{
    e.preventDefault(); const f=new FormData(e.currentTarget);f.set('action','upload');f.set('csrf',token);f.set('folder',folder.value);f.set('current_folder',folder.value);
    try {const before=new Set(assets.map(a=>a.path));const data=await request(f);await load(folder.value);const newest=data.items.find(x=>x.image && !before.has(x.path)); if(newest){selected=newest;insert.disabled=false;render();}e.currentTarget.reset();setMessage('Bild hochgeladen.');}catch(err){setMessage(err.message);}
  });
  $('hub-media-folder-form')?.addEventListener('submit',async e=>{
    e.preventDefault();const f=new FormData(e.currentTarget);f.set('action','folder');f.set('csrf',token);f.set('current_folder',f.get('folder'));
    try {await request(f);await load(String(f.get('folder')));e.currentTarget.reset();setMessage('Ordner angelegt.');}catch(err){setMessage(err.message);}
  });
  insert.addEventListener('click',()=>{
    if (!selected || !target) return;
    const description=alt.value.replace(/[\[\]\n\r]/g,' ').trim() || 'Bild';
    const markdown='!['+description+']('+selected.url+')';
    target.setRangeText(markdown,lastPosition,lastPosition,'end');target.dispatchEvent(new Event('input',{bubbles:true}));target.focus();dialog.close();
  });
})();