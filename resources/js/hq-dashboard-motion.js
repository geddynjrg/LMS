function easeOutCubic(t) {
    return 1 - Math.pow(1 - t, 3);
}

function parseCountValue(raw) {
    if (raw === null || raw === undefined || raw === '') {
        return null;
    }

    const cleaned = String(raw).replace(/,/g, '').trim();
    const value = Number(cleaned);

    return Number.isFinite(value) ? value : null;
}

function formatCountValue(value, element) {
    const prefix = element.dataset.countPrefix || '';
    const suffix = element.dataset.countSuffix || '';
    const decimals = Number(element.dataset.countDecimals || 0);
    const compact = element.dataset.countCompact === 'true';

    let display = value;

    if (compact) {
        if (value >= 1_000_000_000) {
            display = `${(value / 1_000_000_000).toFixed(1)}B`;
        } else if (value >= 1_000_000) {
            display = `${(value / 1_000_000).toFixed(1)}M`;
        } else if (value >= 1_000) {
            display = `${(value / 1_000).toFixed(1)}K`;
        } else {
            display = Math.round(value).toLocaleString();
        }
    } else if (decimals > 0) {
        display = value.toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    } else {
        display = Math.round(value).toLocaleString();
    }

    return `${prefix}${display}${suffix}`;
}

function animateCountElement(element, force = false) {
    const target = parseCountValue(element.dataset.countUp);

    if (target === null) {
        return;
    }

    const previousTarget = parseCountValue(element.dataset.countCurrent);
    const startValue = force || previousTarget === null ? 0 : previousTarget;

    if (!force && previousTarget === target) {
        element.textContent = formatCountValue(target, element);
        return;
    }

    element.dataset.countCurrent = String(target);

    const duration = Number(element.dataset.countDuration || 900);
    const startTime = performance.now();

    const tick = (now) => {
        const progress = Math.min((now - startTime) / duration, 1);
        const eased = easeOutCubic(progress);
        const current = startValue + (target - startValue) * eased;

        element.textContent = formatCountValue(current, element);

        if (progress < 1) {
            requestAnimationFrame(tick);
        } else {
            element.textContent = formatCountValue(target, element);
        }
    };

    requestAnimationFrame(tick);
}

export function animateDashboardCounts(root = document) {
    root.querySelectorAll('[data-count-up]').forEach((element) => {
        animateCountElement(element);
    });
}

function restartInboxReveal(root = document) {
    root.querySelectorAll('.hq-inbox-feed-item').forEach((item) => {
        item.classList.remove('hq-inbox-feed-item--visible');
        void item.offsetWidth;
        item.classList.add('hq-inbox-feed-item--visible');
    });
}

function animateShareBars(root = document) {
    root.querySelectorAll('.application-volume-share-fill').forEach((bar) => {
        const targetWidth = bar.dataset.shareWidth || bar.style.width;

        if (!targetWidth) {
            return;
        }

        bar.dataset.shareWidth = targetWidth;
        bar.style.width = '0%';

        requestAnimationFrame(() => {
            bar.style.width = targetWidth;
        });
    });
}

function animateProgressBars(root = document) {
    root.querySelectorAll('.nat-kpi-progress-fill').forEach((bar) => {
        const target = Number(bar.dataset.progress || 0);
        bar.style.width = '0%';

        requestAnimationFrame(() => {
            bar.style.width = `${Math.min(100, Math.max(0, target))}%`;
        });
    });
}

export function initDashboardMotion() {
    animateDashboardCounts();
    restartInboxReveal();
    animateShareBars();
    animateProgressBars();

    document.addEventListener('hq-dashboard-theme-changed', () => {
        animateDashboardCounts();
    });
}

if (typeof window !== 'undefined') {
    window.animateDashboardCounts = animateDashboardCounts;
    window.initDashboardMotion = initDashboardMotion;
}

document.addEventListener('DOMContentLoaded', () => {
    initDashboardMotion();
});

document.addEventListener('livewire:navigated', () => {
    initDashboardMotion();
});

document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', ({ el }) => {
        requestAnimationFrame(() => {
            animateDashboardCounts(el);
            restartInboxReveal(el);
            animateShareBars(el);
            animateProgressBars(el);
        });
    });
});
