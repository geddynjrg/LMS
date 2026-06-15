// resources/js/navbar-search.js

$(document).off('click', '[data-widget="navbar-search"]').on('click', '[data-widget="navbar-search"]', function (e) {
    e.preventDefault();

    const searchBlock = $('.navbar-search-block');
    const isCloseBtn = $(this).find('.fas.fa-times').length > 0;

    if (isCloseBtn) {
        searchBlock.removeClass('navbar-search-block-open');
    } else {
        searchBlock.addClass('navbar-search-block-open');
        setTimeout(() => {
            $('#nav-search-input').focus();
        }, 150);
    }
});

function handleGlobalSearch(e) {
    const activeElem = document.activeElement.tagName;
    if (activeElem === 'INPUT' || activeElem === 'TEXTAREA' || activeElem === 'SELECT') return;

    if (e.key === '/') {
        e.preventDefault();
        const searchToggle = document.querySelector('[data-widget="navbar-search"]');
        if (searchToggle) {
            const searchBlock = document.querySelector('.navbar-search-block');
            if (searchBlock && !searchBlock.classList.contains('navbar-search-block-open')) {
                searchToggle.click();
            }
            setTimeout(() => {
                const input = document.getElementById('nav-search-input');
                if (input) input.focus();
            }, 100);
        }
    }

    if (e.key === 'Escape') {
        const searchBlock = $('.navbar-search-block');
        if (searchBlock.hasClass('navbar-search-block-open')) {
            searchBlock.removeClass('navbar-search-block-open');
        }
    }
}

$(document).on('mousedown', function (e) {
    const searchBlock = $('.navbar-search-block');
    const searchToggle = $('[data-widget="navbar-search"]');

    if (!searchBlock.is(e.target) && searchBlock.has(e.target).length === 0 &&
        !searchToggle.is(e.target) && searchToggle.has(e.target).length === 0) {
        if (searchBlock.hasClass('navbar-search-block-open')) {
            searchBlock.removeClass('navbar-search-block-open');
        }
    }
});

document.addEventListener('keydown', handleGlobalSearch);
