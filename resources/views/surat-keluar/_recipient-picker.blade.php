@php
    $recipientSelectId = $prefix === 'edit' ? 'editPenerimaInternal' : 'penerimaInternal';
    $recipientGroups = [
        'all' => 'Pilih Semua',
        'PIMPINAN' => 'Pimpinan',
        'HAKIM_TINGGI' => 'Hakim Tinggi',
        'KEPANITERAAN' => 'Kepaniteraan',
        'KESEKRETARIATAN' => 'Kesekretariatan',
    ];
@endphp
<div class="recipient-group-toolbar" data-recipient-target="#{{ $recipientSelectId }}" role="group" aria-label="Pilih kelompok penerima">
    @foreach($recipientGroups as $code => $label)
        <label class="recipient-select-all">
            <input type="checkbox" data-recipient-group="{{ $code }}" @if($code === 'all') id="{{ $prefix === 'edit' ? 'selectAllEdit' : 'selectAll' }}" @endif>
            <span>{{ $label }}</span>
        </label>
    @endforeach
</div>
<select class="form-control select2 surat-recipient-select" name="penerima_internal[]" id="{{ $recipientSelectId }}" multiple>
    @foreach($users as $recipient)
        <option value="{{ $recipient->id }}" data-unit-code="{{ optional($recipient->unit)->kode ?: optional(optional($recipient->jabatan)->unit)->kode }}">{{ $recipient->name }}{{ $recipient->jabatan ? ' (' . $recipient->jabatan->nama . ')' : '' }}</option>
    @endforeach
</select>
