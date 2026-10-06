import { Controller } from '@hotwired/stimulus';

/**
 * Filtre le select d'acteurs selon le service choisi.
 */
export default class extends Controller {
    static targets = ['department', 'actor'];

    connect() {
        if (!this.hasActorTarget) {
            return;
        }

        this.allOptions = Array.from(this.actorTarget.options).map((option) => ({
            value: option.value,
            text: option.textContent ?? '',
            departmentId: option.dataset.departmentId ?? '',
        }));

        this.filter();
    }

    filter() {
        if (!this.hasActorTarget || !this.allOptions) {
            return;
        }

        const departmentId = this.hasDepartmentTarget ? (this.departmentTarget.value || '') : '';
        const previousValue = this.actorTarget.value;

        this.actorTarget.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = departmentId ? 'Non assigné' : 'Sélectionnez d’abord un service';
        this.actorTarget.appendChild(placeholder);

        if (!departmentId) {
            this.actorTarget.value = '';
            return;
        }

        let keepSelection = false;
        this.allOptions.forEach((option) => {
            if (option.value === '') {
                return;
            }
            if (String(option.departmentId) !== String(departmentId)) {
                return;
            }

            const el = document.createElement('option');
            el.value = option.value;
            el.textContent = option.text;
            el.dataset.departmentId = option.departmentId;
            if (option.value === previousValue) {
                el.selected = true;
                keepSelection = true;
            }
            this.actorTarget.appendChild(el);
        });

        if (!keepSelection) {
            this.actorTarget.value = '';
        }
    }
}
