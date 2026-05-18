@php($signaturePadId = $id ?? ('signaturePad' . uniqid()))

<div class="signature-pad-field js-signature-pad" data-required="{{ !empty($required) ? '1' : '0' }}">
    <div class="signature-pad-label">
        <span>{{ $label ?? 'Tanda Tangan' }}</span>
        <button type="button" class="btn btn-outline-secondary btn-sm signature-pad-clear" data-signature-clear="1">
            Hapus
        </button>
    </div>
    <canvas id="{{ $signaturePadId }}" class="signature-pad-canvas"></canvas>
    <input type="hidden" name="{{ $name ?? 'signature_data' }}" data-signature-input="1">
    <div class="signature-pad-hint">
        {{ $hint ?? 'Gunakan mouse atau layar sentuh untuk membubuhkan tanda tangan.' }}
    </div>
</div>
