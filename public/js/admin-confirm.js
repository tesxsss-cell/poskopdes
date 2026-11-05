(() => {
    'use strict';
    const dialog = document.getElementById('admin-confirm-dialog');
    if (!dialog) return;
    const error = document.querySelector('[data-admin-confirm-error]');
    const cancel = dialog.querySelector('[data-admin-cancel]');
    const confirm = dialog.querySelector('[data-admin-submit]');
    let pendingForm = null;
    let allowedForm = null;
    let trigger = null;
    const unavailable = () => {
        error.textContent = 'Konfirmasi tidak tersedia. Perbarui browser dan coba lagi.';
        error.hidden = false;
    };
    document.querySelectorAll('form[data-admin-confirm]').forEach((form) => {
        const button = form.querySelector('[data-admin-confirm-trigger]');
        button.addEventListener('click', () => {
            if (typeof form.requestSubmit !== 'function') { unavailable(); return; }
            form.requestSubmit();
        });
        form.addEventListener('submit', (event) => {
            if (allowedForm === form) { allowedForm = null; return; }
            event.preventDefault();
            if (typeof dialog.showModal !== 'function') { unavailable(); return; }
            error.hidden = true;
            pendingForm = form;
            trigger = button;
            dialog.dataset.tone = form.dataset.tone || 'danger';
            dialog.querySelector('#admin-confirm-title').textContent = form.dataset.title;
            dialog.querySelector('#admin-confirm-message').textContent = form.dataset.message;
            const item = dialog.querySelector('#admin-confirm-item');
            item.textContent = form.dataset.item || '';
            item.hidden = !item.textContent;
            confirm.textContent = form.dataset.confirmLabel || 'Ya, lanjutkan';
            confirm.disabled = false;
            dialog.showModal();
            cancel.focus();
        });
    });
    cancel.addEventListener('click', () => dialog.close());
    confirm.addEventListener('click', () => {
        const form = pendingForm;
        if (!form) return;
        confirm.disabled = true;
        allowedForm = form;
        dialog.close();
        form.requestSubmit();
    });
    dialog.addEventListener('close', () => {
        pendingForm = null;
        if (trigger?.isConnected) trigger.focus();
    });
})();
