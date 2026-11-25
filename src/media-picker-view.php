<?php /* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */ ?><dialog id="hub-media-picker" class="hub-media-picker" aria-labelledby="hub-media-title">
  <div class="hub-media-picker-header hub-dialog-header">
    <div class="hub-media-picker-heading">
      <span class="hub-media-picker-icon" aria-hidden="true"><i class="fa-regular fa-images"></i></span>
      <div><h2 id="hub-media-title">Bild einfügen</h2><p>Bild auswählen oder direkt hochladen</p></div>
    </div>
    <button type="button" class="hub-media-icon-button" data-media-close aria-label="Schliessen"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
  </div>
  <div class="hub-media-picker-body">
    <div class="hub-media-picker-controls">
      <label>Ordner<select id="hub-media-folder"></select></label>
      <label>Suche<input type="search" id="hub-media-search" placeholder="Dateiname suchen …"></label>
    </div>
    <div id="hub-media-items" class="hub-media-items" aria-label="Bilder"></div>
    <div class="hub-media-picker-details">
      <label>Bildbeschreibung (Alternativtext)<input type="text" id="hub-media-alt" maxlength="200" placeholder="Bild kurz beschreiben …"></label>
      <div class="hub-media-picker-utilities">
        <form id="hub-media-upload">
          <label>Bild hochladen<input type="file" name="file" accept="image/jpeg,image/png,image/webp,image/gif" required></label>
          <button type="submit" class="hub-media-button hub-media-button-secondary"><i class="fa-solid fa-upload" aria-hidden="true"></i> Hochladen</button>
        </form>
        <?php if (\HomeHub\PermissionService::can($role, 'media.manage')): ?>
        <form id="hub-media-folder-form">
          <label>Neuer Ordner<input name="folder" placeholder="z. B. dokumentation/hardware" required></label>
          <button type="submit" class="hub-media-button hub-media-button-secondary"><i class="fa-regular fa-folder-open" aria-hidden="true"></i> Anlegen</button>
        </form>
        <?php endif; ?>
      </div>
      <p id="hub-media-message" role="status" aria-live="polite"></p>
    </div>
  </div>
  <div class="hub-media-picker-actions">
    <button type="button" class="hub-media-button hub-media-button-secondary" data-media-close>Abbrechen</button>
    <button type="button" class="hub-media-button hub-media-button-primary" id="hub-media-insert" disabled><i class="fa-regular fa-image" aria-hidden="true"></i> Bild einfügen</button>
  </div>
</dialog>
