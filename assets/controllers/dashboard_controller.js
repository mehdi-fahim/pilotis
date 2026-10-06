import { Controller } from '@hotwired/stimulus';
import { Chart, registerables } from 'chart.js';
import { animate, stagger } from 'motion';

Chart.register(...registerables);

const HEALTH_COLORS = {
    green: '#22c55e',
    orange: '#f59e0b',
    red: '#ef4444',
};

export default class extends Controller {
    static values = {
        healthData: Object,
        sparkProjects: Array,
        sparkTasks: Array,
    };

    static targets = ['healthChart', 'activityChart'];

    connect() {
        this.charts = [];
        this.playEntrance();
        this.countUp();
        requestAnimationFrame(() => this.renderCharts());
    }

    disconnect() {
        this.charts.forEach((chart) => chart.destroy());
        this.charts = [];
    }

    playEntrance() {
        const cards = this.element.querySelectorAll('.dash-card');
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (reduce || cards.length === 0) {
            cards.forEach((card) => {
                card.style.opacity = '1';
            });

            return;
        }

        try {
            animate([...cards], { opacity: [0, 1] }, {
                duration: 0.55,
                delay: stagger(0.06),
                ease: [0.22, 1, 0.36, 1],
            });
        } catch (error) {
            cards.forEach((card) => {
                card.style.opacity = '1';
            });
        }
    }

    countUp() {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.element.querySelectorAll('[data-count]').forEach((node) => {
            const target = Number(node.dataset.count);
            if (!Number.isFinite(target)) {
                return;
            }
            if (reduce) {
                node.textContent = String(target);
                return;
            }

            node.textContent = '0';
            try {
                const animation = animate(0, target, {
                    duration: 0.9,
                    ease: [0.22, 1, 0.36, 1],
                    onUpdate: (latest) => {
                        node.textContent = String(Math.round(latest));
                    },
                });
                animation?.finished?.then(() => {
                    node.textContent = String(target);
                });
            } catch (error) {
                node.textContent = String(target);
            }
        });
    }

    renderCharts() {
        if (this.hasHealthChartTarget) {
            this.charts.push(this.renderHealthRing(this.healthChartTarget, this.healthDataValue));
        }
        if (this.hasActivityChartTarget) {
            this.charts.push(this.renderActivity(this.activityChartTarget, this.sparkProjectsValue, this.sparkTasksValue));
        }
    }

    renderHealthRing(canvas, data) {
        const chartData = { ...data };
        let keys = Object.keys(HEALTH_COLORS).filter((key) => (chartData[key] ?? 0) > 0);
        if (keys.length === 0) {
            keys = ['empty'];
            chartData.empty = 1;
        }

        const labels = { green: 'Sain', orange: 'Attention', red: 'Critique', empty: 'Aucun projet' };
        const colors = keys.map((key) => HEALTH_COLORS[key] ?? '#e2e8f0');

        return new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: keys.map((key) => labels[key] ?? key),
                datasets: [{
                    data: keys.map((key) => chartData[key]),
                    backgroundColor: colors,
                    borderWidth: 0,
                    spacing: keys.length > 1 ? 4 : 0,
                    borderRadius: 10,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '78%',
                plugins: { legend: { display: false }, tooltip: { enabled: keys[0] !== 'empty' } },
            },
        });
    }

    renderActivity(canvas, projects, tasks) {
        const styles = getComputedStyle(document.documentElement);
        const muted = styles.getPropertyValue('--text-muted').trim() || '#94a3b8';
        const days = ['J-6', 'J-5', 'J-4', 'J-3', 'J-2', 'J-1', 'Auj.'];
        const seriesA = Array.isArray(projects) && projects.length ? projects : [0, 0, 0, 0, 0, 0, 0];
        const seriesB = Array.isArray(tasks) && tasks.length ? tasks : [0, 0, 0, 0, 0, 0, 0];

        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels: days,
                datasets: [
                    { label: 'Projets', data: seriesA, backgroundColor: '#4f8ff7', borderRadius: 8, borderSkipped: false, maxBarThickness: 14 },
                    { label: 'Tâches', data: seriesB, backgroundColor: '#22c55e', borderRadius: 8, borderSkipped: false, maxBarThickness: 14 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true, color: muted, padding: 16 } },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: muted } },
                    y: { beginAtZero: true, ticks: { stepSize: 1, color: muted, precision: 0 }, grid: { color: 'rgba(148, 163, 184, 0.18)' }, border: { display: false } },
                },
            },
        });
    }
}
