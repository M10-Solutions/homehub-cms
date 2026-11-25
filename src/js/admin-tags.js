/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
(() => {
    const root = document.getElementById('tags-manager');
    if (!root) return;
    const get = id => document.getElementById(id);
    const rows = [...root.querySelectorAll('tbody tr')];
    let selected = null;
    const sync = () => {
        const query = get('tags-search').value.trim().toLocaleLowerCase();
        for (const row of rows) {
            row.hidden = !row.dataset.tag.toLocaleLowerCase().includes(query);
            if (row.hidden && selected === row) selected = null;
            row.classList.toggle('selected', selected === row);
            row.querySelector('input').checked = selected === row;
        }
        const count = rows.filter(row => !row.hidden).length;
        get('tags-count').textContent = `${count} Schlagwörter`;
        get('tags-empty').hidden = count !== 0;
        get('tags-edit').disabled = !selected;
        get('tags-delete').disabled = !selected || Number(selected.dataset.used) > 0;
    };
    const edit = creating => {
        if (!creating && !selected) return;
        const dialog = get('tags-editor'), form = dialog.querySelector('form');
        form.elements.action.value = creating ? 'tag-create' : 'tag-update';
        form.elements.original_tag.value = creating ? '' : selected.dataset.id;
        form.elements.tag.value = creating ? '' : selected.dataset.tag;
        get('tags-title').textContent = creating ? 'Schlagwort erstellen' : 'Schlagwort bearbeiten';
        dialog.showModal(); form.elements.tag.focus();
    };
    for (const row of rows) {
        row.addEventListener('click', () => { selected = row; sync(); });
        row.querySelector('input').addEventListener('change', () => { selected = row; sync(); });
        row.addEventListener('dblclick', () => edit(false));
    }
    get('tags-search').addEventListener('input', sync);
    get('tags-new').addEventListener('click', () => edit(true));
    get('tags-edit').addEventListener('click', () => edit(false));
    get('tags-delete').addEventListener('click', () => {
        if (!selected || Number(selected.dataset.used)) return;
        const dialog = get('tags-confirm');
        dialog.querySelector('[name="original_tag"]').value = selected.dataset.id;
        get('tags-confirm-text').textContent = `«${selected.dataset.tag}» wirklich löschen?`;
        dialog.showModal();
    });
    root.querySelectorAll('[data-tag-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
    sync();
})();
