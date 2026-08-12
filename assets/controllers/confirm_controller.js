import { Controller } from '@hotwired/stimulus';

/**
 * Intercepte les formulaires [data-confirm] et affiche une modale Pilotis.
 */
export default class extends Controller {
    static targets = ['dialog', 'title', 'message', 'confirmButton', 'icon'];

    connect() {
        this.pendingForm = null;
        this.onSubmit = this.onSubmit.bind(this);
        this.onKeydown = this.onKeydown.bind(this);
        document.addEventListener('submit', this.onSubmit, true);
    }

    disconnect() {
        document.removeEventListener('submit', this.onSubmit, true);
        document.removeEventListener('keydown', this.onKeydown);
    }

    onSubmit(event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) {
            return;
        }

        if (form.dataset.confirmAccepted === '1') {
            delete form.dataset.confirmAccepted;
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        this.pendingForm = form;
        this.titleTarget.textContent = form.dataset.confirmTitle || 'Confirmation';
        this.messageTarget.textContent = form.dataset.confirm || 'Confirmer cette action ?';

        const confirmLabel = form.dataset.confirmLabel || 'Confirmer';
        this.confirmButtonTarget.innerHTML = `<i class="bi bi-check-lg"></i> ${confirmLabel}`;

        const variant = form.dataset.confirmVariant || 'danger';
        this.confirmButtonTarget.className = `btn btn-pilotis pilotis-confirm-btn ${variant === 'success' ? 'btn-success' : 'btn-danger'}`;
        this.iconTarget.className = `pilotis-confirm-icon ${variant === 'success' ? 'success' : 'danger'}`;
        this.iconTarget.innerHTML = variant === 'success'
            ? '<i class="bi bi-check2-circle"></i>'
            : '<i class="bi bi-exclamation-triangle"></i>';

        this.open();
    }

    open() {
        this.element.classList.add('is-open');
        this.element.setAttribute('aria-hidden', 'false');
        document.body.classList.add('pilotis-confirm-open');
        document.addEventListener('keydown', this.onKeydown);
        this.confirmButtonTarget.focus();
    }

    close() {
        this.element.classList.remove('is-open');
        this.element.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('pilotis-confirm-open');
        document.removeEventListener('keydown', this.onKeydown);
        this.pendingForm = null;
    }

    cancel() {
        this.close();
    }

    confirm() {
        const form = this.pendingForm;
        if (!form) {
            this.close();
            return;
        }

        form.dataset.confirmAccepted = '1';
        this.close();
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    onBackdrop(event) {
        if (event.target === this.element) {
            this.close();
        }
    }

    onKeydown(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    }
}
