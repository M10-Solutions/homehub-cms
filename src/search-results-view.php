<?php /* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */ ?><section class="hub-search-results" aria-label="Suchergebnisse">
    <?php if ($searchQuery === ''): ?>
        <p>Bitte einen Suchbegriff eingeben.</p>
    <?php else: ?>
        <p class="hub-search-summary"><?= count($searchResults) ?> Treffer für «<?= e($searchQuery) ?>»</p>
        <?php if (!$searchResults): ?>
            <p>Keine passenden Seiten gefunden. Versuche einen anderen Suchbegriff.</p>
        <?php else: ?>
            <ul class="hub-search-list">
            <?php foreach ($searchResults as $result): $page = $result['page'];
                $href = $page->id !== null ? '/page.php?id=' . rawurlencode($page->id) : '/' . implode('/', array_map('rawurlencode', explode('/', $page->slug))); ?>
                <li>
                    <h2><a href="<?= e($href) ?>"><i class="fa-regular fa-file-lines" aria-hidden="true"></i> <?= e($page->title) ?></a></h2>
                    <p class="hub-search-path"><?= e($page->slug) ?></p>
                    <?php if ($result['excerpt'] !== ''): ?><p class="hub-search-excerpt"><?= e($result['excerpt']) ?></p><?php endif; ?>
                    <?php if ($result['tags']): ?>
                        <div class="hub-search-tags" aria-label="Passende Schlagworte">
                            <?php foreach ($result['tags'] as $tag): ?><span class="page-label-chip"><?= e($tag) ?></span><?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</section>
