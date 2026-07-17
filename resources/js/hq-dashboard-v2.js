import Chart from 'chart.js/auto';
import { getDashboardTheme } from './hq-theme';

const lightPalette = ['#059669', '#0ea5e9', '#8b5cf6', '#f59e0b', '#ec4899', '#14b8a6'];
const darkPalette = ['#34d399', '#38bdf8', '#a78bfa', '#fbbf24', '#f472b6', '#2dd4bf'];

let charts = {};

const centerTextPlugin = {
    id: 'hqCenterText',
    afterDraw(chart, _args, options) {
        const meta = chart.getDatasetMeta(0);
        if (!meta?.data?.length || chart.config.type !== 'doughnut') {
            return;
        }

        const { ctx } = chart;
        const centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
        const centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;
        const isDark = getDashboardTheme() === 'dark';
        const total = chart.data.datasets[0].data.reduce((sum, value) => sum + Number(value || 0), 0);

        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = isDark ? '#f8fafc' : '#0f172a';
        ctx.font = '700 1.35rem "Segoe UI", system-ui, sans-serif';
        ctx.fillText(formatCompactNumber(total), centerX, centerY - 8);
        ctx.fillStyle = isDark ? '#94a3b8' : '#64748b';
        ctx.font = '600 0.72rem "Segoe UI", system-ui, sans-serif';
        ctx.fillText(options?.label || 'Total', centerX, centerY + 14);
        ctx.restore();
    },
};

Chart.register(centerTextPlugin);

function formatCompactNumber(value) {
    const number = Number(value) || 0;

    if (number >= 1_000_000_000) {
        return `${(number / 1_000_000_000).toFixed(1)}B`;
    }

    if (number >= 1_000_000) {
        return `${(number / 1_000_000).toFixed(1)}M`;
    }

    if (number >= 1_000) {
        return `${(number / 1_000).toFixed(1)}K`;
    }

    return number.toLocaleString();
}

function themeTokens() {
    const isDark = getDashboardTheme() === 'dark';

    return {
        isDark,
        palette: isDark ? darkPalette : lightPalette,
        text: isDark ? '#e2e8f0' : '#334155',
        muted: isDark ? '#94a3b8' : '#64748b',
        grid: isDark ? 'rgba(148, 163, 184, 0.12)' : 'rgba(148, 163, 184, 0.35)',
        tooltipBg: isDark ? '#1e293b' : '#ffffff',
        tooltipBorder: isDark ? '#334155' : '#e2e8f0',
    };
}

function gradientFill(ctx, chartArea, colorStops) {
    const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);

    colorStops.forEach(([offset, color]) => gradient.addColorStop(offset, color));

    return gradient;
}

function destroyChart(canvasId) {
    if (charts[canvasId]) {
        charts[canvasId].destroy();
        delete charts[canvasId];
    }
}

function destroyCharts() {
    Object.keys(charts).forEach((canvasId) => destroyChart(canvasId));
}

function sharedAnimation() {
    return {
        duration: 1400,
        easing: 'easeOutQuart',
    };
}

function doughnutChart(canvasId, labels, values, centerLabel = 'Total') {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels?.length) return;

    destroyChart(canvasId);

    const tokens = themeTokens();

    charts[canvasId] = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: tokens.palette,
                borderColor: tokens.isDark ? '#1e293b' : '#ffffff',
                borderWidth: 3,
                hoverOffset: 12,
                spacing: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            animation: sharedAnimation(),
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: tokens.text,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 16,
                        font: { size: 11, weight: '600' },
                    },
                },
                tooltip: {
                    backgroundColor: tokens.tooltipBg,
                    titleColor: tokens.text,
                    bodyColor: tokens.muted,
                    borderColor: tokens.tooltipBorder,
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 10,
                },
                hqCenterText: { label: centerLabel },
            },
        },
    });
}

function barChart(canvasId, labels, values, horizontal = false) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels?.length) return;

    destroyChart(canvasId);

    const tokens = themeTokens();

    charts[canvasId] = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: (context) => {
                    const { chart } = context;
                    const { ctx, chartArea } = chart;

                    if (!chartArea) {
                        return tokens.palette[context.dataIndex % tokens.palette.length];
                    }

                    const base = tokens.palette[context.dataIndex % tokens.palette.length];

                    if (horizontal) {
                        return gradientFill(ctx, chartArea, [
                            [0, `${base}33`],
                            [1, base],
                        ]);
                    }

                    return gradientFill(ctx, chartArea, [
                        [0, base],
                        [1, `${base}55`],
                    ]);
                },
                borderRadius: horizontal ? { topRight: 8, bottomRight: 8 } : { topLeft: 8, topRight: 8 },
                borderSkipped: false,
                maxBarThickness: horizontal ? 22 : 42,
            }],
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            animation: sharedAnimation(),
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: tokens.tooltipBg,
                    titleColor: tokens.text,
                    bodyColor: tokens.muted,
                    borderColor: tokens.tooltipBorder,
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 10,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: tokens.muted, font: { weight: '600' } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: tokens.grid },
                    ticks: { color: tokens.muted, font: { weight: '600' } },
                },
            },
        },
    });
}

export function renderHqDashboardCharts(payload) {
    if (!payload) return;

    destroyCharts();

    if (document.getElementById('hqPortfolioChart')) {
        doughnutChart('hqPortfolioChart', payload.portfolio.labels, payload.portfolio.values, 'Portfolio');
        doughnutChart('hqDemographicsChart', payload.demographics.labels, payload.demographics.values, 'Groups');
        barChart('hqRecoveryChart', payload.recovery.labels, payload.recovery.values);
        barChart('hqConstituencyChart', payload.constituencies.labels, payload.constituencies.values);
    }

    if (document.getElementById('hqSectorChart')) {
        barChart('hqSectorChart', payload.sectors.labels, payload.sectors.values, true);
    }

    if (document.getElementById('hqAnalyzeChart')) {
        const dimension = payload.activeDimension || 'demographics';
        const dataset = payload[dimension] || payload.demographics;
        doughnutChart('hqAnalyzeChart', dataset.labels, dataset.values, 'Analysis');
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
document.addEventListener('hq-dashboard-theme-changed', () => {
    requestAnimationFrame(renderFromWindowPayload);
});
