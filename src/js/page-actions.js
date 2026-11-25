/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
(() => {
  const dialogs = document.querySelectorAll('dialog[data-page-dialog]');
  document.querySelectorAll('[data-open-page-dialog]').forEach(button => {
    button.addEventListener('click', () => {
      const dialog = document.getElementById(button.dataset.openPageDialog);
      if (dialog && !dialog.open) dialog.showModal();
    });
  });
  dialogs.forEach(dialog => {
    dialog.querySelectorAll('[data-close-page-dialog]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  });
  // ADMIN-001b: the server still receives the original validated target_slug.
  const moveFields = document.querySelector('[data-page-move-fields]');
  if (moveFields) {
    const folder = moveFields.querySelector('[data-move-folder]');
    const name = moveFields.querySelector('[data-move-name]');
    const target = moveFields.querySelector('[data-move-target]');
    const preview = moveFields.querySelector('[data-move-preview]');
    const error = moveFields.querySelector('[data-move-error]');
    const form = moveFields.closest('form');
    const update = () => {
      const value = folder.value ? folder.value + '/' + name.value.trim() : name.value.trim();
      const valid = /^[a-z0-9_-]+$/.test(name.value.trim()) && value.length <= 180;
      target.value = value;
      preview.textContent = value || '—';
      name.setCustomValidity(valid ? '' : 'Bitte einen gültigen Seitennamen (a-z, 0-9, - oder _) eingeben; maximal 180 Zeichen insgesamt.');
      error.hidden = valid;
      error.textContent = valid ? '' : name.validationMessage;
      return valid;
    };
    folder.addEventListener('change', update);
    name.addEventListener('input', update);
    form.addEventListener('submit', event => { if (!update()) { event.preventDefault(); name.reportValidity(); } });
    update();
  }
  // The styled page-delete dialog already confirms the action. Do not ask twice.
  // HomeHubFeedback.confirm remains available for single-step destructive actions.

})();

(() => {
  const root = document.querySelector('[data-tag-editor]');
  if (!root) return;
  const field = root.querySelector('#hub-tag-fallback');
  const input = root.querySelector('#hub-tag-input');
  const pills = root.querySelector('[data-tag-pills]');
  const options = root.querySelector('[role=listbox]');
  const status = root.querySelector('[data-tag-status]');
  const form = root.closest('form');
  const key = value => value.trim().toLowerCase();
  const known = JSON.parse(root.dataset.knownTags);
  const name = token => Object.hasOwn(known, token) ? known[token] : token;
  const initialTags = field.value.split(',').map(t => t.trim()).filter(Boolean);
  let tags = [...initialTags];
  let choices = [], active = -1;
  const close = () => { options.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); active = -1; };
  const render = () => {
    field.value = tags.join(', ');
    pills.replaceChildren();
    tags.forEach((tag, index) => {
      const chip = document.createElement('span'); chip.className = 'page-label-chip';
      chip.append(document.createTextNode(name(tag)));
      const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = '×'; remove.setAttribute('aria-label', `${name(tag)} entfernen`);
      remove.addEventListener('click', () => { tags.splice(index, 1); render(); input.focus(); });
      chip.append(remove); pills.append(chip);
    });
    status.textContent = `${tags.length} / 20 Schlagworte`;
    input.setCustomValidity('');
  };
  const add = value => {
    const typed = value.trim();
    const tag = Object.hasOwn(known, typed) ? typed : (Object.keys(known).find(id => known[id] === typed) ?? typed);
    if (!tag) return true;
    if (tag.includes(',') || [...tag].length > 40 || (tags.length >= 20 && !tags.includes(tag))) {
      input.setCustomValidity('Maximal 20 Schlagworte mit je 40 Zeichen, ohne Komma.'); input.reportValidity(); return false;
    }
    if (!tags.includes(tag)) tags.push(tag);
    input.value = ''; render(); close(); return true;
  };
  const highlight = () => {
    [...options.children].forEach((el, i) => el.setAttribute('aria-selected', String(i === active)));
    if (active >= 0) { input.setAttribute('aria-activedescendant', `hub-tag-option-${active}`); options.children[active].scrollIntoView({block:'nearest'}); }
    else input.removeAttribute('aria-activedescendant');
  };
  const suggest = () => {
    input.setCustomValidity('');
    const term = key(input.value);
    if (!term) { close(); return; }
    choices = Object.keys(known).filter(t => key(known[t]).includes(term) && !tags.includes(t)).slice(0, 12).map(t => ({tag:t, label:known[t]}));
    const typed = input.value.trim();
    if (!Object.values(known).includes(typed) && !tags.includes(typed) && !typed.includes(',')) choices.push({tag:typed,label:`+ «${typed}» hinzufügen`});
    options.replaceChildren(); active = -1;
    choices.forEach((choice,i) => {
      const el = document.createElement('div'); el.id = `hub-tag-option-${i}`; el.setAttribute('role','option'); el.setAttribute('aria-selected','false'); el.textContent = choice.label;
      el.addEventListener('pointerdown', e => e.preventDefault());
      el.addEventListener('click', () => { add(choice.tag); input.focus(); }); options.append(el);
    });
    options.hidden = !choices.length; input.setAttribute('aria-expanded', String(choices.length > 0));
    input.removeAttribute('aria-activedescendant');
  };
  input.addEventListener('input', suggest);
  input.addEventListener('focus', suggest);
  input.addEventListener('blur', close);
  input.addEventListener('keydown', e => {
    if (e.isComposing) return;
    if (e.key === 'Escape' && !options.hidden) { e.preventDefault(); e.stopPropagation(); close(); }
    else if (['ArrowDown','ArrowUp'].includes(e.key) && !options.hidden) { e.preventDefault(); active = (active < 0 ? (e.key === 'ArrowDown' ? 0 : choices.length - 1) : (active + (e.key === 'ArrowDown' ? 1 : -1) + choices.length) % choices.length); highlight(); }
    else if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); add(!options.hidden && active >= 0 ? choices[active].tag : input.value); }
  });
  form.addEventListener('submit', e => { if (!add(input.value)) e.preventDefault(); });
  root.closest('dialog').addEventListener('close', () => { tags = [...initialTags]; input.value = ''; render(); close(); });
  field.type = 'hidden'; root.querySelector('[data-tag-label]').htmlFor = input.id; root.querySelector('[data-tag-enhanced]').hidden = false; render();
})();
