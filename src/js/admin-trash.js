/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
(() => {
  const root = document.getElementById('trash-manager');
  if (!root) return;
  const rows = [...root.querySelectorAll('[data-trash-id]')];
  const all = root.querySelector('#trash-select-all');
  const buttons = [...root.querySelectorAll('[data-trash-action]')];
  const dialog = root.querySelector('#trash-confirm');
  const form = dialog.querySelector('form');
  const check = root.querySelector('#trash-confirm-check');
  const submit = root.querySelector('#trash-submit');
  const selected = () => rows.filter(row => row.checked);
  const update = () => {
    const n = selected().length;
    all.checked = rows.length > 0 && n === rows.length;
    all.indeterminate = n > 0 && n < rows.length;
    rows.forEach(row => row.closest('tr').classList.toggle('selected', row.checked));
    buttons.forEach(button => { button.disabled = button.dataset.trashAction === 'trash-empty' ? !rows.length : !n; });
    root.querySelector('#trash-count').textContent = `${n} von ${rows.length} ausgewählt`;
  };
  all.addEventListener('change', () => { rows.forEach(row => { row.checked = all.checked; }); update(); });
  rows.forEach(row => {
    row.addEventListener('change', update);
    row.closest('tr').addEventListener('click', event => { if (event.target === row) return; row.checked = !row.checked; update(); });
  });
  buttons.forEach(button => button.addEventListener('click', () => {
    const action = button.dataset.trashAction;
    const restore = action === 'trash-restore';
    const empty = action === 'trash-empty';
    const items = empty ? rows : selected();
    if (!items.length) return;
    form.elements.action.value = action;
    const ids = root.querySelector('#trash-confirm-ids');
    ids.replaceChildren();
    if (!empty) items.forEach(row => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'ids[]'; input.value = row.value; ids.append(input); });
    const title = restore ? 'Wiederherstellen' : empty ? 'Papierkorb leeren' : 'Endgültig löschen';
    dialog.querySelector('.hub-dialog-title-text').textContent = title + '?';
    dialog.querySelector('.hub-dialog-header i').className = 'fa-solid ' + (restore ? 'fa-rotate-left' : 'fa-trash');
    root.querySelector('#trash-confirm-text').textContent = restore ? `${items.length} Einträge am ursprünglichen Ort wiederherstellen?` : empty ? 'Alle Einträge unwiderruflich löschen?' : `${items.length} ausgewählte Einträge unwiderruflich löschen?`;
    check.name = restore ? 'restore_confirm' : 'confirm';
    check.checked = false;
    root.querySelector('#trash-confirm-label').textContent = restore ? 'Aktuelle Kapitelberechtigungen berücksichtigt' : 'Endgültiges Löschen bestätigen';
    submit.textContent = title;
    submit.className = 'admin-btn ' + (restore ? 'primary' : 'danger');
    submit.disabled = true;
    dialog.showModal();
  }));
  check.addEventListener('change', () => { submit.disabled = !check.checked; });
  root.querySelector('#trash-cancel').addEventListener('click', () => dialog.close());
  update();
})();
