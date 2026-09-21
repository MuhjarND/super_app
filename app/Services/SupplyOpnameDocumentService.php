<?php

namespace App\Services;

use App\SupplyOpnameReport;
use DOMDocument;
use DOMElement;
use DOMXPath;
use ZipArchive;

/**
 * Fills the supplied BA Opname template while retaining its Word layout.
 * The template is deliberately kept as the design authority; only text and
 * table rows are changed here.
 */
class SupplyOpnameDocumentService
{
    const TEMPLATE = 'templates/BA OPNAME FISIK PERSEDIAAN.docx';

    public function make(SupplyOpnameReport $report)
    {
        $template = public_path(self::TEMPLATE);
        if (!is_file($template)) {
            throw new \RuntimeException('Template BA Opname Fisik tidak ditemukan.');
        }

        $zip = new ZipArchive();
        if ($zip->open($template) !== true) {
            throw new \RuntimeException('Template BA Opname Fisik tidak dapat dibuka.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if (!$xml) {
            throw new \RuntimeException('Isi dokumen Word tidak lengkap.');
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $snapshot = (array) $report->snapshot;
        $this->replaceParagraph($xpath, 'BULAN AGUSTUS 2026', 'BULAN ' . $this->monthName($report->report_period) . ' ' . $report->report_period->format('Y'));
        $this->replaceNthParagraphContaining($xpath, 'Nomor', 'Nomor : ' . $report->nomor_surat, 0);
        $dateSentence = sprintf(
            'Pada hari ini %s tanggal %s bulan %s tahun %s (%s), bertempat di kantor Pengadilan Tinggi Agama Papua Barat, kami yang bertanda tangan di bawah ini, Panitia opname fisik persediaan:',
            $this->dayName($report->opname_date),
            $this->numberToWords((int) $report->opname_date->format('j')),
            ucwords(strtolower($this->monthName($report->opname_date))),
            $this->yearToWords((int) $report->opname_date->format('Y')),
            $report->opname_date->format('d-m-Y')
        );
        $this->replaceParagraph($xpath, 'Pada hari ini', $dateSentence);
        $this->replaceParagraph($xpath, 'Menyatakan bahwa telah melakukan opname fisik', sprintf(
            'Menyatakan bahwa telah melakukan opname fisik barang persediaan Bulan %s tahun %s, dengan hasil sebagaimana terlampir dalam hasil opname fisik.',
            ucwords(strtolower($this->monthName($report->report_period))), $report->report_period->format('Y')
        ));
        $this->replaceParagraph($xpath, 'Demikian Berita Acara', sprintf(
            'Demikian Berita Acara Opname Fisik Persediaan ini dibuat untuk Laporan Monitoring Persediaan Bulanan Tahun %s dan apabila terdapat kekeliruan akan dilakukan perbaikan sebagaimana mestinya.',
            $report->report_period->format('Y')
        ));
        $this->replaceNthParagraphContaining($xpath, 'Tanggal', 'Tanggal : ' . $this->formatDate($report->opname_date), 0);
        $this->replacePeople($xpath, $snapshot);
        $this->replaceNthParagraphContaining($xpath, 'Nomor', 'Nomor : ' . $report->nomor_surat, 1);
        $this->removeTemplateSignature($xpath);

        $tables = $xpath->query('//w:tbl');
        if ($tables->length) {
            $this->fillTable($xpath, $tables->item(0), $snapshot['groups'] ?? [], (float) ($snapshot['grand_total'] ?? 0));
        }
        $this->trimTrailingEmptyParagraphs($xpath);

        $temporaryBase = tempnam(storage_path('app'), 'ba-opname-');
        if ($temporaryBase === false) {
            throw new \RuntimeException('Lokasi penyimpanan sementara DOCX tidak tersedia.');
        }
        @unlink($temporaryBase);
        $out = $temporaryBase . '.docx';
        $writer = new ZipArchive();
        if ($writer->open($out, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Berkas DOCX tidak dapat dibuat.');
        }
        $source = new ZipArchive();
        $source->open($template);
        for ($i = 0; $i < $source->numFiles; $i++) {
            $stat = $source->statIndex($i);
            $name = $stat['name'];
            $writer->addFromString($name, $name === 'word/document.xml' ? $dom->saveXML() : $source->getFromIndex($i));
        }
        $source->close();
        $writer->close();

        return $out;
    }

    protected function fillTable(DOMXPath $xpath, DOMElement $table, array $groups, $grandTotal)
    {
        $rows = $xpath->query('./w:tr', $table);
        if ($rows->length < 5) {
            return;
        }
        $templates = [
            'account' => $rows->item(3),
            'item' => $rows->item(4),
            'subtotal' => $rows->item(57) ?: $rows->item($rows->length - 2),
            'total' => $rows->item($rows->length - 1),
        ];
        for ($i = $rows->length - 1; $i >= 3; $i--) {
            $table->removeChild($rows->item($i));
        }

        foreach ($groups as $groupIndex => $group) {
            $account = $templates['account']->cloneNode(true);
            $this->setRow($xpath, $account, [$this->alpha($groupIndex) . '.', $group['label'], $group['account_code'], '', '', '', '', '', '']);
            $table->appendChild($account);
            $no = 1;
            foreach ($group['items'] as $item) {
                $row = $templates['item']->cloneNode(true);
                $this->setRow($xpath, $row, [
                    $no++, $item['name'], $item['code'], $item['unit'], $item['saldo'], $item['fisik'],
                    $item['selisih'], $item['kondisi'], $this->money($item['nilai']),
                ]);
                $table->appendChild($row);
            }
            $subtotal = $templates['subtotal']->cloneNode(true);
            $this->setRow($xpath, $subtotal, ['', 'Jumlah per Akun', $this->money($group['total'])]);
            $table->appendChild($subtotal);
        }
        $total = $templates['total']->cloneNode(true);
        $this->setRow($xpath, $total, ['', 'Total Jumlah Baik', $this->money($grandTotal), '', '', '', '', '', '', '']);
        $table->appendChild($total);
    }

    protected function setRow(DOMXPath $xpath, DOMElement $row, array $values)
    {
        $cells = $xpath->query('./w:tc', $row);
        foreach ($values as $index => $value) {
            if (!$cells->item($index)) {
                continue;
            }
            $cell = $cells->item($index);
            $texts = $xpath->query('.//w:t', $cell);
            if ($texts->length) {
                $texts->item(0)->nodeValue = (string) $value;
                for ($j = $texts->length - 1; $j > 0; $j--) {
                    $texts->item($j)->parentNode->removeChild($texts->item($j));
                }
            } else {
                $p = $xpath->query('.//w:p', $cell)->item(0);
                if (!$p) {
                    continue;
                }
                $r = $xpath->query('./w:r', $p)->item(0);
                if (!$r) {
                    $r = $p->appendChild($p->ownerDocument->createElement('w:r'));
                }
                $t = $r->appendChild($p->ownerDocument->createElement('w:t'));
                $t->nodeValue = (string) $value;
            }
        }
    }

    protected function replaceParagraph(DOMXPath $xpath, $needle, $replacement, $all = false)
    {
        $paragraphs = $xpath->query('//w:p');
        foreach ($paragraphs as $paragraph) {
            $text = trim($xpath->evaluate('string(.)', $paragraph));
            if (strpos($text, $needle) === false) {
                continue;
            }
            $texts = $xpath->query('.//w:t', $paragraph);
            if ($texts->length) {
                $texts->item(0)->nodeValue = $replacement;
                for ($i = $texts->length - 1; $i > 0; $i--) {
                    $texts->item($i)->parentNode->removeChild($texts->item($i));
                }
            }
            if (!$all) {
                break;
            }
        }
    }

    protected function replaceNthParagraphContaining(DOMXPath $xpath, $needle, $replacement, $occurrence = 0, array $excludedNeedles = [])
    {
        $paragraphs = $xpath->query('//w:p');
        $found = 0;
        foreach ($paragraphs as $paragraph) {
            $text = trim($xpath->evaluate('string(.)', $paragraph));
            if (strpos($text, $needle) === false) {
                continue;
            }
            $excluded = false;
            foreach ($excludedNeedles as $excludedNeedle) {
                if (strpos($text, $excludedNeedle) !== false) {
                    $excluded = true;
                    break;
                }
            }
            if ($excluded) {
                continue;
            }
            if ($found++ !== (int) $occurrence) {
                continue;
            }
            $this->setParagraphText($xpath, $paragraph, $replacement);
            return;
        }
    }

    protected function replacePeople(DOMXPath $xpath, array $snapshot)
    {
        $committee = array_values($snapshot['committee'] ?? []);
        for ($i = 0; $i < 4; $i++) {
            $person = isset($committee[$i]) && is_array($committee[$i])
                ? $committee[$i]
                : [];
            $name = trim((string) ($person['name'] ?? '-')) ?: '-';
            $nip = trim((string) ($person['nip'] ?? '-')) ?: '-';
            $role = trim((string) ($person['role'] ?? ['Ketua', 'Sekretaris', 'Anggota', 'Anggota'][$i]));

            $this->replaceNthParagraphContaining(
                $xpath,
                'Nama',
                'Nama ' . "\t\t" . ': ' . $name,
                $i,
                ['satker', 'Nama Barang']
            );
            $this->replaceNthParagraphContaining($xpath, 'NIP', 'NIP ' . "\t\t" . ': ' . $nip, $i, ['NIP.']);
            $this->replaceNthParagraphContaining($xpath, 'Jabatan', 'Jabatan ' . "\t" . ': ' . $role, $i);
            $listLine = ($i + 1) . '.   ' . $name . '    ' . ($i + 1) . '...............................................';
            $this->replaceParagraphStartingWith($xpath, ($i + 1) . '.', $listLine, 0);
            $this->replaceParagraphStartingWith($xpath, ($i + 1) . '.', $listLine, 1);
        }
        $this->replaceLampiranPeople($xpath, $committee);

        $signatory = is_array($snapshot['signatory'] ?? null) ? $snapshot['signatory'] : [];
        $signatoryName = trim((string) ($signatory['name'] ?? '-')) ?: '-';
        $signatoryNip = trim((string) ($signatory['nip'] ?? '-')) ?: '-';
        $signatoryTitle = trim((string) ($signatory['title'] ?? 'Plt Sekretaris')) ?: 'Pejabat Penandatangan';
        $this->replaceParagraph($xpath, 'Plt Sekretaris', $signatoryTitle . ',');
        $this->replacePreviousNonEmptyParagraph($xpath, 'NIP.', $signatoryName);
        $this->replaceNthParagraphContaining($xpath, 'NIP.', 'NIP. ' . $signatoryNip, 0);
    }

    protected function replaceParagraphStartingWith(DOMXPath $xpath, $prefix, $replacement, $occurrence = 0)
    {
        $paragraphs = $xpath->query('//w:p');
        $found = 0;
        foreach ($paragraphs as $paragraph) {
            $text = trim($xpath->evaluate('string(.)', $paragraph));
            if (strpos($text, $prefix) !== 0) {
                continue;
            }
            if ($found++ !== (int) $occurrence) {
                continue;
            }
            $this->setParagraphText($xpath, $paragraph, $replacement);
            return;
        }
    }

    protected function replaceLampiranPeople(DOMXPath $xpath, array $committee)
    {
        $paragraphs = $xpath->query('//w:p[w:pPr/w:numPr/w:numId[@w:val="13"]]');
        foreach ($paragraphs as $index => $paragraph) {
            if ($index >= 4) {
                break;
            }
            $person = isset($committee[$index]) && is_array($committee[$index]) ? $committee[$index] : [];
            $name = trim((string) ($person['name'] ?? '-')) ?: '-';
            $this->setParagraphText(
                $xpath,
                $paragraph,
                $name . "\t\t\t" . ($index + 1) . '...............................................'
            );
        }
    }

    protected function replacePreviousNonEmptyParagraph(DOMXPath $xpath, $needle, $replacement)
    {
        $paragraphs = $xpath->query('//w:p');
        for ($i = 0; $i < $paragraphs->length; $i++) {
            $text = trim($xpath->evaluate('string(.)', $paragraphs->item($i)));
            if (strpos($text, $needle) === false) {
                continue;
            }
            for ($j = $i - 1; $j >= 0; $j--) {
                $previous = trim($xpath->evaluate('string(.)', $paragraphs->item($j)));
                if ($previous !== '') {
                    $this->setParagraphText($xpath, $paragraphs->item($j), $replacement);
                    return;
                }
            }
        }
    }

    protected function setParagraphText(DOMXPath $xpath, DOMElement $paragraph, $replacement)
    {
        $texts = $xpath->query('.//w:t', $paragraph);
        if ($texts->length) {
            $texts->item(0)->nodeValue = (string) $replacement;
            for ($i = $texts->length - 1; $i > 0; $i--) {
                $texts->item($i)->parentNode->removeChild($texts->item($i));
            }
            return;
        }

        $p = $paragraph;
        $r = $xpath->query('./w:r', $p)->item(0);
        if (!$r) {
            $r = $p->appendChild($p->ownerDocument->createElement('w:r'));
        }
        $t = $r->appendChild($p->ownerDocument->createElement('w:t'));
        $t->nodeValue = (string) $replacement;
    }

    protected function removeTemplateSignature(DOMXPath $xpath)
    {
        // image1.png is the sample stamp and image2.png is the sample personal
        // signature. Neither may be reused for a different current signatory.
        foreach (['rId7', 'rId8'] as $relationshipId) {
            $blips = $xpath->query('//a:blip[@r:embed="' . $relationshipId . '"]');
            foreach ($blips as $blip) {
                $drawing = $xpath->query('ancestor::w:drawing[1]', $blip)->item(0);
                if ($drawing && $drawing->parentNode) {
                    $drawing->parentNode->removeChild($drawing);
                }
            }
        }
    }

    protected function trimTrailingEmptyParagraphs(DOMXPath $xpath)
    {
        $body = $xpath->query('//w:body')->item(0);
        if (!$body) {
            return;
        }

        for ($i = $body->childNodes->length - 1; $i >= 0; $i--) {
            $node = $body->childNodes->item($i);
            if (!$node instanceof DOMElement || $node->localName !== 'p') {
                continue;
            }

            $text = trim($xpath->evaluate('string(.//w:t)', $node));
            $hasDrawing = $xpath->query('.//w:drawing', $node)->length > 0;
            if ($text !== '' || $hasDrawing) {
                break;
            }

            $body->removeChild($node);
        }
    }

    protected function monthName($date)
    {
        $months = [1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'];
        return $months[(int) $date->format('n')];
    }

    protected function formatDate($date)
    {
        return (int) $date->format('j') . ' ' . ucwords(strtolower($this->monthName($date))) . ' ' . $date->format('Y');
    }

    protected function dayName($date)
    {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        return $days[(int) $date->format('w')];
    }

    protected function numberToWords($number)
    {
        $words = ['Nol', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        if ($number < 12) return $words[$number];
        if ($number < 20) return $words[$number - 10] . ' Belas';
        if ($number < 100) return $words[(int) ($number / 10)] . ' Puluh' . ($number % 10 ? ' ' . $words[$number % 10] : '');
        return (string) $number;
    }

    protected function yearToWords($year)
    {
        if ($year >= 2000 && $year < 2100) {
            $rest = $year - 2000;
            return 'Dua Ribu' . ($rest ? ' ' . $this->numberToWords($rest) : '');
        }

        return (string) $year;
    }

    protected function alpha($index)
    {
        return chr(65 + ($index % 26));
    }

    protected function money($amount)
    {
        return number_format((float) $amount, 0, '.', '.');
    }
}
