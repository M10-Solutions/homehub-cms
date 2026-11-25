<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace HomeHub;

final class Page
{
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $description,
        public readonly string $content,
        public readonly string $category,
        public readonly int $order,
        public readonly bool $showInNavigation,
        public readonly bool $published,
        public readonly array $labels = [],
        public readonly string $visibility = 'all',
        public readonly array $allowedRoles = [],
        public readonly array $allowedUsers = [],
        public readonly ?string $dateCreated = null,
        public readonly ?string $dateModified = null,
        public readonly ?string $id = null,
        public readonly bool $metadataValid = true,
    ) {}
}
