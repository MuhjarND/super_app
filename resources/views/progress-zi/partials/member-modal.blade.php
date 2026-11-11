<div class="modal fade" id="ziMembersModal" tabindex="-1" role="dialog" aria-labelledby="ziMembersTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div><h2 class="modal-title" id="ziMembersTitle">Penanggung jawab area</h2><p class="text-muted mb-0 mt-1" id="ziMembersArea"></p></div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup daftar penanggung jawab"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="ziMembersContent"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#ziMembersModal').on('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        var content = document.getElementById('ziMembersContent');
        content.textContent = '';
        document.getElementById('ziMembersArea').textContent = trigger ? trigger.getAttribute('data-area-title') : '';
        var template = trigger ? document.getElementById(trigger.getAttribute('data-members-template')) : null;
        if (template) content.appendChild(template.content.cloneNode(true));
    });
});
</script>
@endpush
