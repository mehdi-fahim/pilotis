import { Controller } from '@hotwired/stimulus';

/**
 * Charge les intervenants d'un service en pastilles,
 * tout en conservant une sélection multi-services.
 */
export default class extends Controller {
    static targets = ['department', 'grid', 'placeholder', 'empty', 'picked', 'inputs', 'initial'];
    static values = {
        url: { type: String, default: '/incidents/actors.json' },
        fieldName: String,
    };

    connect() {
        this.picked = new Map();
        this.loadInitialPicked();
        this.renderPicked();
        this.filter();
    }

    loadInitialPicked() {
        if (!this.hasInitialTarget || !this.initialTarget.value) {
            return;
        }

        try {
            const items = JSON.parse(this.initialTarget.value);
            if (!Array.isArray(items)) {
                return;
            }
            items.forEach((item) => {
                if (item && item.id != null && item.name) {
                    this.picked.set(String(item.id), {
                        id: String(item.id),
                        name: String(item.name),
                    });
                }
            });
        } catch (e) {
            // ignore invalid bootstrap payload
        }
    }

    async filter() {
        const department = this.departmentSelect();
        const grid = this.hasGridTarget ? this.gridTarget : null;
        if (!department || !grid) {
            return;
        }

        const departmentId = department.value || '';

        if (!departmentId) {
            grid.innerHTML = '';
            grid.hidden = true;
            if (this.hasPlaceholderTarget) this.placeholderTarget.hidden = this.picked.size > 0;
            if (this.hasEmptyTarget) this.emptyTarget.hidden = true;
            return;
        }

        if (this.hasPlaceholderTarget) this.placeholderTarget.hidden = true;
        if (this.hasEmptyTarget) this.emptyTarget.hidden = true;

        try {
            const response = await fetch(`${this.urlValue}?department=${encodeURIComponent(departmentId)}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            const actors = await response.json();
            this.renderAvailable(grid, Array.isArray(actors) ? actors : []);
        } catch (e) {
            grid.innerHTML = '';
            grid.hidden = true;
            if (this.hasEmptyTarget) this.emptyTarget.hidden = false;
        }
    }

    departmentSelect() {
        if (this.hasDepartmentTarget) {
            return this.departmentTarget;
        }

        return this.element.querySelector('[data-actor-filter-target="department"]');
    }

    renderAvailable(grid, actors) {
        grid.innerHTML = '';

        if (actors.length === 0) {
            grid.hidden = true;
            if (this.hasEmptyTarget) this.emptyTarget.hidden = false;
            return;
        }

        if (this.hasEmptyTarget) this.emptyTarget.hidden = true;
        grid.hidden = false;

        actors.forEach((actor) => {
            const id = String(actor.id);
            const inputId = `actor-available-${id}`;
            const isChecked = this.picked.has(id);

            const label = document.createElement('label');
            label.className = 'pill-option pill-option-neutral' + (isChecked ? ' is-selected' : '');
            label.setAttribute('for', inputId);

            const input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'pill-input';
            input.id = inputId;
            input.value = id;
            input.checked = isChecked;
            input.addEventListener('change', () => {
                if (input.checked) {
                    this.picked.set(id, { id, name: String(actor.name) });
                } else {
                    this.picked.delete(id);
                }
                label.classList.toggle('is-selected', input.checked);
                this.renderPicked();
            });

            const content = document.createElement('span');
            content.className = 'pill-content';
            const text = document.createElement('span');
            text.className = 'pill-label';
            text.textContent = actor.name;
            content.appendChild(text);

            label.appendChild(input);
            label.appendChild(content);
            grid.appendChild(label);
        });
    }

    renderPicked() {
        if (this.hasPickedTarget) {
            this.pickedTarget.innerHTML = '';
            this.pickedTarget.hidden = this.picked.size === 0;

            if (this.picked.size > 0) {
                const title = document.createElement('div');
                title.className = 'actor-pills-picked-label';
                title.textContent = 'Sélectionnés';
                this.pickedTarget.appendChild(title);

                const row = document.createElement('div');
                row.className = 'pill-select-grid actor-pills-live';

                Array.from(this.picked.values()).forEach((actor) => {
                    const chip = document.createElement('button');
                    chip.type = 'button';
                    chip.className = 'pill-option pill-option-neutral is-selected actor-pill-picked';
                    chip.title = 'Retirer';
                    chip.innerHTML = `<span class="pill-content"><span class="pill-label">${this.escapeHtml(actor.name)}</span><span class="pill-remove" aria-hidden="true">×</span></span>`;
                    chip.addEventListener('click', () => {
                        this.picked.delete(String(actor.id));
                        this.renderPicked();
                        this.syncAvailableChecks();
                    });
                    row.appendChild(chip);
                });

                this.pickedTarget.appendChild(row);
            }
        }

        this.syncHiddenInputs();

        if (this.hasPlaceholderTarget) {
            const department = this.departmentSelect();
            const hasDepartment = department && department.value;
            this.placeholderTarget.hidden = this.picked.size > 0 || !!hasDepartment;
        }
    }

    syncAvailableChecks() {
        if (!this.hasGridTarget) {
            return;
        }

        this.gridTarget.querySelectorAll('input[type="checkbox"]').forEach((input) => {
            const checked = this.picked.has(String(input.value));
            input.checked = checked;
            input.closest('.pill-option')?.classList.toggle('is-selected', checked);
        });
    }

    syncHiddenInputs() {
        if (!this.hasInputsTarget) {
            return;
        }

        const fieldName = this.fieldNameValue || 'incident_form[assignedActors][]';
        this.inputsTarget.innerHTML = '';

        Array.from(this.picked.keys()).forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = fieldName;
            input.value = id;
            this.inputsTarget.appendChild(input);
        });
    }

    escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }
}
