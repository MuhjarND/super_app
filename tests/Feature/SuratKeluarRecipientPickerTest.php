<?php

namespace Tests\Feature;

use App\Jabatan;
use App\Unit;
use App\User;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class SuratKeluarRecipientPickerTest extends TestCase
{
    /** @dataProvider formPrefixes */
    public function test_recipient_groups_share_one_toolbar_and_map_to_user_units($prefix, $selectId)
    {
        $users = collect([
            $this->recipient(1, 'PIMPINAN'),
            $this->recipient(2, 'HAKIM_TINGGI'),
            $this->recipient(3, 'KEPANITERAAN'),
            $this->recipient(4, 'KESEKRETARIATAN'),
        ]);
        $html = view('surat-keluar._recipient-picker', compact('prefix', 'users'))->render();
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $toolbar = $xpath->query('//div[@data-recipient-target="#' . $selectId . '"]');

        $this->assertSame(1, $toolbar->length);
        $this->assertSame(5, $xpath->query('.//input[@type="checkbox"]', $toolbar->item(0))->length);
        $this->assertSame(0, $xpath->query('.//input[@name]', $toolbar->item(0))->length);
        $labels = [];
        foreach ($xpath->query('.//label/span', $toolbar->item(0)) as $label) {
            $labels[] = trim($label->textContent);
        }
        $this->assertSame(['Pilih Semua', 'Pimpinan', 'Hakim Tinggi', 'Kepaniteraan', 'Kesekretariatan'], $labels);
        foreach ($users as $user) {
            $this->assertSame(1, $xpath->query('//select[@id="' . $selectId . '"]/option[@value="' . $user->id . '"][@data-unit-code="' . $user->unit->kode . '"]')->length);
        }
        $this->assertSame(1, $xpath->query('//select[@id="' . $selectId . '"][@name="penerima_internal[]"][@multiple]')->length);
    }

    public function test_recipient_without_assigned_unit_uses_position_unit_as_fallback()
    {
        $recipient = $this->recipient(5, 'KEPANITERAAN');
        $position = new Jabatan(['nama' => 'Panitera']);
        $position->setRelation('unit', $recipient->unit);
        $recipient->setRelation('unit', null);
        $recipient->setRelation('jabatan', $position);
        $html = view('surat-keluar._recipient-picker', [
            'prefix' => 'create', 'users' => collect([$recipient]),
        ])->render();

        $this->assertStringContainsString('data-unit-code="KEPANITERAAN"', $html);
        $this->assertStringContainsString('(Panitera)', $html);
    }

    public function test_recipient_name_is_escaped()
    {
        $recipient = $this->recipient(6, 'PIMPINAN');
        $recipient->name = '<script>alert(1)</script>';
        $html = view('surat-keluar._recipient-picker', [
            'prefix' => 'create', 'users' => collect([$recipient]),
        ])->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function formPrefixes()
    {
        return [['create', 'penerimaInternal'], ['edit', 'editPenerimaInternal']];
    }

    protected function recipient($id, $unitCode)
    {
        $user = new User(['name' => 'Pegawai ' . $id]);
        $user->id = $id;
        $user->setRelation('unit', new Unit(['kode' => $unitCode]));
        $user->setRelation('jabatan', null);

        return $user;
    }
}
