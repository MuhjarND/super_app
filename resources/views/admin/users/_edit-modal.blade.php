<div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content user-form-modal">
            <div class="modal-header user-form-header">
                <div>
                    <h5 class="modal-title">Edit User</h5>
                    <div class="user-form-subtitle">{{ $user->name }}{{ $user->nip ? ' / ' . $user->nip : '' }}</div>
                </div>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    @include('admin.users._form-fields', ['mode' => 'edit'])
                </div>
                <div class="modal-footer user-form-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
