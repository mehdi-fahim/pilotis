import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['option'];
    static values = { variant: String };

    connect() {
        this.syncSelectedStyles();
        this.element.querySelectorAll('.pill-input').forEach((input) => {
            input.addEventListener('change', () => this.syncSelectedStyles());
        });

        if (this.variantValue === 'actor') {
            this.bindDepartmentFilter();
        }
    }

    syncSelectedStyles() {
        this.element.querySelectorAll('.pill-option').forEach((label) => {
            const input = label.querySelector('.pill-input');
            label.classList.toggle('is-selected', input?.checked ?? false);
        });
    }

    bindDepartmentFilter() {
        const form = this.element.closest('form');
        if (!form) {
            return;
        }

        const departmentField = form.querySelector('[name*="[department]"]');
        if (!departmentField) {
            return;
        }

        const filter = () => {
            const selectedDept = this.getSelectedDepartmentId(form);
            this.element.querySelectorAll('.pill-option-actor').forEach((label) => {
                const deptId = label.dataset.departmentId ?? '';
                const show = !selectedDept || !deptId || deptId === selectedDept;
                label.classList.toggle('is-hidden', !show);
            });
        };

        form.querySelectorAll('[name*="[department]"]').forEach((input) => {
            input.addEventListener('change', filter);
        });

        filter();
    }

    getSelectedDepartmentId(form) {
        const checked = form.querySelector('[name*="[department]"]:checked');
        return checked?.value ?? '';
    }
}
