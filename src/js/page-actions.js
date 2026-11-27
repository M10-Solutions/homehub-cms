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
  const create = document.querySelector('#hub-page-create form');
  if (create) {
    const slug = create.elements.slug;
    const error = create.querySelector('#hub-create-error');
    const submit = create.querySelector('[type=submit]');
    let timer, generation = 0, busy = false;
    const showError = message => { error.textContent = message; error.hidden = !message; };
    const request = async action => {
      const body = new FormData(create);
      body.set('page_action', action);
      const response = await fetch(create.action, {method: 'POST', body, headers: {Accept: 'application/json'}});
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.error || 'Aktion fehlgeschlagen.');
      return result;
    };
    const schedule = () => {
      clearTimeout(timer);
      const current = ++generation;
      showError('');
      submit.disabled = busy;
      if (!slug.validity.valid) return;
      timer = setTimeout(async () => {
        try { await request('check-create'); }
        catch (e) {
          if (current !== generation || busy) return;
          showError(e instanceof SyntaxError ? 'Prüfung nicht verfügbar. Beim Erstellen wird erneut geprüft.' : e.message);
          // Network failures must not prevent the authoritative create attempt.
          submit.disabled = !(e instanceof TypeError || e instanceof SyntaxError);
        }
      }, 300);
    };
    slug.addEventListener('input', schedule);
    create.closest('dialog').addEventListener('close', () => { clearTimeout(timer); ++generation; });
    document.querySelectorAll('[data-open-page-dialog="hub-page-create"]').forEach(button => button.addEventListener('click', schedule));
    create.addEventListener('submit', async event => {
      event.preventDefault();
      if (busy) return;
      clearTimeout(timer); ++generation; busy = true; submit.disabled = true; showError('');
      try { const result = await request('create'); window.location.assign(result.url); }
      catch (e) { showError(e instanceof SyntaxError ? 'Erstellen fehlgeschlagen. Bitte Anmeldung prüfen.' : e.message); }
      finally { busy = false; submit.disabled = false; }
    });
  }
  // ADMIN-001b: the server still receives the original validated target_slug.
  const moveFields = document.querySelector('[data-page-move-fields]');
  if (moveFields) {
    const folder = moveFields.querySelector('[data-move-folder]');
    const name = moveFields.querySelector('[data-move-name]');
    const target = moveFields.querySelector('[data-move-target]');
    const preview = moveFields.querySelector('[data-move-preview]');
    const error = moveFields.querySelector('[data-move-error]');
    const form = moveFields.closest('form');
    const tree = form.querySelector('[data-move-tree]');
    const aclHint = form.querySelector('[data-move-acl]');
    const initialFolder = folder.value;
    const dialog = form.closest('dialog');
    const submit = form.querySelector('[type=submit]');
    let timer, generation = 0, approved = null, busy = false;
    const showError = message => { error.textContent = message; error.hidden = !message; };
    const update = () => {
      const value = folder.value ? folder.value + '/' + name.value.trim() : name.value.trim();
      const valid = /^[a-z0-9_-]+$/.test(name.value.trim()) && value.length <= 180;
      target.value = value;
      preview.textContent = value || '—';
      aclHint.hidden = folder.value === initialFolder;
      name.setCustomValidity(valid ? '' : 'Bitte einen gültigen Seitennamen (a-z, 0-9, - oder _) eingeben; maximal 180 Zeichen insgesamt.');
      return valid && name.validity.valid;
    };
    const signature = () => JSON.stringify([target.value, !!tree?.checked]);
    const schedule = () => {
      clearTimeout(timer);
      const current = ++generation;
      approved = null;
      submit.disabled = true;
      showError('');
      if (!update()) { showError(name.validationMessage); return; }
      if (target.value === form.elements.slug.value) return;
      timer = setTimeout(async () => {
        const checked = signature();
        try {
          const body = new FormData(form);
          body.set('page_action', 'check-move');
          const response = await fetch(form.action, {method: 'POST', body, headers: {Accept: 'application/json'}});
          const result = await response.json();
          if (!response.ok || !result.ok) throw new Error(result.error || 'Prüfung fehlgeschlagen.');
          if (current !== generation || !dialog.open || busy) return;
          approved = checked;
          submit.disabled = false;
        } catch (e) {
          if (current !== generation || !dialog.open || busy) return;
          showError(e instanceof SyntaxError || e instanceof TypeError ? 'Prüfung nicht verfügbar. Bitte Eingabe ändern oder Dialog erneut öffnen.' : e.message);
        }
      }, 300);
    };
    const breadcrumb = moveFields.querySelector('[data-folder-breadcrumb]');
    const list = moveFields.querySelector('[data-folder-list]');
    const children = new Map();
    for (const option of folder.options) {
      const path = option.value;
      if (!path) continue;
      const parent = path.split('/').slice(0, -1).join('/');
      if (!children.has(parent)) children.set(parent, []);
      children.get(parent).push(path);
    }
    const icon = (classes) => {
      const element = document.createElement('span');
      element.className = classes;
      element.setAttribute('aria-hidden', 'true');
      return element;
    };
    const button = (label, path, className, symbol, trailing = false) => {
      const element = document.createElement('button');
      element.type = 'button';
      element.className = className;
      if (symbol) element.append(icon(symbol));
      const text = document.createElement('span');
      text.textContent = label;
      element.append(text);
      if (trailing) element.append(icon('hub-folder-chevron'));
      element.addEventListener('click', () => {
        folder.value = path;
        renderFolders();
        breadcrumb.lastElementChild?.focus();
        schedule();
      });
      return element;
    };
    const renderFolders = () => {
      const path = folder.value;
      const parts = path ? path.split('/') : [];
      breadcrumb.replaceChildren(button('Hauptverzeichnis', '', 'hub-folder-crumb', 'fa-solid fa-house'));
      parts.forEach((part, index) => {
        breadcrumb.append(icon('hub-folder-chevron'), button(part, parts.slice(0, index + 1).join('/'), 'hub-folder-crumb'));
      });
      breadcrumb.lastElementChild.setAttribute('aria-current', 'location');
      list.replaceChildren();
      if (path) list.append(button('Eine Ebene höher', parts.slice(0, -1).join('/'), 'hub-folder-row hub-folder-parent', 'fa-solid fa-arrow-turn-up'));
      for (const child of children.get(path) || []) {
        list.append(button(child.split('/').pop(), child, 'hub-folder-row', 'hub-nav-icon hub-nav-folder-icon', true));
      }
      if (!children.get(path)?.length) {
        const empty = document.createElement('p');
        empty.className = 'hub-muted hub-folder-empty';
        empty.textContent = 'Keine Unterordner';
        list.append(empty);
      }
      list.scrollTop = 0;
    };
    renderFolders();
    folder.addEventListener('change', schedule);
    name.addEventListener('input', schedule);
    tree?.addEventListener('change', schedule);
    document.querySelectorAll('[data-open-page-dialog="hub-page-move"]').forEach(button => button.addEventListener('click', schedule));
    dialog.addEventListener('close', () => {
      clearTimeout(timer); ++generation; approved = null; submit.disabled = true;
      if (tree && !tree.disabled) tree.checked = false;
      folder.value = initialFolder;
      renderFolders();
      update();
      showError('');
    });
    form.addEventListener('submit', event => {
      if (busy || !update() || approved !== signature() || submit.disabled) { event.preventDefault(); return; }
      busy = true; submit.disabled = true;
    });
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
  const key = value => value.trim().toLowerCase();
  const known = JSON.parse(root.dataset.knownTags);
  const name = token => Object.hasOwn(known, token) ? known[token] : token;
  const initialTags = field.value.split(',').map(t => t.trim()).filter(Boolean);
  let tags = [...initialTags];
  let choices = [], active = -1;
  const close = () => { options.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); active = -1; };
  const render = () => {
    field.value = tags.join(', ');
    field.dispatchEvent(new Event('input', {bubbles:true}));
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
  root.addEventListener('hub-tag-commit', e => { if (!add(input.value)) e.preventDefault(); });
  root.addEventListener('hub-tag-reset', () => { tags = [...initialTags]; input.value = ''; render(); close(); });
  field.type = 'hidden'; root.querySelector('[data-tag-label]').htmlFor = input.id; root.querySelector('[data-tag-enhanced]').hidden = false; render();
})();

(() => {
  const select = document.querySelector('#hub-page-settings select[name=visibility]');
  if (!select) return;
  const update = () => document.querySelectorAll('[data-visibility-fields]').forEach(group => {
    group.hidden = group.dataset.visibilityFields !== select.value;
    group.disabled = group.hidden;
  });
  select.addEventListener('change', update);
  update();
})();
