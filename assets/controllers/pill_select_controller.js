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

        const filter = () => {
            const selectedDept = this.getSelectedDepartmentId(form);
            this.element.querySelectorAll('.pill-option-actor').forEach((label) => {
                const deptId = label.dataset.departmentId ?? '';
                const show = !selectedDept || !deptId || String(deptId) === String(selectedDept);
                label.classList.toggle('is-hidden', !show);

                if (!show) {
                    const input = label.querySelector('.pill-input');
                    if (input?.checked) {
                        input.checked = false;
                    }
                }
            });
            this.syncSelectedStyles();
        };

        form.querySelectorAll('[name*="[department]"]').forEach((input) => {
            input.addEventListener('change', filter);
        });

        filter();
    }

    getSelectedDepartmentId(form) {
        const checked = form.querySelector('[name*="[department]"]:checked');
        if (checked && checked.value !== '') {
            return checked.value;
        }

        const select = form.querySelector('select[name*="[department]"]');
        return select?.value ?? '';
    }
}
