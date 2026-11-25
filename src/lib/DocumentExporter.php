<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace HomeHub;

final class DocumentExporter
{
    public function __construct(private readonly string $uploadDirectory) {}

    /** @return array{path:string,filename:string,mime:string,directory:string} */
    public function export(Page $page, string $format, array $options = []): array
    {
        if (!in_array($format, ['pdf', 'odt'], true)) {
            throw new \InvalidArgumentException('Unbekanntes Exportformat.');
        }
        $directory = sys_get_temp_dir() . '/homehub-export-' . bin2hex(random_bytes(12));
        if (!mkdir($directory, 0700)) {
            throw new \RuntimeException('Exportverzeichnis konnte nicht erstellt werden.');
        }
        try {
            $source = $directory . '/seite.md';
            $output = $directory . '/seite.' . $format;
            $body = AlignedMarkdown::forPandoc($this->prepareImages((empty($options['collection']) && $page->description !== '' ? $page->description . "\n\n" : '') . $page->content));
            if (file_put_contents($source, $body) === false) {
                throw new \RuntimeException('Exportquelle konnte nicht erstellt werden.');
            }
            $command = [
                '/usr/bin/pandoc', '--from=markdown+fenced_divs-raw_html', '--standalone',
                '--metadata=title:' . $page->title,
                '--metadata=lang:de-CH',
                '--output=' . $output,
            ];
            if ($format === 'pdf') {
                $command[] = '--to=html5';
                $command[] = '--pdf-engine=weasyprint';
                $command[] = '--css=' . dirname(__DIR__) . '/css/export.css';
                if (!empty($options['collection'])) $command[] = '--css=' . dirname(__DIR__) . '/css/chapter-document.css';
            } else {
                $command[] = '--to=odt';
                $reference = dirname(__DIR__, 2) . '/docs/templates/reference.odt';
                if (is_file($reference) && is_readable($reference)) {
                    $command[] = '--reference-doc=' . $reference;
                }
            }
            if (!empty($options['toc'])) {
                $command[] = '--toc';
                $command[] = '--toc-depth=5';
            }
            if (!empty($options['numbering'])) $command[] = '--number-sections';
            if (!empty($options['collection']) && empty($options['cover'])) {
                $command[] = '--metadata=title:';
            }
            $command[] = $source;
            $process = proc_open($command, [
                0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
            ], $pipes, $directory, ['PATH' => '/usr/local/bin:/usr/bin:/bin', 'HOME' => $directory]);
            if (!is_resource($process)) {
                throw new \RuntimeException('Dokumenten-Konverter konnte nicht gestartet werden.');
            }
            fclose($pipes[0]);
            stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $status = proc_close($process);
            if ($status !== 0 || !is_file($output) || filesize($output) === 0) {
                error_log('HomeHub Export fehlgeschlagen: ' . substr($errors, 0, 2000));
                throw new \RuntimeException('Dokument konnte nicht erzeugt werden.');
            }
            if ($format === 'odt') {
                OdtImageFitter::fit($output);
            }
            // Keep the native Pandoc index editable in LibreOffice, but fill its
            // cached heading entries so it is useful before the first refresh.
            if ($format === 'odt' && !empty($options['collection']) && !empty($options['toc'])) {
                $this->populateOdtTableOfContents($output);
            }
            $name = basename(str_replace('/', '-', $page->slug));
            return [
                'path' => $output,
                'filename' => $name . '.' . $format,
                'mime' => $format === 'pdf' ? 'application/pdf' : 'application/vnd.oasis.opendocument.text',
                'directory' => $directory,
            ];
        } catch (\Throwable $exception) {
            self::cleanup($directory);
            throw $exception;
        }
    }

    /** Populate Pandoc's native ODT index without an additional runtime. */
    private function populateOdtTableOfContents(string $odtPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($odtPath) !== true) {
            throw new \RuntimeException('ODT-Datei konnte nicht geöffnet werden.');
        }
        try {
            $xml = $zip->getFromName('content.xml');
            if ($xml === false) {
                throw new \RuntimeException('ODT-Inhalt fehlt.');
            }
            $dom = new \DOMDocument('1.0', 'UTF-8');
            if (!$dom->loadXML($xml, LIBXML_NONET)) {
                throw new \RuntimeException('ODT-Inhalt ist ungültiges XML.');
            }
            $xpath = new \DOMXPath($dom);
            $textNs = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
            $officeNs = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
            $xpath->registerNamespace('text', $textNs);
            $xpath->registerNamespace('office', $officeNs);
            $toc = $xpath->query('//text:table-of-content')->item(0);
            if (!$toc instanceof \DOMElement) {
                return;
            }
            $body = $xpath->query('./text:index-body', $toc)->item(0);
            if (!$body instanceof \DOMElement) {
                $body = $dom->createElementNS($textNs, 'text:index-body');
                $toc->appendChild($body);
            }
            // Do not replace existing index entries or duplicate them.
            if ($body->hasChildNodes()) {
                return;
            }
            $headings = $xpath->query('//office:body/office:text//text:h[not(ancestor::text:table-of-content)]');
            foreach ($headings as $heading) {
                if (!$heading instanceof \DOMElement) continue;
                $level = (int) $heading->getAttributeNS($textNs, 'outline-level');
                if ($level < 1 || $level > 5) continue;
                $label = trim($heading->textContent);
                if ($label === '') continue;
                $paragraph = $dom->createElementNS($textNs, 'text:p');
                $paragraph->setAttributeNS($textNs, 'text:style-name', 'Contents_20_' . $level);
                $paragraph->appendChild($dom->createTextNode($label));
                $body->appendChild($paragraph);
            }
            if ($body->hasChildNodes()) {
                $updated = $dom->saveXML();
                if ($updated === false || !$zip->addFromString('content.xml', $updated)) {
                    throw new \RuntimeException('ODT-Inhaltsverzeichnis konnte nicht gespeichert werden.');
                }
            }
        } finally {
            if (!$zip->close()) {
                throw new \RuntimeException('ODT-Datei konnte nicht gespeichert werden.');
            }
        }
    }

    private function prepareImages(string $markdown): string
    {
        // Only permit local images from HomeHub's managed upload directory.
        // Avoid remote requests and arbitrary local file reads by Pandoc.
        return (string) preg_replace_callback('~!\[([^\]\\r\\n]*)\]\(([^)\\r\\n]+)\)~u', function (array $matches): string {
            $alt = $matches[1];
            $url = trim($matches[2]);
            $parts = parse_url($url);
            if (!is_array($parts) || ($parts['path'] ?? '') !== '/media.php') {
                return $alt;
            }
            parse_str($parts['query'] ?? '', $query);
            $name = $query['file'] ?? null;
            if (isset($query['id']) && is_string($query['id'])) {
                try {
                    $name = (new MediaRegistry(dirname($this->uploadDirectory) . '/config'))->pathForId($query['id']);
                } catch (\Throwable) { return $alt; }
            }
            if (!is_string($name)) return $alt;
            try {
                $file = (new MediaLibrary($this->uploadDirectory))->resolve($name);
                if (!str_starts_with($file['mime'], 'image/')) return $alt;
                // Local absolute paths are supplied only after MediaLibrary validation.
                return '![' . $alt . '](' . $file['path'] . ')';
            } catch (\Throwable) {
                return $alt;
            }
        }, $markdown);
    }

    public static function cleanup(string $directory): void
    {
        foreach (glob($directory . '/*') ?: [] as $file) {
            if (is_file($file)) @unlink($file);
        }
        @rmdir($directory);
    }
}
