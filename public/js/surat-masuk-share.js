(function (window, document) {
    'use strict';

    var modalId = 'suratMasukShareModal';
    var currentPayload = null;

    function value(source, key) {
        if (!source) return '';
        return String(source[key] || '').trim();
    }

    function payloadFromElement(element) {
        return {
            url: value(element.dataset, 'shareUrl'),
            number: value(element.dataset, 'shareNumber'),
            sender: value(element.dataset, 'shareSender'),
            subject: value(element.dataset, 'shareSubject'),
            date: value(element.dataset, 'shareDate')
        };
    }

    function shareTitle(payload) {
        return payload.number ? 'Surat Masuk ' + payload.number : 'Surat Masuk';
    }

    function shareText(payload, includeUrl) {
        var lines = ['Surat Masuk'];
        if (payload.number) lines.push('Nomor: ' + payload.number);
        if (payload.sender) lines.push('Pengirim: ' + payload.sender);
        if (payload.subject) lines.push('Perihal: ' + payload.subject);
        if (payload.date) lines.push('Tanggal: ' + payload.date);
        if (includeUrl && payload.url) lines.push('', 'Buka surat: ' + payload.url);
        return lines.join('\n');
    }

    function ensureModal() {
        var modal = document.getElementById(modalId);
        if (modal) return modal;

        var wrapper = document.createElement('div');
        wrapper.innerHTML = [
            '<div class="modal fade" id="' + modalId + '" tabindex="-1" role="dialog" aria-labelledby="suratMasukShareModalLabel" aria-hidden="true">',
            '  <div class="modal-dialog modal-dialog-centered" role="document">',
            '    <div class="modal-content" style="border:0;border-radius:16px;overflow:hidden;box-shadow:0 24px 60px rgba(15,23,42,.2);">',
            '      <div class="modal-header" style="border-bottom:1px solid #eef2f7;">',
            '        <div><h5 class="modal-title font-weight-bold" id="suratMasukShareModalLabel"><i class="fas fa-paper-plane mr-2 text-primary"></i>Kirim Surat Masuk</h5><small class="text-muted">Pilih aplikasi tujuan atau salin tautannya.</small></div>',
            '        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>',
            '      </div>',
            '      <div class="modal-body">',
            '        <div class="p-3 mb-3" style="border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">',
            '          <div class="small text-uppercase text-muted font-weight-bold mb-1">Surat yang dibagikan</div>',
            '          <div id="suratMasukShareSummary" class="font-weight-bold text-dark"></div>',
            '          <div id="suratMasukShareSubject" class="small text-muted mt-1"></div>',
            '        </div>',
            '        <div class="row">',
            '          <div class="col-6 mb-2"><a id="suratMasukShareWhatsapp" class="btn btn-block text-white" style="background:#16a34a;border-radius:11px;" target="_blank" rel="noopener"><i class="fab fa-whatsapp mr-2"></i>WhatsApp</a></div>',
            '          <div class="col-6 mb-2"><a id="suratMasukShareTelegram" class="btn btn-block text-white" style="background:#0284c7;border-radius:11px;" target="_blank" rel="noopener"><i class="fab fa-telegram-plane mr-2"></i>Telegram</a></div>',
            '          <div class="col-6 mb-2"><a id="suratMasukShareEmail" class="btn btn-block btn-outline-secondary" style="border-radius:11px;"><i class="fas fa-envelope mr-2"></i>Email</a></div>',
            '          <div class="col-6 mb-2"><button type="button" id="suratMasukCopyLink" class="btn btn-block btn-outline-primary" style="border-radius:11px;"><i class="fas fa-link mr-2"></i>Salin Link</button></div>',
            '        </div>',
            '        <div class="small text-muted mt-2"><i class="fas fa-lock mr-1"></i>Penerima harus login dan memiliki hak akses untuk membuka surat.</div>',
            '      </div>',
            '    </div>',
            '  </div>',
            '</div>'
        ].join('');

        modal = wrapper.firstChild;
        document.body.appendChild(modal);
        document.getElementById('suratMasukCopyLink').addEventListener('click', copyCurrentLink);
        return modal;
    }

    function fillModal(payload) {
        ensureModal();
        var textWithUrl = shareText(payload, true);
        document.getElementById('suratMasukShareSummary').textContent = shareTitle(payload);
        document.getElementById('suratMasukShareSubject').textContent = payload.subject || '-';
        document.getElementById('suratMasukShareWhatsapp').href = 'https://wa.me/?text=' + encodeURIComponent(textWithUrl);
        document.getElementById('suratMasukShareTelegram').href = 'https://t.me/share/url?url=' + encodeURIComponent(payload.url) + '&text=' + encodeURIComponent(shareText(payload, false));
        document.getElementById('suratMasukShareEmail').href = 'mailto:?subject=' + encodeURIComponent(shareTitle(payload)) + '&body=' + encodeURIComponent(textWithUrl);
    }

    function feedback(message, success) {
        if (window.toastr) {
            window.toastr[success ? 'success' : 'error'](message);
        } else if (window.Swal) {
            window.Swal.fire({ icon: success ? 'success' : 'error', title: success ? 'Berhasil' : 'Gagal', text: message, timer: success ? 1800 : undefined, showConfirmButton: !success });
        } else {
            window.alert(message);
        }
    }

    function legacyCopy(text) {
        var input = document.createElement('textarea');
        input.value = text;
        input.setAttribute('readonly', 'readonly');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        var copied = document.execCommand('copy');
        document.body.removeChild(input);
        return copied;
    }

    function copyCurrentLink() {
        if (!currentPayload || !currentPayload.url) {
            feedback('Tautan surat tidak tersedia.', false);
            return;
        }

        var copyPromise = navigator.clipboard && window.isSecureContext
            ? navigator.clipboard.writeText(currentPayload.url)
            : Promise.resolve(legacyCopy(currentPayload.url));

        copyPromise.then(function (result) {
            if (result === false) throw new Error('copy-failed');
            feedback('Tautan surat berhasil disalin.', true);
        }).catch(function () {
            window.prompt('Salin tautan surat berikut:', currentPayload.url);
        });
    }

    function openFallback(payload) {
        currentPayload = payload;
        fillModal(payload);
        if (window.jQuery && window.jQuery.fn.modal) {
            window.jQuery('#' + modalId).modal('show');
        } else {
            window.prompt('Salin tautan surat berikut:', payload.url);
        }
    }

    function share(payload) {
        if (!payload.url) {
            feedback('Tautan surat tidak tersedia.', false);
            return;
        }

        if (navigator.share) {
            navigator.share({ title: shareTitle(payload), text: shareText(payload, false), url: payload.url })
                .catch(function (error) {
                    if (!error || error.name !== 'AbortError') openFallback(payload);
                });
            return;
        }
        openFallback(payload);
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('.js-share-surat-masuk');
        if (!trigger) return;
        event.preventDefault();
        share(payloadFromElement(trigger));
    });

    window.SuratMasukShare = { share: share, payloadFromElement: payloadFromElement };
})(window, document);
