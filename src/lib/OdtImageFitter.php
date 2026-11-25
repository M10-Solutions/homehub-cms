<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace HomeHub;

/** Post-process Pandoc ODT image frames using the reference document's page layout. */
final class OdtImageFitter
{
    private const SVG = 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0';
    private const FO = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
    private const STYLE = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';

    public static function fit(string $odtPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($odtPath) !== true) throw new \RuntimeException('ODT-Datei konnte nicht geöffnet werden.');
        try {
            $content = $zip->getFromName('content.xml');
            if ($content === false) throw new \RuntimeException('ODT-Inhalt fehlt.');
            $dom = self::parse($content);
            $styles = $zip->getFromName('styles.xml');
            // Keep a conservative default for documents with no readable page layout.
            $maxCm = $styles === false ? 16.0 : self::availableWidth(self::parse($styles));
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
            $changed = false;
            foreach ($xpath->query('//draw:frame[draw:image]') as $frame) {
                if (!$frame instanceof \DOMElement) continue;
                $width = self::centimeters($frame->getAttributeNS(self::SVG, 'width'));
                $height = self::centimeters($frame->getAttributeNS(self::SVG, 'height'));
                if ($width === null || $height === null || $width <= $maxCm || $height <= 0) continue;
                $frame->setAttributeNS(self::SVG, 'svg:width', self::format($maxCm));
                $frame->setAttributeNS(self::SVG, 'svg:height', self::format($height * $maxCm / $width));
                $changed = true;
            }
            if ($changed) {
                $updated = $dom->saveXML();
                if ($updated === false || !$zip->addFromString('content.xml', $updated)) {
                    throw new \RuntimeException('ODT-Bildgrössen konnten nicht gespeichert werden.');
                }
            }
        } finally {
            if (!$zip->close()) throw new \RuntimeException('ODT-Datei konnte nicht gespeichert werden.');
        }
    }

    private static function availableWidth(\DOMDocument $dom): float
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('style', self::STYLE);
        $masters = $xpath->query('//style:master-page');
        $layoutName = null;
        foreach ($masters as $master) {
            if (!$master instanceof \DOMElement) continue;
            $layoutName = $master->getAttributeNS(self::STYLE, 'page-layout-name');
            if ($master->getAttributeNS(self::STYLE, 'name') === 'Standard') break;
        }
        foreach ($xpath->query('//style:page-layout') as $layout) {
            if (!$layout instanceof \DOMElement || ($layoutName !== null && $layoutName !== '' && $layout->getAttributeNS(self::STYLE, 'name') !== $layoutName)) continue;
            $properties = $xpath->query('./style:page-layout-properties', $layout)->item(0);
            if (!$properties instanceof \DOMElement) continue;
            $width = self::centimeters($properties->getAttributeNS(self::FO, 'page-width'));
            $left = self::centimeters($properties->getAttributeNS(self::FO, 'margin-left'));
            $right = self::centimeters($properties->getAttributeNS(self::FO, 'margin-right'));
            // Margin shorthand is valid ODF too; individual margins take precedence.
            $margin = self::centimeters($properties->getAttributeNS(self::FO, 'margin'));
            $left ??= $margin;
            $right ??= $margin;
            if ($width !== null && $left !== null && $right !== null && $width > $left + $right + 0.1) {
                return $width - $left - $right;
            }
        }
        return 16.0;
    }

    private static function centimeters(string $value): ?float
    {
        if (!preg_match('/^\s*(\d+(?:\.\d+)?)\s*(cm|mm|in|pt|pc)\s*$/i', $value, $m)) return null;
        return (float) $m[1] * match (strtolower($m[2])) {
            'cm' => 1.0, 'mm' => 0.1, 'in' => 2.54, 'pt' => 2.54 / 72, 'pc' => 2.54 / 6,
        };
    }

    private static function format(float $cm): string
    {
        return rtrim(rtrim(sprintf('%.4f', $cm), '0'), '.') . 'cm';
    }

    private static function parse(string $xml): \DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        if (!$dom->loadXML($xml, LIBXML_NONET)) throw new \RuntimeException('ODT-XML ist ungültig.');
        return $dom;
    }
}
