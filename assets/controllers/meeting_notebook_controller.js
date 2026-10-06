import { Controller } from '@hotwired/stimulus';

/**
 * Carnet de réunions : pages à gauche, compte rendu mis en forme, enregistrement automatique.
 */
export default class extends Controller {
    static values = {
        saveUrl: String,
        csrf: String,
    };

    static targets = ['editor', 'title', 'date', 'status', 'pageTitle', 'pageDate', 'activePage', 'pageList', 'forePalette', 'backPalette'];

    connect() {
        this.timer = null;
        this.savedRange = null;
        this.dirty = false;
        this.savePromise = null;
        this.lastSaved = this.snapshot();
        this.refreshPlaceholder();
        document.execCommand('styleWithCSS', false, true);

        this.boundDocumentClick = (event) => this.onDocumentClick(event);
        this.boundDocumentSubmit = (event) => this.onDocumentSubmit(event);
        this.boundBeforeUnload = () => this.onBeforeUnload();
        document.addEventListener('click', this.boundDocumentClick, true);
        document.addEventListener('submit', this.boundDocumentSubmit, true);
        window.addEventListener('beforeunload', this.boundBeforeUnload);

        this.element.querySelector('.notebook-page.active')?.scrollIntoView({ block: 'nearest' });
    }

    disconnect() {
        window.clearTimeout(this.timer);
        document.removeEventListener('click', this.boundDocumentClick, true);
        document.removeEventListener('submit', this.boundDocumentSubmit, true);
        window.removeEventListener('beforeunload', this.boundBeforeUnload);
    }

    scheduleSave() {
        this.refreshPlaceholder();
        this.updateSidebar();
        this.dirty = this.snapshot() !== this.lastSaved;
        this.setStatus(this.dirty ? 'dirty' : 'saved');
        window.clearTimeout(this.timer);
        this.timer = window.setTimeout(() => {
            this.save();
        }, 700);
    }

    onDate() {
        this.scheduleSave();
    }

