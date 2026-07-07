import Chart from 'chart.js/auto';

const palette = ['#059669', '#0ea5e9', '#db2777', '#f59e0b', '#6366f1', '#14b8a6'];

let charts = {};

const applicationVolumeLabelsPlugin = {
    id: 'applicationVolumeLabels',
    afterDatasetsDraw(chart) {
        if (chart.config?.options?.plugins?.applicationVolumeLabels === false) {
            return;
        }

        const { ctx, chartArea } = chart;
        const dataset = chart.data.datasets[0];

        if (!dataset) {
            return;
        }

        const meta = chart.getDatasetMeta(0);

        meta.data.forEach((bar, index) => {
            const value = dataset.data[index];
            const label = `${Number(value).toLocaleString()}`;

            ctx.save();
            ctx.fillStyle = '#334155';
            ctx.font = '600 12px "Source Sans Pro", sans-serif';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'middle';

            const x = Math.min(bar.x + 8, chartArea.right - ctx.measureText(label).width - 4);
            ctx.fillText(label, x, bar.y);
            ctx.restore();
        });
    },
};

Chart.register(applicationVolumeLabelsPlugin);

function destroyChart(canvasId) {
    if (charts[canvasId]) {
        charts[canvasId].destroy();
        delete charts[canvasId];
    }
}

function destroyCharts() {
    Object.keys(charts).forEach((canvasId) => destroyChart(canvasId));
}

function doughnutChart(canvasId, labels, values) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels?.length) return;

    destroyChart(canvasId);

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

    destroyChart(canvasId);

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

/**
 * Ranked horizontal lollipop chart for constituency application counts.
 * Sorted highest → lowest; count labels are drawn at the end of each bar.
 */
export function renderApplicationVolumeChart(canvasId, payload) {
    const canvas = document.getElementById(canvasId);

    if (!canvas || !payload?.labels?.length) {
        return;
    }

    destroyChart(canvasId);

    const values = payload.values.map((value) => Number(value) || 0);
    const maxValue = Math.max(...values, 1);
    const wrap = canvas.closest('.application-volume-chart-wrap');

    if (wrap) {
        const height = Math.min(560, Math.max(260, payload.labels.length * 36 + 48));
        wrap.style.height = `${height}px`;
    }

    const barColors = values.map((value) => {
        const ratio = value / maxValue;
        const alpha = 0.45 + ratio * 0.55;

        return `rgba(5, 150, 105, ${alpha.toFixed(2)})`;
    });

    charts[canvasId] = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: payload.labels,
            datasets: [{
                data: values,
                backgroundColor: barColors,
                borderColor: '#047857',
                borderWidth: 1,
                borderRadius: 999,
                barThickness: 14,
                maxBarThickness: 16,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: { right: 56 },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `${Number(context.raw).toLocaleString()} applications`,
                    },
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    suggestedMax: maxValue * 1.12,
                    grid: { color: '#eef2f7' },
                    ticks: {
                        precision: 0,
                        callback: (value) => Number(value).toLocaleString(),
                    },
                    title: {
                        display: true,
                        text: 'Applications',
                        color: '#6b7280',
                        font: { size: 11, weight: '600' },
                    },
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        autoSkip: false,
                        color: '#374151',
                        font: { size: 12, weight: '600' },
                    },
                },
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

    if (document.getElementById('hqApplicationVolumeChart')) {
        renderApplicationVolumeChart('hqApplicationVolumeChart', payload.applicationVolume);
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

window.renderApplicationVolumeChart = renderApplicationVolumeChart;
window.renderHqDashboardCharts = renderHqDashboardCharts;

function renderFromWindowPayload() {
    if (window.__hqDashboardChartPayload) {
        renderHqDashboardCharts(window.__hqDashboardChartPayload);
    }

    if (window.__secretariatApplicationVolumePayload) {
        renderApplicationVolumeChart('secretariatApplicationVolumeChart', window.__secretariatApplicationVolumePayload);
    }
}

document.addEventListener('livewire:init', () => {
    Livewire.on('hq-dashboard-charts-updated', ({ charts: payload }) => {
        window.__hqDashboardChartPayload = payload;
        requestAnimationFrame(() => renderHqDashboardCharts(payload));
    });

    Livewire.on('secretariat-application-volume-updated', ({ chart }) => {
        window.__secretariatApplicationVolumePayload = chart;
        requestAnimationFrame(() => renderApplicationVolumeChart('secretariatApplicationVolumeChart', chart));
    });

    Livewire.hook('morph.updated', () => {
        requestAnimationFrame(renderFromWindowPayload);
    });
});

document.addEventListener('DOMContentLoaded', renderFromWindowPayload);
document.addEventListener('livewire:navigated', renderFromWindowPayload);
