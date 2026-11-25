/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
(() => {
  const copyFallback = value => {
    const previous = document.activeElement;
    const field = document.createElement('textarea');
    field.value = value; field.readOnly = true;
    field.style.cssText = 'position:fixed;left:-9999px;top:0';
    document.body.append(field); field.select();
    try { return document.execCommand('copy'); }
    finally { field.remove(); previous?.focus(); }
  };
  document.querySelectorAll('.copy-markdown').forEach(b => {
    let timer;
    b.addEventListener('click', async () => {
      let copied = false;
      try { copied = copyFallback(b.dataset.markdown); } catch {}
      if (!copied) {
        try { await navigator.clipboard.writeText(b.dataset.markdown); copied = true; } catch {}
      }
      clearTimeout(timer);
      b.querySelector('span').textContent = copied ? 'Kopiert' : 'Kopieren fehlgeschlagen';
      b.querySelector('i').className = copied ? 'fa-solid fa-check' : 'fa-solid fa-circle-exclamation';
      timer = setTimeout(() => {
        b.querySelector('span').textContent = 'Markdown kopieren';
        b.querySelector('i').className = 'fa-solid fa-copy';
      }, 2500);
    });
  });
  const zone = document.getElementById('drop-zone');
  if (zone) {
    const input = document.getElementById('media-upload-files');
    const uploadForm = document.getElementById('upload-form');
    const selection = document.getElementById('upload-selection');
    const empty = document.getElementById('upload-empty');
    const list = document.getElementById('upload-file-list');
    const count = document.getElementById('upload-count');
    const errorView = document.getElementById('upload-error');
    const actions = document.getElementById('upload-actions');
    const send = document.getElementById('upload-submit');
    const clear = document.getElementById('upload-clear');
    const syncUpload = () => {
      const files = [...input.files];
      const size = files.reduce((sum, file) => sum + file.size, 0);
      const error = files.length > 20 || size > 10 * 1024 * 1024 ? 'Maximal 20 Dateien und 10 MB insgesamt.' : '';
      input.setCustomValidity(error);
      empty.hidden = files.length > 0;
      selection.hidden = actions.hidden = !files.length;
      count.textContent = `${files.length} Datei(en) ausgewählt`;
      list.replaceChildren(...files.map((file, index) => {
        const item = document.createElement('li');
        const icon = document.createElement('i');
        const ext = file.name.split('.').pop().toLowerCase();
        icon.className = 'fa-solid ' + (ext === 'pdf' ? 'fa-file-pdf' : ext === 'txt' ? 'fa-file-lines' : 'fa-file-image');
        icon.setAttribute('aria-hidden', 'true');
        const name = document.createElement('span');
        name.textContent = file.name;
        const removeFile = document.createElement('button');
        removeFile.type = 'button'; removeFile.className = 'admin-btn upload-remove-file';
        removeFile.textContent = '×'; removeFile.setAttribute('aria-label', `${file.name} aus Auswahl entfernen`);
        removeFile.addEventListener('click', () => {
          try {
            const transfer = new DataTransfer();
            [...input.files].forEach((entry, i) => { if (i !== index) transfer.items.add(entry); });
            input.files = transfer.files; syncUpload();
            (list.querySelectorAll('button')[index] || list.querySelector('button') || input).focus();
          } catch { errorView.textContent = 'Einzelne Datei konnte nicht entfernt werden. Bitte Auswahl neu erstellen.'; errorView.hidden = false; }
        });
        item.append(icon, name, removeFile);
        return item;
      }));
      errorView.textContent = error;
      errorView.hidden = !error;
      send.disabled = !files.length || !!error;
      clear.hidden = !files.length;
    };
    input.addEventListener('change', syncUpload);
    clear.addEventListener('click', () => { input.value = ''; syncUpload(); input.focus(); });
    ['dragenter','dragover'].forEach(type => zone.addEventListener(type, e => { e.preventDefault(); zone.classList.add('dragover'); }));
    zone.addEventListener('dragleave', e => { if (!zone.contains(e.relatedTarget)) zone.classList.remove('dragover'); });
    zone.addEventListener('drop', e => {
      e.preventDefault(); zone.classList.remove('dragover');
      if (!e.dataTransfer.files.length) return;
      try { input.files = e.dataTransfer.files; syncUpload(); }
      catch { errorView.textContent = 'Dateien konnten nicht übernommen werden. Bitte die Auswahl löschen und die Dateiauswahl verwenden.'; errorView.hidden = false; }
    });
    uploadForm.addEventListener('submit', () => { send.disabled = true; send.querySelector('span').textContent = 'Wird hochgeladen …'; });
    syncUpload();
  }
  const filter = document.getElementById('media-filter');
  const cards = [...document.querySelectorAll('#media-manager-grid .media-card')];
  const checks = cards.map(card => card.querySelector('.media-select input'));
  const form = document.getElementById('media-bulk-form');
  if (!form) return;
  const move = document.getElementById('media-move-selected');
  const remove = document.getElementById('media-delete-selected');
  const selected = () => checks.filter(c => c.checked && !c.closest('.media-card').hidden);
  const sync = () => {
    const count = selected().length;
    document.getElementById('media-count').textContent = `${count} ausgewählt`;
    move.disabled = remove.disabled = count === 0;
    checks.forEach(c => c.closest('.media-card').classList.toggle('selected', c.checked));
  };
  checks.forEach(c => c.addEventListener('change', sync));
  filter?.addEventListener('input', () => {
    cards.forEach((card, i) => {
      card.hidden = !card.dataset.mediaName.includes(filter.value.toLowerCase());
      if (card.hidden) checks[i].checked = false;
    });
    sync();
  });
  document.getElementById('media-select-all').addEventListener('click', () => {
    checks.forEach(c => { c.checked = !c.closest('.media-card').hidden; }); sync();
  });
  document.getElementById('media-clear').addEventListener('click', () => { checks.forEach(c => c.checked = false); sync(); });
  const populate = () => {
    form.elements.selection.value = JSON.stringify(selected().map(c => c.value));
  };
  const radios = [...form.querySelectorAll('[name="target_folder"]')];
  const submit = document.getElementById('media-move-submit');
  const preview = document.getElementById('media-target-preview');
  const updateTarget = () => {
    const target = radios.find(r => r.checked);
    submit.disabled = !target || target.value === form.elements.folder.value;
    preview.textContent = target ? `Ziel: ${target.value || 'Hauptordner'}` + (submit.disabled ? ' (aktueller Ordner)' : '') : 'Bitte Zielordner auswählen.';
  };
  radios.forEach(r => r.addEventListener('change', updateTarget));
  document.querySelectorAll('[data-open-media]').forEach(button => button.addEventListener('click', () => {
    const dialog = document.getElementById(button.dataset.openMedia);
    if (dialog.id === 'rename-dialog') {
      const renameForm = dialog.querySelector('form');
      renameForm.elements.file.value = button.dataset.mediaPath;
      renameForm.elements.target_name.value = button.dataset.mediaFilename;
      dialog.querySelector('[data-current-name]').textContent = button.dataset.mediaFilename;
    }
    if (dialog.id === 'move-dialog') {
      if (!selected().length) return;
      populate(); form.elements.operation.value = 'move';
      radios.forEach(r => { r.checked = r.value === form.elements.folder.value; }); updateTarget();
    }
    dialog.showModal();
  }));
  document.querySelectorAll('[data-close-media]').forEach(b => b.addEventListener('click', () => b.closest('dialog').close()));
  remove.addEventListener('click', async () => {
    const count = selected().length;
    if (!count) return;
    const message = `${count} Medien endgültig löschen? Verwendete Dateien fehlen danach auf Wiki-Seiten.`;
    const ok = window.HomeHubFeedback ? await window.HomeHubFeedback.confirm(message,{title:'Medien löschen',confirmLabel:'Endgültig löschen'}) : confirm(message);
    if (!ok) return;
    populate(); form.elements.operation.value = 'delete';
    HTMLFormElement.prototype.submit.call(form);
  });
  form.addEventListener('submit', e => {
    if (!selected().length || submit.disabled) e.preventDefault();
  });
  sync();
})();
