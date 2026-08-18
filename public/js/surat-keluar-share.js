(function (window, document) {
    'use strict';

    function value(source, key) {
        if (!source) return '';
        return String(source[key] || '').trim();
    }

    function payloadFromElement(element) {
        return {
            url: value(element.dataset, 'shareUrl'),
            label: value(element.dataset, 'shareLabel') || 'Surat Keluar',
            number: value(element.dataset, 'shareNumber'),
            recipient: value(element.dataset, 'shareRecipient'),
            subject: value(element.dataset, 'shareSubject'),
            date: value(element.dataset, 'shareDate'),
            accessNote: value(element.dataset, 'shareAccessNote')
        };
    }

    function fallbackShare(payload) {
        if (!payload.url) {
            window.alert('Tautan surat keluar tidak tersedia.');
            return;
        }

        var lines = [payload.label];
        if (payload.number) lines.push('Nomor: ' + payload.number);
        if (payload.recipient) lines.push('Tujuan: ' + payload.recipient);
        if (payload.subject) lines.push('Perihal: ' + payload.subject);
        if (payload.date) lines.push('Tanggal: ' + payload.date);
        lines.push('', 'Buka surat: ' + payload.url);

        if (window.navigator.share) {
            window.navigator.share({
                title: payload.label + (payload.number ? ' ' + payload.number : ''),
                text: lines.slice(0, -2).join('\n'),
                url: payload.url
            }).catch(function (error) {
                if (!error || error.name !== 'AbortError') {
                    window.prompt('Salin tautan surat berikut:', payload.url);
                }
            });
            return;
        }

        window.prompt('Salin tautan surat berikut:', payload.url);
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('.js-share-surat');
        if (!trigger) return;

        event.preventDefault();
        event.stopPropagation();

        var payload = payloadFromElement(trigger);
        var shareApi = window.SuratShare || window.SuratMasukShare;

        if (shareApi && typeof shareApi.share === 'function') {
            shareApi.share(payload);
            return;
        }

        fallbackShare(payload);
    });
})(window, document);
