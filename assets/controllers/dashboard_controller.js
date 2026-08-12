import { Controller } from '@hotwired/stimulus';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const LIGHT = {
    grid: 'rgba(15, 23, 42, 0.08)',
    text: '#94a3b8',
    teal: '#10b981',
    purple: '#8b5cf6',
    blue: '#4f8ff7',
    red: '#ef4444',
    orange: '#f59e0b',
};

Chart.defaults.color = LIGHT.text;
Chart.defaults.borderColor = LIGHT.grid;

export default class extends Controller {
    static values = {
        projectStatusData: Object,
        incidentStatusData: Object,
        incidentPriorityData: Object,
        sparkProjects: Array,
        sparkTasks: Array,
        sparkIncidentsOpened: Array,
        sparkIncidentsResolved: Array,
    };

    static targets = [
        'projectStatusChart',
        'projectActivityChart',
        'incidentStatusChart',
        'incidentPriorityChart',
        'incidentActivityChart',
        'sparkProjects',
        'sparkTasks',
        'sparkIncidentsOpened',
        'sparkIncidentsResolved',
        'defaultViewButton',
    ];

    connect() {
        this.charts = [];
        this.incidentChartsRendered = false;
        this.storageKey = 'pilotis-default-view';
        this.activeView = 'projects';

        requestAnimationFrame(() => {
            this.applyDefaultView();
            this.renderProjectCharts();
            this.bindTabEvents();
            this.updateDefaultViewButton();
        });
    }

    disconnect() {
        this.charts.forEach((chart) => chart.destroy());
        this.charts = [];
    }

    bindTabEvents() {
        document.querySelectorAll('[data-dashboard-tab]').forEach((tab) => {
            tab.addEventListener('shown.bs.tab', (event) => {
                const view = event.target.getAttribute('data-dashboard-tab') || 'projects';
                this.activeView = view;
                if (view === 'incidents') {
                    this.renderIncidentChartsOnce();
                }
                this.charts.forEach((chart) => chart.resize());
                this.updateDefaultViewButton();
            });
        });
    }

    applyDefaultView() {
        let preferred = 'projects';
        try {
            const stored = localStorage.getItem(this.storageKey);
            if (stored === 'projects' || stored === 'incidents') {
                preferred = stored;
            }
        } catch (e) {
            preferred = 'projects';
        }

        this.activeView = preferred;
        if (preferred === 'incidents') {
            const button = document.getElementById('tab-btn-incidents');
            if (button && window.bootstrap?.Tab) {
                window.bootstrap.Tab.getOrCreateInstance(button).show();
                this.renderIncidentChartsOnce();
            }
        }
    }

    setDefaultView() {
        try {
            localStorage.setItem(this.storageKey, this.activeView);
        } catch (e) {
            // ignore storage errors
        }
        this.updateDefaultViewButton(true);
    }

    updateDefaultViewButton(justSaved = false) {
        if (!this.hasDefaultViewButtonTarget) {
            return;
        }

        let preferred = 'projects';
        try {
            const stored = localStorage.getItem(this.storageKey);
            if (stored === 'projects' || stored === 'incidents') {
                preferred = stored;
            }
        } catch (e) {
            preferred = 'projects';
        }

        const isCurrentDefault = preferred === this.activeView;
        const label = this.activeView === 'incidents' ? 'Incidents' : 'Projets';

        if (justSaved || isCurrentDefault) {
            this.defaultViewButtonTarget.innerHTML = `<i class="bi bi-pin-fill me-1"></i> Vue par défaut : ${label}`;
            this.defaultViewButtonTarget.classList.remove('btn-outline-secondary');
            this.defaultViewButtonTarget.classList.add('btn-outline-primary');
        } else {
            this.defaultViewButtonTarget.innerHTML = '<i class="bi bi-pin-angle me-1"></i> Définir comme vue par défaut';
            this.defaultViewButtonTarget.classList.add('btn-outline-secondary');
            this.defaultViewButtonTarget.classList.remove('btn-outline-primary');
        }
    }
    trackChart(chart) {
        this.charts.push(chart);

        return chart;
    }

    renderProjectCharts() {
        if (this.hasProjectActivityChartTarget) {
            this.trackChart(this.renderDualBarChart(
                this.projectActivityChartTarget,
                this.sparkProjectsValue,
                this.sparkTasksValue,
                'Projets',
                'Tâches',
                LIGHT.blue,
                LIGHT.purple,
            ));
        }
        if (this.hasProjectStatusChartTarget) {
            this.trackChart(this.renderDoughnut(this.projectStatusChartTarget, this.projectStatusDataValue, {
                draft: 'Brouillon', active: 'Actif', on_hold: 'Pause', completed: 'Terminé', cancelled: 'Annulé',
            }, [LIGHT.text, LIGHT.blue, LIGHT.orange, LIGHT.teal, LIGHT.red]));
        }
        if (this.hasSparkProjectsTarget) {
            this.trackChart(this.renderSpark(this.sparkProjectsTarget, this.sparkProjectsValue, LIGHT.blue));
        }
        if (this.hasSparkTasksTarget) {
            this.trackChart(this.renderSpark(this.sparkTasksTarget, this.sparkTasksValue, LIGHT.purple));
        }
    }

