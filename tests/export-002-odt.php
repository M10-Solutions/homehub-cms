<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

require __DIR__ . '/../src/lib/OdtImageFitter.php';

use HomeHub\OdtImageFitter;

function testOdt(float $pageWidth, float $left, float $right, float $imageWidth, float $imageHeight, float $expected): void
{
    $path = tempnam(sys_get_temp_dir(), 'homehub-odt-');
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Cannot create ODT');
    $zip->addFromString('styles.xml', '<?xml version="1.0"?><office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"><office:automatic-styles><style:page-layout style:name="P1"><style:page-layout-properties fo:page-width="'.$pageWidth.'cm" fo:margin-left="'.$left.'cm" fo:margin-right="'.$right.'cm"/></style:page-layout></office:automatic-styles><office:master-styles><style:master-page style:name="Standard" style:page-layout-name="P1"/></office:master-styles></office:document-styles>');
    $zip->addFromString('content.xml', '<?xml version="1.0"?><office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"><office:body><office:text><draw:frame svg:width="'.$imageWidth.'cm" svg:height="'.$imageHeight.'cm"><draw:image/></draw:frame></office:text></office:body></office:document-content>');
    $zip->close();
    try {
        OdtImageFitter::fit($path);
        $zip->open($path);
        $xml = $zip->getFromName('content.xml');
        $zip->close();
        $dom = new DOMDocument(); $dom->loadXML($xml, LIBXML_NONET);
        $frame = $dom->getElementsByTagNameNS('urn:oasis:names:tc:opendocument:xmlns:drawing:1.0', 'frame')->item(0);
        $ns = 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0';
        $actual = (float) $frame->getAttributeNS($ns, 'width');
        $height = (float) $frame->getAttributeNS($ns, 'height');
        if (abs($actual - $expected) > 0.001 || abs($height - $imageHeight * $expected / $imageWidth) > 0.001) {
            throw new RuntimeException("Expected {$expected}cm, got {$actual}cm x {$height}cm");
        }
        OdtImageFitter::fit($path); // Idempotence
    } finally { @unlink($path); }
}

testOdt(21, 2, 2, 20, 10, 17);
testOdt(21, 3, 3, 20, 10, 15);
testOdt(29.7, 2, 2, 28, 14, 25.7);
testOdt(21, 2, 2, 10, 5, 10); // No upscaling

echo "OK: EXPORT-002 dynamic page width, aspect ratio, no upscaling\n";

// Exercise actual Pandoc-generated table styles, including a headerless table.
$path = tempnam(sys_get_temp_dir(), 'homehub-odt-table-');
try {
    $process = proc_open(['/usr/bin/pandoc', '--from=html', '--to=odt', '--output='.$path, '--reference-doc='.__DIR__.'/../docs/templates/reference.odt'], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Pandoc unavailable');
    fwrite($pipes[0], '<table><thead><tr><th>Header</th></tr></thead><tbody><tr><td>Body</td></tr></tbody></table><table><tr><td>Other</td></tr></table>');
    fclose($pipes[0]); stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    if (proc_close($process) !== 0) throw new RuntimeException($errors);
    OdtImageFitter::fit($path);
    OdtImageFitter::fit($path);
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) throw new RuntimeException('Cannot open export');
    $xml = $zip->getFromName('content.xml'); $zip->close();
    $dom = new DOMDocument(); $dom->loadXML($xml, LIBXML_NONET);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
    $fo = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
    foreach (['TableHeaderRowCell', 'TableRowCell'] as $name) {
        $props = $xpath->query('//style:style[@style:name="'.$name.'"]/style:table-cell-properties')->item(0);
        if (!$props instanceof DOMElement || $props->getAttributeNS($fo, 'border') !== '0.5pt solid #C5CCD0') throw new RuntimeException('Missing table border: '.$name);
        if ($name === 'TableHeaderRowCell' && $props->getAttributeNS($fo, 'background-color') !== '#E8EEF1') throw new RuntimeException('Missing header background');
    }
    foreach (['Header', 'Body', 'Other'] as $value) if (!str_contains($dom->textContent, $value)) throw new RuntimeException('Missing table content');
} finally { @unlink($path); }
echo "OK: EXPORT-002 Pandoc table borders and header background\n";
