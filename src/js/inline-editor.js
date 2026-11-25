/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
(() => {
  const toggle=document.getElementById('inline-edit-toggle'); if(!toggle)return;
  const workspace=document.getElementById('inline-workspace'),read=document.getElementById('inline-read-content');
  const textarea=document.getElementById('inline-body'),preview=document.getElementById('inline-preview');
  const panels=document.getElementById('inline-panels'),status=document.getElementById('inline-status');
  const editPill=document.querySelector('.inline-edit-pill');
  const readActions=document.getElementById('page-heading-read-actions'),revision=document.getElementById('inline-revision');
  const csrf=document.getElementById('inline-csrf').value,slug=document.getElementById('inline-slug').value;
  let original=textarea.value,dirty=false,sequence=0,timeout,active=false,saving=false,lockToken="",heartbeatTimer=null,lockHealthy=false,heartbeatBusy=false,connectionNotice=null,connectionState="ok";
  const setStatus=(message,error=false)=>{status.textContent=message;status.style.color=error?'#b42318':'#64748b';};
  const params=action=>{const data=new FormData();data.set('action',action);data.set('csrf',csrf);data.set('slug',slug);data.set('body',textarea.value);data.set('revision',revision.value);data.set('lock_token',lockToken);return data;};
  const request=async action=>{const response=await fetch('/inline.php',{method:'POST',body:params(action),credentials:'same-origin'});const json=await response.json();if(!response.ok)throw new Error(json.error||'Aktion fehlgeschlagen.');return json;};
  const render=async()=>{const seq=++sequence;setStatus('Vorschau wird aktualisiert …');try{const data=await request('preview');if(seq!==sequence)return;preview.innerHTML=data.html;setStatus(dirty?'Ungespeicherte Änderungen':'Vorschau aktuell');if(window.Prism)window.Prism.highlightAllUnder(preview);}catch(e){if(seq===sequence)setStatus(e.message,true);}};
  const view=mode=>{panels.dataset.view=mode;document.querySelectorAll('[data-inline-view]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.inlineView===mode)));if(mode!=='edit')render();};
  const lockRequest=async(action,token=lockToken)=>{
    const data=new FormData();data.set('action',action);data.set('csrf',csrf);data.set('slug',slug);data.set('lock_token',token);
    const response=await fetch('/page-lock.php',{method:'POST',body:data,credentials:'same-origin'});
    const json=await response.json();if(!response.ok)throw new Error(json.error||'Sperre konnte nicht aktualisiert werden.');return json;
  };
  const notify=(message,type='info',duration)=>window.HomeHubFeedback?.notify(message,type,duration);
  const clearConnectionNotice=()=>{if(connectionNotice){connectionNotice.remove();connectionNotice=null;}};
  const connectionLost=()=>{
    lockHealthy=false;
    if(connectionState!=='offline'){
      clearConnectionNotice();
      connectionNotice=notify('Verbindung zu HomeHub unterbrochen. Deine Änderungen bleiben erhalten. Speichern ist vorübergehend nicht möglich.','error',0)||null;
    }
    connectionState='offline';
  };
  const lockInvalid=()=>{
    lockHealthy=false;
    if(connectionState!=='invalid'){
      clearConnectionNotice();
      connectionNotice=notify('Die Bearbeitungssperre ist nicht mehr gültig. Deine Änderungen bleiben erhalten. Bitte sichere deinen Text und öffne die Seite danach neu.','error',0)||null;
    }
    connectionState='invalid';
  };
  const connectionRestored=()=>{
    const wasOffline=connectionState==='offline';
    clearConnectionNotice();connectionState='ok';lockHealthy=true;
    setStatus(dirty?'Ungespeicherte Änderungen':'Keine Änderungen');
    if(wasOffline)notify('Verbindung wiederhergestellt. Du kannst weiterarbeiten und speichern.','success');
  };
  const stopHeartbeat=()=>{if(heartbeatTimer!==null)clearInterval(heartbeatTimer);heartbeatTimer=null;};
  const heartbeat=async()=>{if(!active||!lockToken||heartbeatBusy||connectionState==='invalid')return;heartbeatBusy=true;try{await lockRequest('heartbeat');if(active)connectionRestored();}catch(e){if(active){if(e instanceof TypeError||!navigator.onLine)connectionLost();else lockInvalid();}}finally{heartbeatBusy=false;}};
  const release=async()=>{stopHeartbeat();const token=lockToken;lockToken='';lockHealthy=false;if(token){try{await lockRequest('release',token);}catch(e){/* Lease expires server-side. */}}};
  // Den Platz zwischen sichtbarem Seitentitel und Viewport-Unterkante verwenden.
  // Auch nach Toolbar-Umbruch oder Fenstergrössenwechsel bleiben die Aktionen sichtbar.
  const fitWorkspace=()=>{
    if(!active)return;
    const top=workspace.getBoundingClientRect().top;
    const bottomGap=12;
    const available=Math.floor(window.innerHeight-top-bottomGap);
    workspace.style.setProperty('--inline-available-height',Math.max(260,available)+'px');
  };
  window.addEventListener('resize',fitWorkspace);
  let opening=false;
  toggle.addEventListener('click',async()=>{
    if(opening||active)return;opening=true;toggle.disabled=true;
    try{
      const result=await lockRequest('acquire');lockToken=result.lock_token;lockHealthy=true;
      connectionState='ok';clearConnectionNotice();active=true;workspace.hidden=false;read.hidden=true;readActions.hidden=true;document.body.classList.add('inline-edit-active');if(editPill)editPill.hidden=false;view('edit');document.querySelector('.page-heading')?.scrollIntoView({block:'start',behavior:'instant'});fitWorkspace();textarea.focus({preventScroll:true});
      heartbeatTimer=setInterval(heartbeat,30000);
    }catch(e){notify(e instanceof TypeError?'HomeHub ist momentan nicht erreichbar. Bitte Verbindung prüfen und erneut versuchen.':(e.message||'Diese Seite wird bereits bearbeitet.'),'error',8000);}
    finally{opening=false;toggle.disabled=false;}
  });
  document.addEventListener('visibilitychange',()=>{if(!document.hidden&&active)heartbeat();});
  window.addEventListener('online',()=>{if(active)heartbeat();});
  const close=()=>{clearConnectionNotice();connectionState='ok';active=false;workspace.hidden=true;read.hidden=false;readActions.hidden=false;document.body.classList.remove('inline-edit-active');if(editPill)editPill.hidden=true;workspace.style.removeProperty('--inline-available-height');};
  document.getElementById('inline-cancel').addEventListener('click',async()=>{
    if(dirty){const ok=window.HomeHubFeedback ? await window.HomeHubFeedback.confirm('Ungespeicherte Änderungen verwerfen?',{title:'Bearbeitung abbrechen',confirmLabel:'Verwerfen'}) : confirm('Ungespeicherte Änderungen verwerfen?');if(!ok)return;}
    textarea.value=original;dirty=false;await release();close();
  });
  document.querySelectorAll('[data-inline-view]').forEach(b=>b.addEventListener('click',()=>view(b.dataset.inlineView)));
  textarea.addEventListener('input',()=>{dirty=textarea.value!==original;if(connectionState==='ok')setStatus(dirty?'Ungespeicherte Änderungen':'Keine Änderungen');clearTimeout(timeout);if(panels.dataset.view!=='edit')timeout=setTimeout(render,400);});
  document.querySelectorAll('[data-inline-md]').forEach(b=>b.addEventListener('click',()=>window.HomeHubMarkdown.apply(textarea,b.dataset.inlineMd)));
  textarea.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&['b','i'].includes(e.key.toLowerCase())){e.preventDefault();window.HomeHubMarkdown.apply(textarea,e.key.toLowerCase()==='b'?'bold':'italic');}});
  document.getElementById('inline-save').addEventListener('click',async()=>{if(saving)return;if(!lockHealthy){notify('Speichern derzeit nicht möglich. Bitte warte auf die Wiederherstellung der Verbindung beziehungsweise öffne die Seite bei abgelaufener Sperre neu.','error',8000);return;}saving=true;setStatus('Speichern …');try{await request('save');dirty=false;original=textarea.value;await release();window.location.reload();}catch(e){setStatus(e.message,true);}finally{saving=false;}});
  window.addEventListener('beforeunload',event=>{if(active&&dirty){event.preventDefault();event.returnValue='';}});
  window.addEventListener('pagehide',()=>{
    stopHeartbeat();if(!lockToken)return;
    const data=new FormData();data.set('action','release');data.set('csrf',csrf);data.set('slug',slug);data.set('lock_token',lockToken);
    if(navigator.sendBeacon)navigator.sendBeacon('/page-lock.php',data);
  });
})();
