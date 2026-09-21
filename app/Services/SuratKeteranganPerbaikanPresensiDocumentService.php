<?php

namespace App\Services;

use App\SuratKeluar;
use App\SuratKeluarApproval;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Creates the approved DOCX for Surat Keterangan Perbaikan Presensi.
 * The supplied DOCX remains the layout authority; this service only fills
 * placeholders, checks the approval option, and inserts the approved stamp.
 */
class SuratKeteranganPerbaikanPresensiDocumentService
{
    const TEMPLATE = 'templates/surat-keterangan-perbaikan-presensi.docx';
    const STAMP = 'kpta + stempel.png';
    const SLUG = 'surat-keterangan-perbaikan-presensi';

    public function make(SuratKeluar $suratKeluar, SuratKeluarApproval $approval)
    {
        $template = public_path(self::TEMPLATE);
        $stamp = public_path(self::STAMP);
        if (!is_file($template) || !is_file($stamp)) {
            throw new \RuntimeException('Template atau gambar tanda tangan KPTA tidak ditemukan.');
        }

        $source = new ZipArchive();
        if ($source->open($template) !== true) {
            throw new \RuntimeException('Template Surat Keterangan Perbaikan Presensi tidak dapat dibuka.');
        }

        $documentXml = $source->getFromName('word/document.xml');
        $relsXml = $source->getFromName('word/_rels/document.xml.rels');
        $typesXml = $source->getFromName('[Content_Types].xml');
        if (!$documentXml || !$relsXml || !$typesXml) {
            $source->close();
            throw new \RuntimeException('Struktur DOCX template tidak lengkap.');
        }

        $values = $this->resolvedValues($suratKeluar, $approval);
        $document = $this->loadXml($documentXml);
        $xpath = $this->xpath($document);
        foreach ($values as $key => $value) {
            if ($key === 'tanda_tangan_pimpinan_satker') {
                continue;
            }
            $this->replaceText($xpath, '{{' . $key . '}}', (string) $value);
        }

        $this->setApprovalState($xpath, $approval);
        $rels = $this->loadXml($relsXml);
        $relationshipId = $this->addImageRelationship($rels);
        $this->insertStamp($xpath, $relationshipId, $stamp);
        $types = $this->loadXml($typesXml);
        $this->ensurePngContentType($types);

        $temporary = tempnam(storage_path('app'), 'suket-presensi-');
        if ($temporary === false) {
            $source->close();
            throw new \RuntimeException('Lokasi penyimpanan sementara DOCX tidak tersedia.');
        }
        $outputPath = $temporary . '.docx';
        @unlink($temporary);

        $writer = new ZipArchive();
        if ($writer->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $source->close();
            throw new \RuntimeException('Berkas DOCX hasil approval tidak dapat dibuat.');
        }

        for ($index = 0; $index < $source->numFiles; $index++) {
            $entry = $source->statIndex($index);
            $name = $entry['name'];
            if ($name === 'word/document.xml') {
                $writer->addFromString($name, $document->saveXML());
            } elseif ($name === 'word/_rels/document.xml.rels') {
                $writer->addFromString($name, $rels->saveXML());
            } elseif ($name === '[Content_Types].xml') {
                $writer->addFromString($name, $types->saveXML());
            } else {
                $writer->addFromString($name, $source->getFromIndex($index));
            }
        }
        $writer->addFile($stamp, 'word/media/kpta-stempel.png');
        $writer->close();
        $source->close();

        $relativePath = 'surat-keluar/generated/suket-perbaikan-presensi-' . $suratKeluar->id . '.docx';
        Storage::disk('public')->put($relativePath, file_get_contents($outputPath));
        @unlink($outputPath);

        return $relativePath;
    }

    protected function resolvedValues(SuratKeluar $suratKeluar, SuratKeluarApproval $approval)
    {
        $values = is_array($approval->field_values) ? $approval->field_values : [];
        $approver = $approval->relationLoaded('approver') ? $approval->approver : $approval->approver()->with('jabatan')->first();
        $signerName = $approval->signer_name_snapshot ?: optional($approver)->name ?: '-';
        $signerTitle = $approval->signer_title_snapshot ?: optional(optional($approver)->jabatan)->nama ?: (optional($approver)->jabatan_keterangan ?: 'Ketua');

        $values['nomor_surat'] = $suratKeluar->nomor_surat_formatted;
        $values['tanggal_surat'] = optional($suratKeluar->tanggal_surat)->format('d/m/Y') ?: now()->format('d/m/Y');
        if (!empty($values['tanggal_presensi']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $values['tanggal_presensi'])) {
            $values['tanggal_presensi'] = Carbon::parse($values['tanggal_presensi'])->format('d/m/Y');
        }
        $values['tempat_surat'] = $values['tempat_surat'] ?? 'Manokwari';
        $values['zona_waktu'] = $values['zona_waktu'] ?? 'WIT';
        $values['jabatan_pimpinan_satker'] = $values['jabatan_pimpinan_satker'] ?? $signerTitle;
        $values['nama_pimpinan_satker'] = $values['nama_pimpinan_satker'] ?? $signerName;
        $values['nip_pimpinan_satker'] = $values['nip_pimpinan_satker'] ?? (optional($approver)->nip ?: '-');
        $values['alasan_penolakan'] = $values['alasan_penolakan'] ?? '-';
        $values['tanda_tangan_pembuat_keterangan'] = $values['tanda_tangan_pembuat_keterangan'] ?? '';

        return $values;
    }

