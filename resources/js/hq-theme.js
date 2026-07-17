const STORAGE_KEY = 'uwezo-dashboard-theme';

export function getDashboardTheme() {
    if (typeof document === 'undefined') {
        return 'light';
    }

    return document.documentElement.getAttribute('data-hq-theme') === 'dark' ? 'dark' : 'light';
}

export function applyDashboardTheme(theme) {
    const next = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-hq-theme', next);

    try {
        localStorage.setItem(STORAGE_KEY, next);
    } catch (_) {
        // Ignore storage failures (private browsing, etc.)
    }

    document.dispatchEvent(new CustomEvent('hq-dashboard-theme-changed', { detail: { theme: next } }));
}

export function toggleDashboardTheme() {
    applyDashboardTheme(getDashboardTheme() === 'dark' ? 'light' : 'dark');
}

export function initDashboardTheme() {
    let stored = 'light';

    try {
        stored = localStorage.getItem(STORAGE_KEY) || 'light';
    } catch (_) {
        stored = 'light';
    }

    applyDashboardTheme(stored === 'dark' ? 'dark' : 'light');

    document.querySelectorAll('[data-hq-theme-toggle]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            toggleDashboardTheme();
        });
    });

    document.addEventListener('hq-dashboard-theme-changed', syncThemeToggleLabels);
    syncThemeToggleLabels();
}

function syncThemeToggleLabels() {
    const isDark = getDashboardTheme() === 'dark';

    document.querySelectorAll('[data-hq-theme-toggle]').forEach((button) => {
        const label = button.querySelector('[data-hq-theme-label]');
        const icon = button.querySelector('[data-hq-theme-icon]');

        if (label) {
            label.textContent = isDark ? 'Light mode' : 'Dark mode';
        }

        if (icon) {
            icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        }

        button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        button.title = isDark ? 'Switch to light theme' : 'Switch to dark theme';
    });
}

if (typeof window !== 'undefined') {
    window.initDashboardTheme = initDashboardTheme;
    window.toggleDashboardTheme = toggleDashboardTheme;
    window.getDashboardTheme = getDashboardTheme;
}
