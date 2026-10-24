import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

// Shared presentation only: datasets, thresholds and category calculations are unchanged.
Chart.defaults.font.family = '"Plus Jakarta Sans", system-ui, sans-serif';
Chart.defaults.font.size = 12;
Chart.defaults.color = '#666666';
Chart.register({
    id: 'flatMonitoringTheme',
    beforeUpdate(chart) {
        const options = chart.config.options;
        options.animation = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 150 };
        options.plugins ??= {};
        options.plugins.legend ??= {};
        options.plugins.legend.labels = { ...options.plugins.legend.labels, color: '#666666', font: { size: 12 }, boxWidth: 10, boxHeight: 10 };
        options.plugins.tooltip = { ...options.plugins.tooltip, backgroundColor: '#ffffff', titleColor: '#171717', bodyColor: '#666666', borderColor: '#e5e7eb', borderWidth: 1, cornerRadius: 4, padding: 10 };
        Object.values(options.scales || {}).forEach(scale => {
            scale.grid = { ...scale.grid, color: '#e5e7eb' };
            scale.ticks = { ...scale.ticks, color: '#666666', font: { size: 12 }, maxRotation: 30, maxTicksLimit: 5 };
        });
        chart.data.datasets.forEach((dataset, index) => {
            if (['doughnut', 'pie'].includes(chart.config.type)) {
                dataset.backgroundColor = ['#176b50', '#76998a', '#aebfb7', '#d8e0dc', '#737b76', '#ecefed'];
                dataset.borderColor = '#ffffff';
                dataset.hoverOffset = 2;
            } else {
                const color = index === 0 ? '#176b50' : index === 1 ? '#737373' : '#a3a3a3';
                dataset.borderColor = color;
                dataset.backgroundColor = chart.config.type === 'bar' ? '#176b50' : 'transparent';
                dataset.pointBackgroundColor = color;
                dataset.fill = false;
                if (!dataset.borderDash) dataset.pointRadius = 2;
            }
        });
    },
});

// Native POST forms show feedback; forms with Alpine handlers keep their own loading state.
document.addEventListener('submit', event => {
    const form = event.target, button = event.submitter;
    if (event.defaultPrevented || form.method.toLowerCase() !== 'post' || form.hasAttribute('x-data') || !button || button.name || !form.checkValidity()) return;
    button.dataset.submitHtml = button.innerHTML;
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.textContent = 'Memproses...';
});
window.addEventListener('pageshow', () => document.querySelectorAll('[data-submit-html]').forEach(button => {
    button.innerHTML = button.dataset.submitHtml;
    button.disabled = false;
    button.removeAttribute('aria-busy');
    delete button.dataset.submitHtml;
}));

// Tables retain their columns and scroll inside the panel, including by keyboard.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('main .overflow-x-auto').forEach(panel => {
        if (!panel.querySelector('table')) return;
        panel.setAttribute('role', 'region');
        panel.setAttribute('aria-label', 'Tabel data; gulir horizontal untuk melihat seluruh kolom');
        const update = () => panel.tabIndex = panel.scrollWidth > panel.clientWidth ? 0 : -1;
        new ResizeObserver(update).observe(panel);
        update();
    });
    document.querySelectorAll('main table thead th:not([scope])').forEach(th => th.setAttribute('scope', 'col'));
    document.querySelectorAll('main table').forEach(table => {
        const headers = [...(table.tHead?.rows[table.tHead.rows.length - 1]?.cells || [])];
        headers.forEach((header, index) => {
            if (!/^(suhu|lembap|tanah|ph|ec|cahaya|tandon|kelembapan|tinggi|kerapatan|skor|rata-rata|minimum|maksimum)\b/i.test(header.textContent.trim())) return;
            header.classList.add('ui-number-cell');
            [...table.tBodies].flatMap(body => [...body.rows]).forEach(row => {
                if (row.cells.length === headers.length) row.cells[index]?.classList.add('ui-number-cell');
            });
        });
    });
});

// Keep keyboard focus inside the mobile drawer / confirmation dialog, then return it.
Alpine.directive('focus-scope', (el, { expression }, { evaluateLater, effect, cleanup }) => {
    const evaluate = evaluateLater(expression);
    let active = false, previous, overflow;
    const focusable = () => [...el.querySelectorAll('a[href], button:not(:disabled), input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex="0"]')]
        .filter(node => node.getClientRects().length && !node.closest('[inert]'));
    const trap = event => {
        if (!active || event.key !== 'Tab') return;
        const items = focusable(), first = items[0], last = items.at(-1);
        if (!first) { event.preventDefault(); return; }
        if (event.shiftKey && (document.activeElement === first || !el.contains(document.activeElement))) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && (document.activeElement === last || !el.contains(document.activeElement))) { event.preventDefault(); first.focus(); }
    };
    document.addEventListener('keydown', trap);
    effect(() => evaluate(value => {
        if (!!value === active) return;
        active = !!value;
        if (active) {
            previous = document.activeElement;
            overflow = document.documentElement.style.overflow;
            document.documentElement.style.overflow = 'hidden';
            // x-show may still be applying display/transition when this effect runs.
            const focusWhenVisible = (frames = 12) => {
                if (!active) return;
                const target = focusable()[0];
                if (target) target.focus();
                else if (frames > 0) requestAnimationFrame(() => focusWhenVisible(frames - 1));
            };
            Alpine.nextTick(() => requestAnimationFrame(() => focusWhenVisible()));
        } else {
            document.documentElement.style.overflow = overflow || '';
            Alpine.nextTick(() => previous?.isConnected && previous.focus());
        }
    }));
    cleanup(() => { document.removeEventListener('keydown', trap); if (active) document.documentElement.style.overflow = overflow || ''; });
});

Alpine.start();