    renderIncidentChartsOnce() {
        if (this.incidentChartsRendered) {
            this.charts.forEach((chart) => chart.resize());

            return;
        }
        this.incidentChartsRendered = true;

        if (this.hasIncidentActivityChartTarget) {
            this.trackChart(this.renderDualBarChart(
                this.incidentActivityChartTarget,
                this.sparkIncidentsOpenedValue,
                this.sparkIncidentsResolvedValue,
                'Ouverts',
                'Résolus',
                LIGHT.red,
                LIGHT.teal,
            ));
        }
        if (this.hasIncidentStatusChartTarget) {
            this.trackChart(this.renderDoughnut(this.incidentStatusChartTarget, this.incidentStatusDataValue, {
                open: 'Ouvert', in_progress: 'En cours', waiting: 'En attente', resolved: 'Résolu', closed: 'Clôturé',
            }, [LIGHT.red, LIGHT.blue, LIGHT.orange, LIGHT.teal, LIGHT.text]));
        }
        if (this.hasIncidentPriorityChartTarget) {
            this.trackChart(this.renderBarChart(this.incidentPriorityChartTarget, this.incidentPriorityDataValue, {
                low: 'Basse', medium: 'Moyenne', high: 'Haute', critical: 'Critique',
            }, [LIGHT.text, LIGHT.blue, LIGHT.orange, LIGHT.red]));
        }
        if (this.hasSparkIncidentsOpenedTarget) {
            this.trackChart(this.renderSpark(this.sparkIncidentsOpenedTarget, this.sparkIncidentsOpenedValue, LIGHT.red));
        }
        if (this.hasSparkIncidentsResolvedTarget) {
            this.trackChart(this.renderSpark(this.sparkIncidentsResolvedTarget, this.sparkIncidentsResolvedValue, LIGHT.teal));
        }
    }

    renderDualBarChart(canvas, dataA, dataB, labelA, labelB, colorA, colorB) {
        const days = ['J-6', 'J-5', 'J-4', 'J-3', 'J-2', 'J-1', 'Auj.'];
        const seriesA = Array.isArray(dataA) && dataA.length ? dataA : [0, 0, 0, 0, 0, 0, 0];
        const seriesB = Array.isArray(dataB) && dataB.length ? dataB : [0, 0, 0, 0, 0, 0, 0];

        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels: days,
                datasets: [
                    { label: labelA, data: seriesA, backgroundColor: colorA, borderRadius: 6, borderSkipped: false },
                    { label: labelB, data: seriesB, backgroundColor: colorB, borderRadius: 6, borderSkipped: false },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { boxWidth: 12, usePointStyle: true } } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: LIGHT.grid }, ticks: { stepSize: 1 } },
                },
            },
        });
    }

    renderDualLineChart(canvas, dataA, dataB, labelA, labelB, colorA, colorB) {
        const days = ['J-6', 'J-5', 'J-4', 'J-3', 'J-2', 'J-1', 'Auj.'];
        const seriesA = Array.isArray(dataA) && dataA.length ? dataA : [0, 0, 0, 0, 0, 0, 0];
        const seriesB = Array.isArray(dataB) && dataB.length ? dataB : [0, 0, 0, 0, 0, 0, 0];

        return new Chart(canvas, {
            type: 'line',
            data: {
                labels: days,
                datasets: [
                    { label: labelA, data: seriesA, borderColor: colorA, backgroundColor: colorA + '22', fill: true, tension: 0.4, pointRadius: 3 },
                    { label: labelB, data: seriesB, borderColor: colorB, backgroundColor: colorB + '22', fill: true, tension: 0.4, pointRadius: 3 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { boxWidth: 12, usePointStyle: true } } },
                scales: { x: { grid: { color: LIGHT.grid } }, y: { beginAtZero: true, grid: { color: LIGHT.grid }, ticks: { stepSize: 1 } } },
            },
        });
    }

    renderDoughnut(canvas, data, labels, colors) {
        const chartData = { ...data };
        let keys = Object.keys(chartData).filter((k) => chartData[k] > 0);
        if (keys.length === 0) {
            keys = ['empty'];
            chartData.empty = 1;
        }

        return new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: keys.map((k) => labels[k] ?? k),
                datasets: [{ data: keys.map((k) => chartData[k]), backgroundColor: colors, borderWidth: 0 }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                cutout: '65%',
            },
        });
    }

    renderBarChart(canvas, data, labels, colors) {
        const keys = Object.keys(data);

        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels: keys.map((k) => labels[k] ?? k),
                datasets: [{ data: keys.map((k) => data[k]), backgroundColor: colors, borderRadius: 6, borderSkipped: false }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: LIGHT.grid }, ticks: { stepSize: 1 } } },
            },
        });
    }

    renderSpark(canvas, data, color) {
        const values = Array.isArray(data) && data.length ? data : [0, 0, 0, 0, 0, 0, 0];

        return new Chart(canvas, {
            type: 'line',
            data: {
                labels: values.map((_, i) => i),
                datasets: [{ data: values, borderColor: color, backgroundColor: color + '22', fill: true, tension: 0.4, pointRadius: 0, borderWidth: 2 }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { display: false }, y: { display: false } },
            },
        });
    }
}