    onKeydown(event) {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
            event.preventDefault();
            this.save();
        }
    }

    keepSelection(event) {
        if (event.target.closest('input, select, textarea')) {
            this.rememberSelection();
            return;
        }
        event.preventDefault();
    }

    rememberSelection() {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return;
        }
        const range = selection.getRangeAt(0);
        if (this.editorTarget.contains(range.commonAncestorContainer)) {
            this.savedRange = range.cloneRange();
        }
    }

    focusEditor() {
        this.editorTarget.focus({ preventScroll: true });
        if (!this.savedRange) {
            return;
        }
        const selection = window.getSelection();
        if (!selection) {
            return;
        }
        selection.removeAllRanges();
        selection.addRange(this.savedRange);
    }

    format(event) {
        event.preventDefault();
        const button = event.currentTarget;
        const command = button.dataset.command;
        const value = button.dataset.value || null;
        if (!command) {
            return;
        }

        this.focusEditor();
        document.execCommand('styleWithCSS', false, true);

        if (command === 'formatBlock') {
            const tag = value || 'p';
            if (!document.execCommand('formatBlock', false, tag)) {
                if (!document.execCommand('formatBlock', false, `<${tag}>`)) {
                    document.execCommand('formatBlock', false, tag.toUpperCase());
                }
            }
        } else if (command === 'hiliteColor') {
            if (!document.execCommand('hiliteColor', false, value)) {
                document.execCommand('backColor', false, value);
            }
        } else {
            document.execCommand(command, false, value);
        }

        this.rememberSelection();
        this.closePalettes();
        this.scheduleSave();
    }

    onSize(event) {
        const select = event.currentTarget;
        const size = select.value;
        select.value = '';
        if (!size) {
            return;
        }

        this.focusEditor();
        document.execCommand('styleWithCSS', false, false);
        document.execCommand('fontSize', false, '7');
        this.editorTarget.querySelectorAll('font[size="7"]').forEach((node) => {
            node.removeAttribute('size');
            node.style.fontSize = size;
        });
        document.execCommand('styleWithCSS', false, true);
        this.rememberSelection();
        this.scheduleSave();
    }

    applyColor(event) {
        const input = event.currentTarget;
        this.focusEditor();
        document.execCommand('styleWithCSS', false, true);
        if (input.dataset.kind === 'back') {
            if (!document.execCommand('hiliteColor', false, input.value)) {
                document.execCommand('backColor', false, input.value);
            }
        } else {
            document.execCommand('foreColor', false, input.value);
        }
        this.rememberSelection();
        this.closePalettes();
        this.scheduleSave();
    }

    togglePalette(event) {
        event.preventDefault();
        const name = event.currentTarget.dataset.palette;
        const palette = name === 'back' ? this.backPaletteTarget : this.forePaletteTarget;
        const willOpen = palette.hidden;
        this.closePalettes();
        palette.hidden = !willOpen;
    }

    closePalettes() {
        if (this.hasForePaletteTarget) {
            this.forePaletteTarget.hidden = true;
        }
        if (this.hasBackPaletteTarget) {
            this.backPaletteTarget.hidden = true;
        }
    }

    onPaste(event) {
        event.preventDefault();
        const clipboard = event.clipboardData;
        if (!clipboard) {
            return;
        }

        let html = clipboard.getData('text/html') || '';
        const text = clipboard.getData('text/plain') || '';
        if (html.trim() !== '') {
            if (html.length > 150000) {
                html = html.slice(0, 150000);
            }
            document.execCommand('insertHTML', false, this.cleanHtml(html));
        } else {
            document.execCommand('insertText', false, text);
        }

        this.rememberSelection();
        this.scheduleSave();
    }

    onDrop(event) {
        if (event.dataTransfer && event.dataTransfer.files.length > 0) {
            event.preventDefault();
        }
    }

    save(options = {}) {
        if (options.keepalive) {
            this.saveKeepalive();
            return Promise.resolve(true);
        }

        if (this.savePromise) {
            return this.savePromise;
        }

        this.savePromise = this.doSave().finally(() => {
            this.savePromise = null;
        });

        return this.savePromise;
    }

    async doSave(depth = 0) {
        window.clearTimeout(this.timer);
        const body = {
            title: this.titleTarget.value,
            heldAt: this.dateTarget.value,
            content: this.editorTarget.innerHTML,
        };
        const sent = JSON.stringify(body);
        if (sent === this.lastSaved) {
            this.dirty = false;
            this.setStatus('saved');
            return true;
        }

        this.setStatus('saving');

        try {
            const response = await fetch(this.saveUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ ...body, _token: this.csrfValue }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.ok !== true) {
                this.setStatus('error', data.error || 'Échec de l\'enregistrement');
                return false;
            }

            if (typeof data.title === 'string') {
                this.titleTarget.value = data.title;
                body.title = data.title;
            }

            const sentNormalized = JSON.stringify(body);
            const current = this.snapshot();
            this.lastSaved = sentNormalized;
            this.updateSidebar();

            if (current !== sentNormalized && depth < 4) {
                return this.doSave(depth + 1);
            }

            this.dirty = current !== this.lastSaved;
            this.setStatus(this.dirty ? 'dirty' : 'saved');
            return true;
        } catch {
            this.setStatus('error', 'Échec de l\'enregistrement');
            return false;
        }
    }

    saveKeepalive() {
        if (!this.dirty) {
            return;
        }

        const body = JSON.stringify({
            title: this.titleTarget.value,
            heldAt: this.dateTarget.value,
            content: this.editorTarget.innerHTML,
            _token: this.csrfValue,
        });

        fetch(this.saveUrlValue, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
            keepalive: true,
            body,
        });
    }

    onDocumentClick(event) {
        const target = event.target;
        if (!(target instanceof Element)) {
            this.closePalettes();
            return;
        }
        if (target.closest('.notebook-pop')) {
            return;
        }
        this.closePalettes();

        if (!this.dirty || event.defaultPrevented || event.button !== 0) {
            return;
        }
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const link = event.target.closest('a[href]');
        if (!link || link.target === '_blank' || link.hasAttribute('download')) {
            return;
        }

        let url;
        try {
            url = new URL(link.href, window.location.href);
        } catch {
            return;
        }
        if (url.origin !== window.location.origin) {
            return;
        }

        event.preventDefault();
        const href = link.href;
        this.save().then((ok) => {
            if (ok) {
                window.location.href = href;
            }
        });
    }

    onDocumentSubmit(event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.meetingBypass === '1') {
            return;
        }
        if (form.hasAttribute('data-confirm') || !this.dirty) {
            return;
        }

        event.preventDefault();
        this.save().then((ok) => {
            if (!ok) {
                return;
            }
            form.dataset.meetingBypass = '1';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }

    onBeforeUnload() {
        this.saveKeepalive();
    }

    snapshot() {
        return JSON.stringify({
            title: this.titleTarget.value,
            heldAt: this.dateTarget.value,
            content: this.editorTarget.innerHTML,
        });
    }

    updateSidebar() {
        const title = this.titleTarget.value.trim() || 'Sans titre';
        if (this.hasPageTitleTarget) {
            this.pageTitleTarget.textContent = title;
        }
        if (this.hasActivePageTarget) {
            this.activePageTarget.dataset.heldAt = this.dateTarget.value;
        }
        if (this.hasPageDateTarget && /^\d{4}-\d{2}-\d{2}$/.test(this.dateTarget.value)) {
            const [year, month, day] = this.dateTarget.value.split('-');
            this.pageDateTarget.textContent = `${day}/${month}/${year}`;
        }
        this.sortPages();
    }

    sortPages() {
        if (!this.hasPageListTarget) {
            return;
        }
        const items = [...this.pageListTarget.querySelectorAll('[data-held-at]')];
        items.sort((a, b) => {
            const heldA = a.dataset.heldAt || '';
            const heldB = b.dataset.heldAt || '';
            if (heldA !== heldB) {
                return heldA < heldB ? 1 : -1;
            }
            return Number(b.dataset.meetingId || 0) - Number(a.dataset.meetingId || 0);
        });
        items.forEach((item) => this.pageListTarget.appendChild(item));
    }

    refreshPlaceholder() {
        const text = (this.editorTarget.textContent || '').replace(/\u00a0/g, ' ').trim();
        this.editorTarget.classList.toggle('is-empty', text === '');
    }

    setStatus(state, message) {
        if (!this.hasStatusTarget) {
            return;
        }
        const labels = {
            saving: 'Enregistrement…',
            saved: 'Enregistré',
            dirty: 'Modification…',
            error: message || 'Échec de l\'enregistrement',
        };
        this.statusTarget.classList.remove('is-saving', 'is-saved', 'is-dirty', 'is-error');
        this.statusTarget.textContent = labels[state] || labels.saved;
        this.statusTarget.classList.add(state === 'dirty' ? 'is-dirty' : `is-${state}`);
    }

    cleanHtml(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        doc.querySelectorAll('script, style, iframe, object, embed, img, svg, link, meta, form').forEach((node) => node.remove());
        doc.body.querySelectorAll('*').forEach((el) => {
            [...el.attributes].forEach((attr) => {
                const name = attr.name.toLowerCase();
                if (name.startsWith('on') || name === 'src' || name === 'href') {
                    el.removeAttribute(attr.name);
                }
            });
        });
        return doc.body.innerHTML;
    }
}
