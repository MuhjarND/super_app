<div class="card leave-simple-card">
    <div class="card-header">{{ $canVerifyDocuments ? 'Verifikasi Dokumen' : 'Dokumen Pendukung' }}</div>
    <div class="card-body p-0">
        @forelse($leaveRequest->documents as $document)
            <div class="leave-list-row">
                <div>
                    <div class="leave-list-title">{{ $document->original_name }}</div>
                    <div class="leave-list-meta">{{ $document->document_type_label }} | {{ $document->is_verified ? 'Terverifikasi' : 'Belum diverifikasi' }}</div>
                </div>
                <div class="app-action-group">
                    <a href="{{ route('cuti.documents.show', [$leaveRequest, $document]) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-paperclip mr-1"></i> Buka
                    </a>
                    @if($canVerifyDocuments)
                        <form action="{{ route('cuti.approval.verify-document', $leaveApproval) }}" method="POST" class="d-inline-block">
                            @csrf
                            <input type="hidden" name="document_id" value="{{ $document->id }}">
                            <input type="hidden" name="is_verified" value="{{ $document->is_verified ? 0 : 1 }}">
                            <input type="hidden" name="verification_note" value="{{ $document->is_verified ? 'Verifikasi dibatalkan.' : 'Dokumen valid.' }}">
                            <button type="submit" class="btn btn-{{ $document->is_verified ? 'outline-warning' : 'outline-success' }} btn-sm">
                                <i class="fas {{ $document->is_verified ? 'fa-undo' : 'fa-check' }} mr-1"></i>{{ $document->is_verified ? 'Batalkan' : 'Verifikasi' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="leave-list-row"><div class="leave-list-meta">Belum ada dokumen pendukung.</div></div>
        @endforelse
    </div>
</div>
