// resources/js/select2-init.js

window.initSelect2 = function () {
    if (!window.jQuery || !jQuery.fn.select2) return;

    $('select.select2').each(function () {
        const el = $(this);
        if (el.hasClass('select2-hidden-accessible')) return;

        let propertyName = el.attr('wire:model') || el.attr('wire:model.live');

        if (!propertyName && el.attr('id')) {
            propertyName = el.attr('id').replace('select2-', '');
        }

        el.select2({
            width: '100%',
            allowClear: false,
        });

        const currentVal = el.val();
        if (currentVal) {
            el.val(currentVal).trigger('change.select2');
        }

        el.on('change', function () {
            let component = Livewire.find(el.closest('[wire\\:id]').attr('wire:id'));
            if (component && propertyName) {
                component.set(propertyName, el.val());
            }
        });
    });
};
