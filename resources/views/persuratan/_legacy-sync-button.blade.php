@if(auth()->user()->hasRole('super_admin'))
    <button type="button" class="btn btn-outline-primary mr-2 legacy-persuratan-sync-btn"
        title="Sinkronisasi dari simisol.pta-papuabarat.go.id">
        <i class="fas fa-sync-alt mr-1"></i> Sinkronisasi SIMISOL
    </button>

    @once
        @push('styles')
            <style>
                .legacy-persuratan-sync-btn {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                @media (max-width: 767.98px) {
                    .legacy-persuratan-sync-btn {
                        width: 100%;
                        margin: 0 0 8px !important;
                    }
                }
            </style>
        @endpush

        @push('scripts')
            <script>
                $(document).on('click', '.legacy-persuratan-sync-btn', function () {
                    if (!confirm('Sinkronisasi akan mengambil data Surat Masuk dan Surat Keluar terbaru dari SIMISOL. Lanjutkan?')) {
                        return;
                    }

                    const buttons = $('.legacy-persuratan-sync-btn');
                    buttons.prop('disabled', true).html('<i class="fas fa-sync-alt fa-spin mr-1"></i> Sinkronisasi...');

                    $.ajax({
                        url: '{{ route('admin.legacy-persuratan.sync') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            showToast(response.message, 'success');
                            setTimeout(function () {
                                window.location.reload();
                            }, 1800);
                        },
                        error: function (xhr) {
                            showToast(xhr.responseJSON?.message || 'Sinkronisasi SIMISOL gagal.', 'error');
                        },
                        complete: function () {
                            buttons.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i> Sinkronisasi SIMISOL');
                        }
                    });
                });
            </script>
        @endpush
    @endonce
@endif