    protected function loadXml($xml)
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;
        $document->loadXML($xml);
        return $document;
    }

    protected function xpath(DOMDocument $document)
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $xpath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
        return $xpath;
    }

    protected function replaceText(DOMXPath $xpath, $needle, $replacement)
    {
        foreach ($xpath->query('//w:t') as $text) {
            if (strpos($text->nodeValue, $needle) !== false) {
                $text->nodeValue = str_replace($needle, $replacement, $text->nodeValue);
            }
        }
    }

    protected function setApprovalState(DOMXPath $xpath, SuratKeluarApproval $approval)
    {
        foreach ($xpath->query('//w:p') as $paragraph) {
            $text = trim($xpath->evaluate('string(.)', $paragraph));
            if ($text === 'Disetujui') {
                $this->replaceParagraphText($xpath, $paragraph, '☑ Disetujui');
                $numPr = $xpath->query('./w:pPr/w:numPr', $paragraph)->item(0);
                if ($numPr) {
                    $numPr->parentNode->removeChild($numPr);
                }
            } elseif (strpos($text, 'Ditolak, karena') === 0) {
                $reason = $approval->status === 'rejected' ? ($approval->note ?: '-') : '-';
                $this->replaceParagraphText($xpath, $paragraph, '☐ Ditolak, karena ' . $reason);
                $numPr = $xpath->query('./w:pPr/w:numPr', $paragraph)->item(0);
                if ($numPr) {
                    $numPr->parentNode->removeChild($numPr);
                }
            }
        }
    }

    protected function replaceParagraphText(DOMXPath $xpath, DOMElement $paragraph, $replacement)
    {
        $texts = $xpath->query('.//w:t', $paragraph);
        if (!$texts->length) {
            return;
        }
        $texts->item(0)->nodeValue = $replacement;
        for ($index = $texts->length - 1; $index > 0; $index--) {
            $texts->item($index)->parentNode->removeChild($texts->item($index));
        }
    }

    protected function addImageRelationship(DOMDocument $rels)
    {
        $root = $rels->documentElement;
        $max = 0;
        foreach ($root->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }
            if (preg_match('/^rId(\d+)$/', $child->getAttribute('Id'), $match)) {
                $max = max($max, (int) $match[1]);
            }
        }

        $relationship = $rels->createElementNS('http://schemas.openxmlformats.org/package/2006/relationships', 'Relationship');
        $relationship->setAttribute('Id', 'rId' . ($max + 1));
        $relationship->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image');
        $relationship->setAttribute('Target', 'media/kpta-stempel.png');
        $root->appendChild($relationship);

        return 'rId' . ($max + 1);
    }

    protected function ensurePngContentType(DOMDocument $types)
    {
        $root = $types->documentElement;
        foreach ($root->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'Default' && strtolower($child->getAttribute('Extension')) === 'png') {
                return;
            }
        }
        $default = $types->createElementNS('http://schemas.openxmlformats.org/package/2006/content-types', 'Default');
        $default->setAttribute('Extension', 'png');
        $default->setAttribute('ContentType', 'image/png');
        $root->appendChild($default);
    }

    protected function insertStamp(DOMXPath $xpath, $relationshipId, $imagePath)
    {
        foreach ($xpath->query('//w:p') as $paragraph) {
            $text = $xpath->evaluate('string(.)', $paragraph);
            if (strpos($text, '{{tanda_tangan_pimpinan_satker}}') === false) {
                continue;
            }

            foreach ($xpath->query('./w:r', $paragraph) as $run) {
                $paragraph->removeChild($run);
            }

            $dimensions = @getimagesize($imagePath);
            $width = 900000;
            $height = $dimensions && !empty($dimensions[0])
                ? (int) round($width * ((float) $dimensions[1] / (float) $dimensions[0]))
                : 1200000;
            $fragment = $paragraph->ownerDocument->createDocumentFragment();
            $fragment->appendXML($this->drawingXml($relationshipId, $width, $height));
            $paragraph->appendChild($fragment);
            return;
        }
    }

    protected function drawingXml($relationshipId, $width, $height)
    {
        return '<w:r xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            . '<wp:extent cx="' . $width . '" cy="' . $height . '"/>'
            . '<wp:docPr id="42" name="KPTA dan Stempel"/>'
            . '<wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="kpta-stempel.png"/><pic:cNvPicPr/></pic:nvPicPr>'
            . '<pic:blipFill><a:blip r:embed="' . $relationshipId . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            . '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $width . '" cy="' . $height . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            . '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>';
    }
}
