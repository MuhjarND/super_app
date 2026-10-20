(function ($) {
    'use strict';

    function groupOptions($select, group) {
        return $select.find('option:not(:disabled)').filter(function () {
            return this.value !== '' && (group === 'all' || this.getAttribute('data-unit-code') === group);
        });
    }

    function syncToolbar(toolbar) {
        const $toolbar = $(toolbar);
        const $select = $($toolbar.attr('data-recipient-target'));
        $toolbar.find('input[data-recipient-group]').each(function () {
            const $options = groupOptions($select, this.getAttribute('data-recipient-group'));
            const count = $options.filter(':selected').length;
            this.checked = $options.length > 0 && count === $options.length;
            this.indeterminate = count > 0 && count < $options.length;
            this.disabled = $options.length === 0;
        });
    }

    $(document).on('change', '.recipient-group-toolbar input[data-recipient-group]', function () {
        const $toolbar = $(this).closest('.recipient-group-toolbar');
        const $select = $($toolbar.attr('data-recipient-target'));
        groupOptions($select, this.getAttribute('data-recipient-group')).prop('selected', this.checked);
        $select.trigger('change');
    });

    $(document).on('change', '.surat-recipient-select', function () {
        const id = '#' + this.id;
        $('.recipient-group-toolbar').filter(function () {
            return this.getAttribute('data-recipient-target') === id;
        }).each(function () { syncToolbar(this); });
    });

    $(function () {
        $('.recipient-group-toolbar').each(function () { syncToolbar(this); });
    });
})(jQuery);
