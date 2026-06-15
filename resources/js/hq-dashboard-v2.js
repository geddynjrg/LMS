import Chart from 'chart.js/auto';

const palette = ['#059669', '#0ea5e9', '#db2777', '#f59e0b', '#6366f1', '#14b8a6'];

let charts = {};

function destroyCharts() {
    Object.values(charts).forEach((chart) => chart.destroy());
    charts = {};
}

function doughnutChart(canvasId, labels, values) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels?.length) return;

    charts[canvasId] = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: palette,
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
            },
        },
    });
}

function barChart(canvasId, labels, values, horizontal = false) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels?.length) return;

    charts[canvasId] = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: palette,
                borderRadius: 8,
            }],
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: '#eef2f7' } },
            },
        },
    });
}

export function renderHqDashboardCharts(payload) {
    if (!payload) return;

    destroyCharts();

    if (document.getElementById('hqPortfolioChart')) {
        doughnutChart('hqPortfolioChart', payload.portfolio.labels, payload.portfolio.values);
        doughnutChart('hqDemographicsChart', payload.demographics.labels, payload.demographics.values);
        barChart('hqRecoveryChart', payload.recovery.labels, payload.recovery.values);
        barChart('hqConstituencyChart', payload.constituencies.labels, payload.constituencies.values);
    }

    if (document.getElementById('hqSectorChart')) {
        barChart('hqSectorChart', payload.sectors.labels, payload.sectors.values, true);
    }

    if (document.getElementById('hqAnalyzeChart')) {
        const dimension = payload.activeDimension || 'demographics';
        const dataset = payload[dimension] || payload.demographics;
        doughnutChart('hqAnalyzeChart', dataset.labels, dataset.values);
    }
}

window.renderHqDashboardCharts = renderHqDashboardCharts;

function renderFromWindowPayload() {
    if (window.__hqDashboardChartPayload) {
        renderHqDashboardCharts(window.__hqDashboardChartPayload);
    }
}

document.addEventListener('livewire:init', () => {
    Livewire.on('hq-dashboard-charts-updated', ({ charts: payload }) => {
        window.__hqDashboardChartPayload = payload;
        requestAnimationFrame(() => renderHqDashboardCharts(payload));
    });

    Livewire.hook('morph.updated', () => {
        requestAnimationFrame(renderFromWindowPayload);
    });
});

document.addEventListener('DOMContentLoaded', renderFromWindowPayload);
document.addEventListener('livewire:navigated', renderFromWindowPayload);
