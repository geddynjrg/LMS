// resources/js/progress-bar.js

let progressInterval = null;

document.addEventListener('live', () => {
    document.body.classList.add('loading-process');
    const bar = document.getElementById('livewire-main-progress');
    if (!bar) return;

    clearInterval(progressInterval);
    bar.style.transition = 'width 300ms ease-out, opacity 150ms';
    bar.style.opacity = '1';
    bar.style.width = '10%';

    let width = 10;
    progressInterval = setInterval(() => {
        if (width < 30) {
            width += 20;
        } else if (width < 60) {
            width += 10;
        } else if (width < 90) {
            width += 2;
        }
        bar.style.width = width + '%';
    }, 200);

});

document.addEventListener('lived', () => {
    document.body.classList.remove('loading-process');
    const bar = document.getElementById('livewire-main-progress');
    if (!bar) return;

    clearInterval(progressInterval);
    bar.style.width = '100%';

    setTimeout(() => {
        bar.style.opacity = '0';
        setTimeout(() => {
            bar.style.transition = 'none';
            bar.style.width = '0';
        }, 300);
    }, 250);
});

function cleanModals()
{
    $('.modal.show').modal('hide');

    $('.modal-backdrop').remove();

    $('body').removeClass('modal-open').css('padding-right', '');
}
