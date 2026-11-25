/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
/* Shared Markdown toolbar for the inline and classic admin editors. */
(() => {
  function apply(textarea, mode) {
    const start = textarea.selectionStart, end = textarea.selectionEnd;
    const selected = textarea.value.slice(start, end);
    const lineStart = textarea.value.lastIndexOf('\n', Math.max(0,start-1)) + 1;
    const actions = {
      heading1: ['# ', '', 'Überschrift'], heading2: ['## ', '', 'Überschrift'], heading3: ['### ', '', 'Überschrift'],
      bold: ['**','**','Text'], italic: ['*','*','Text'], strike: ['~~','~~','Text'],
      link: ['[','](https://example.org)','Linktext'], image: ['![','](/media.php?file=bild.jpg)','Bildbeschreibung'],
      unordered: ['- ','','Listeneintrag'], ordered: ['1. ','','Listeneintrag'], task: ['- [ ] ','','Aufgabe'],
      quote: ['> ','','Zitat'], inlinecode: ['`','`','Code'],
      codeblock: ['```text\n','\n```','Code'],
      table: ['| Spalte 1 | Spalte 2 |\n| --- | --- |\n| Wert 1 | Wert 2 |\n','',''],
      rule: ['\n---\n','','']
    };
    if (mode.startsWith('align-')) {
      const alignment = mode.slice(6);
      if (!['left','center','right','justify'].includes(alignment)) return;
      const value = selected || 'Text';
      const prefix = '::: ' + alignment + '\n';
      textarea.setRangeText(prefix + value + '\n:::', start, end, 'select');
      textarea.setSelectionRange(start + prefix.length, start + prefix.length + value.length);
      textarea.focus(); textarea.dispatchEvent(new Event('input',{bubbles:true}));
      return;
    }
    const action=actions[mode]; if (!action) return;
    let [before,after,fallback]=action;
    const lineModes = ['heading1','heading2','heading3','unordered','ordered','task','quote'];
    // Line-based markup is inserted at the start of the selected line.
    let from=start, to=end, value=selected || fallback;
    if (lineModes.includes(mode)) {
      from=lineStart;
      to=end;
      value=textarea.value.slice(from,to) || fallback;
      value=value.split('\n').map(line=>before+line).join('\n');
      before=''; after='';
    } else if (mode==='table' || mode==='rule') {
      value='';
    }
    textarea.setRangeText(before+value+after,from,to,'end');
    const cursor=from+before.length;
    if (mode!=='table' && mode!=='rule') textarea.setSelectionRange(cursor,cursor+value.length);
    textarea.focus(); textarea.dispatchEvent(new Event('input',{bubbles:true}));
  }
  window.HomeHubMarkdown = { apply };
})();
