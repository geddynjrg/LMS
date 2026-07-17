import './bootstrap';

import Swal from 'sweetalert2';
import 'animate.css';

window.Swal = Swal;

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4500,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    },
    showClass: { popup: 'animate__animated animate__fadeInRight animate__faster' },
    hideClass: { popup: 'animate__animated animate__fadeOutRight animate__faster' },
    customClass: {
        popup: 'premium-toast',
        title: 'premium-toast-title',
        htmlContainer: 'premium-toast-text',
        timerProgressBar: 'premium-toast-progress'
    }
});

// 3. Make the Toast helper global too
window.Toast = Toast;

window.addEventListener('swal:toast', event => {
    const data = event.detail[0] || event.detail;
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };

    Toast.fire({
        icon: data.type,
        iconColor: colors[data.type] || colors.info,
        title: data.title,
        text: data.message,
        showCloseButton: true,
    });
});

import './select2-init.js';
import './progress-bar.js';
import './navbar-search.js';
import './livewire-global.js';
import './hq-dashboard-v2.js';
import './const-dashboard-v2.js';
import { initDashboardTheme } from './hq-theme.js';
import { initDashboardMotion } from './hq-dashboard-motion.js';

document.addEventListener('DOMContentLoaded', () => {
    initDashboardTheme();
    initDashboardMotion();
});
