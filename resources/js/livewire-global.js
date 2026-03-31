// resources/js/livewire-global.js

function initLivewireEventListeners() {

    // window.addEventListener('open-modal', event => {
    //     $('#' + event.detail.name).modal('show');
    // });

    // window.addEventListener('close-modal', event => {
    //     $('#' + event.detail.name).modal('hide');
    //     $('.modal-backdrop').remove();
    //     $('body').removeClass('modal-open');
    // });

    // Card collapse (moved from blade)
    $(document).off('click', '[data-card-widget="collapse"]');
    $(document).on('click', '[data-card-widget="collapse"]', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        const button = $(this);
        const card = button.closest('.card');
        const cardBody = card.find('.card-body');
        const icon = button.find('i');

        if (card.hasClass('collapsed-card')) {
            cardBody.slideDown(300, function() {
                card.removeClass('collapsed-card');
                icon.removeClass('fa-plus').addClass('fa-minus');
            });
        } else {
            cardBody.slideUp(300, function() {
                card.addClass('collapsed-card');
                icon.removeClass('fa-minus').addClass('fa-plus');
            });
        }
    });
}

document.addEventListener('livewire:init', () => {
    initLivewireEventListeners();
});
