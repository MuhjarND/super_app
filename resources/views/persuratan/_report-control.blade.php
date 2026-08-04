@php
    $reportModalId = $modalId ?? 'persuratanReportModal';
    $reportStart = now('Asia/Jayapura')->startOfMonth()->format('Y-m-d');
    $reportEnd = now('Asia/Jayapura')->format('Y-m-d');
@endphp

<button type="button" class="btn btn-outline-primary mr-1" data-toggle="modal" data-target="#{{ $reportModalId }}">
    <i class="fas fa-print mr-1"></i> Cetak Laporan
</button>

<div class="modal fade text-left" id="{{ $reportModalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border:0;border-radius:16px;overflow:hidden;">
            <form method="GET" action="{{ $action }}" target="_blank" data-report-period-form>
                <div class="modal-header" style="border-bottom:1px solid #e5e7eb;">
                    <div>
                        <h5 class="modal-title mb-1"><i class="fas fa-print mr-2 text-primary"></i>{{ $title }}</h5>
                        <div class="text-muted small">Pilih rentang tanggal surat yang akan dicetak.</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group mb-md-0">
                            <label>Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_mulai" value="{{ $reportStart }}" required data-report-start>
                        </div>
                        <div class="col-md-6 form-group mb-0">
                            <label>Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_selesai" value="{{ $reportEnd }}" required data-report-end>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e5e7eb;">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-file-pdf mr-1"></i> Buka PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            $(document).on('change', '[data-report-start]', function () {
                const $form = $(this).closest('[data-report-period-form]');
                const $end = $form.find('[data-report-end]');
                $end.attr('min', this.value || '');
                if (this.value && $end.val() && $end.val() < this.value) {
                    $end.val(this.value);
                }
            });
        </script>
    @endpush
@endonce
