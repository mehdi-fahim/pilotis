import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

export default class extends Controller {
    static values = {
        moveUrl: String,
    };

    static targets = ['column'];

    connect() {
        this.sortables = [];
        this.columnTargets.forEach((column) => {
            this.sortables.push(
                Sortable.create(column, {
                    group: {
                        name: 'kanban',
                        pull: true,
                        put: true,
                    },
                    animation: 180,
                    draggable: '.kanban-card',
                    filter: 'a, button, input, textarea, select',
                    preventOnFilter: true,
                    forceFallback: true,
                    fallbackOnBody: true,
                    fallbackTolerance: 5,
                    swapThreshold: 0.65,
                    emptyInsertThreshold: 24,
                    ghostClass: 'kanban-card-ghost',
                    dragClass: 'kanban-card-drag',
                    chosenClass: 'kanban-card-chosen',
                    onEnd: (event) => this.onMove(event),
                }),
            );
        });
    }

    disconnect() {
        this.sortables?.forEach((sortable) => sortable.destroy());
        this.sortables = [];
    }

    async onMove(event) {
        if (event.from === event.to && event.oldIndex === event.newIndex) {
            return;
        }

        const taskEl = event.item;
        const taskId = taskEl.dataset.kanbanTaskId;
        const status = event.to.dataset.kanbanStatus;
        const newOrder = event.newIndex;

        if (!taskId || !status) {
            return;
        }

        const headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta?.content) {
            headers['X-CSRF-TOKEN'] = csrfMeta.content;
        }

        taskEl.classList.add('kanban-card-saving');

        try {
            const response = await fetch(this.moveUrlValue, {
                method: 'POST',
                headers,
                credentials: 'same-origin',
                body: JSON.stringify({
                    taskId: parseInt(taskId, 10),
                    status,
                    order: newOrder,
                }),
            });

            if (!response.ok) {
                throw new Error('Move failed');
            }
        } catch {
            if (event.oldIndex >= event.from.children.length) {
                event.from.appendChild(taskEl);
            } else {
                event.from.insertBefore(taskEl, event.from.children[event.oldIndex] ?? null);
            }
            window.alert('Impossible de déplacer la tâche.');
        } finally {
            taskEl.classList.remove('kanban-card-saving');
        }
    }
}
