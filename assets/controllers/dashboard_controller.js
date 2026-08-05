import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

const DARK = {
    grid: 'rgba(255,255,255,0.06)',
    text: '#8b93a8',
    teal: '#00c9a7',
    purple: '#9775fa',
    blue: '#4dabf7',
    red: '#ff6b6b',
};

Chart.defaults.color = DARK.text;
Chart.defaults.borderColor = DARK.grid;

export default class extends Controller {
    static values = {
        statusData: Object,
        healthData: Object,
        sparkProjects: Array,
        sparkTasks: Array,
        sparkActivity: Array,
    };

    static targets = ['statusChart', 'healthChart', 'mainChart', 'sparkProjects', 'sparkTasks', 'sparkActivity'];

    connect() {
        if (this.hasMainChartTarget) this.renderMainChart();
        if (this.hasStatusChartTarget) this.renderStatusChart();
        if (this.hasSparkProjectsTarget) this.renderSpark(this.sparkProjectsTarget, this.sparkProjectsValue, DARK.teal);
        if (this.hasSparkTasksTarget) this.renderSpark(this.sparkTasksTarget, this.sparkTasksValue, DARK.blue);
        if (this.hasSparkActivityTarget) this.renderSpark(this.sparkActivityTarget, this.sparkActivityValue, DARK.purple);
    }

    renderMainChart() {
        const status = this.statusDataValue;
        const months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
        const active = status.active ?? 0;
        const completed = status.completed ?? 0;
        const progress = Array.from({ length: 12 }, (_, i) => Math.max(0, Math.round(active * (i + 1) / 12)));
        const done = Array.from({ length: 12 }, (_, i) => Math.max(0, Math.round(completed * (i + 1) / 12)));

        new Chart(this.mainChartTarget, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Projets actifs',
                        data: progress,
                        borderColor: DARK.teal,
                        backgroundColor: 'rgba(0,201,167,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: DARK.teal,
                    },
                    {
                        label: 'Projets terminés',
                        data: done,
                        borderColor: DARK.purple,
                        backgroundColor: 'rgba(151,117,250,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: DARK.purple,
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top', labels: { boxWidth: 12, usePointStyle: true } } },
                scales: {
                    x: { grid: { color: DARK.grid } },
                    y: { beginAtZero: true, grid: { color: DARK.grid }, ticks: { stepSize: 1 } },
                },
            },
        });
    }

    renderStatusChart() {
        const data = this.statusDataValue;
        const labels = { draft: 'Brouillon', active: 'Actif', on_hold: 'Pause', completed: 'Terminé', cancelled: 'Annulé' };
        const keys = Object.keys(data).filter((k) => data[k] > 0);
        new Chart(this.statusChartTarget, {
            type: 'doughnut',
            data: {
                labels: keys.map((k) => labels[k] ?? k),
                datasets: [{
                    data: keys.map((k) => data[k]),
                    backgroundColor: [DARK.text, DARK.blue, DARK.red, DARK.teal, '#ffa94d'],
                    borderWidth: 0,
                }],
            },
            options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } }, cutout: '65%' },
        });
    }

    renderSpark(canvas, data, color) {
        const values = Array.isArray(data) && data.length ? data : [3, 5, 4, 7, 6, 8, 5];
        const fills = { [DARK.teal]: 'rgba(0,201,167,0.12)', [DARK.blue]: 'rgba(77,171,247,0.12)', [DARK.purple]: 'rgba(151,117,250,0.12)' };
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: values.map((_, i) => i),
                datasets: [{
                    data: values,
                    borderColor: color,
                    backgroundColor: fills[color] ?? 'rgba(77,171,247,0.12)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    borderWidth: 2,
                }],
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
