/**
 * Chart.js integration for Finestro.
 *
 * Exposes a small, brand-aware wrapper so Blade views can spin up charts
 * declaratively without repeating theme boilerplate. Charts automatically
 * pick up the brand palette and adapt to light/dark mode.
 *
 * Usage from a Blade view:
 *
 *   <canvas data-chart='{"type":"line","data":{...},"options":{...}}'></canvas>
 *
 * or programmatically:  window.Finestro.chart(canvasEl, config)
 */
import {
    Chart,
    LineController, LineElement, PointElement,
    BarController, BarElement,
    DoughnutController, ArcElement,
    CategoryScale, LinearScale,
    Filler, Tooltip, Legend,
} from 'chart.js';

Chart.register(
    LineController, LineElement, PointElement,
    BarController, BarElement,
    DoughnutController, ArcElement,
    CategoryScale, LinearScale,
    Filler, Tooltip, Legend,
);

// Brand palette (mirrors tailwind.config.js tokens).
export const palette = {
    primary: '#039855',
    primarySoft: 'rgba(3, 152, 85, 0.12)',
    secondary: '#05c7a6',
    accent: '#f79009',
    red: '#dc2626',
    series: ['#039855', '#f79009', '#05c7a6', '#6ce9a6', '#fdb022', '#0ea5e9', '#8b5cf6', '#ec4899'],
};

function isDark() {
    return document.documentElement.classList.contains('dark');
}

// Global defaults tuned for the app's look and feel.
function applyDefaults() {
    const dark = isDark();
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = dark ? '#918d7d' : '#6f6c5e';
    Chart.defaults.borderColor = dark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.legend.labels.padding = 16;
    Chart.defaults.plugins.tooltip.backgroundColor = dark ? '#232821' : '#161c18';
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;
    Chart.defaults.plugins.tooltip.titleFont = { weight: '600' };
    Chart.defaults.maintainAspectRatio = false;
}

const registry = new Map();

/**
 * Create (or recreate) a chart on a canvas element.
 * @param {HTMLCanvasElement} canvas
 * @param {object} config Chart.js configuration
 */
export function chart(canvas, config) {
    if (!canvas) return null;
    applyDefaults();

    // Destroy any previous instance bound to this canvas (dark-mode redraws).
    if (registry.has(canvas)) {
        registry.get(canvas).destroy();
    }

    const instance = new Chart(canvas.getContext('2d'), config);
    registry.set(canvas, instance);
    return instance;
}

/** Format a number as Brazilian currency for tick/tooltip callbacks. */
export function brl(value) {
    return 'R$ ' + Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/**
 * Auto-initialise charts declared via `data-chart` attribute.
 * Keeps a reference so they can be rebuilt when the theme toggles.
 */
const declarative = [];
function initDeclarativeCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        let config;
        try {
            config = JSON.parse(canvas.dataset.chart);
        } catch (e) {
            console.warn('Finestro: invalid data-chart JSON', e);
            return;
        }
        chart(canvas, config);
        declarative.push({ canvas, config });
    });
}

// Rebuild declarative charts when dark mode changes so colours stay correct.
function watchTheme() {
    const observer = new MutationObserver((mutations) => {
        if (mutations.some((m) => m.attributeName === 'class')) {
            declarative.forEach(({ canvas, config }) => chart(canvas, config));
        }
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
}

export function bootCharts() {
    document.addEventListener('DOMContentLoaded', () => {
        initDeclarativeCharts();
        watchTheme();
    });
}
