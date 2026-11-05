(() => {
    'use strict';
    const dialog = document.querySelector('.expense-dialog');
    let pendingForm = null;
    let allowedForm = null;
    document.querySelectorAll('[data-expense-delete]').forEach((form) => {
        form.querySelector('[data-expense-delete-trigger]').addEventListener('click', () => form.requestSubmit());
        form.addEventListener('submit', (event) => {
            if (allowedForm === form) { allowedForm = null; return; }
            event.preventDefault();
            if (!dialog || typeof dialog.showModal !== 'function') {
                const error = document.querySelector('[data-report-error]');
                error.textContent = 'Konfirmasi tidak tersedia di browser ini. Perbarui browser untuk menghapus biaya.';
                error.hidden = false;
                error.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            pendingForm = form;
            dialog.querySelector('[data-expense-dialog-item]').textContent = `${form.dataset.description} · ${form.dataset.amount}`;
            dialog.showModal();
            dialog.querySelector('[data-dialog-cancel]').focus();
        });
    });
    if (dialog) {
        dialog.querySelector('[data-dialog-cancel]').addEventListener('click', () => dialog.close());
        dialog.querySelector('[data-dialog-confirm]').addEventListener('click', () => {
            const form = pendingForm;
            dialog.close();
            if (form) { allowedForm = form; form.requestSubmit(); }
        });
        dialog.addEventListener('close', () => { pendingForm = null; });
    }

    const printButton = document.querySelector('[data-report-print-url]');
    if (printButton) printButton.addEventListener('click', () => {
        const error = document.querySelector('[data-report-error]');
        error.hidden = true;
        printButton.disabled = true;
        const frame = document.createElement('iframe');
        frame.className = 'report-print-frame';
        frame.title = 'Dokumen cetak rekap laporan';
        let finished = false;
        const cleanup = () => {
            if (finished) return;
            finished = true;
            printButton.disabled = false;
            frame.remove();
        };
        const fail = () => {
            cleanup();
            error.textContent = 'Dokumen cetak tidak dapat dimuat. Pastikan koneksi dan sesi login masih aktif, lalu coba lagi.';
            error.hidden = false;
            error.scrollIntoView({ behavior: 'smooth', block: 'center' });
        };
        const timeout = window.setTimeout(fail, 30000);
        frame.addEventListener('load', async () => {
            try {
                const doc = frame.contentDocument;
                if (!doc || !doc.querySelector('[data-report-print-ready]')) { clearTimeout(timeout); fail(); return; }
                if (doc.fonts && doc.fonts.ready) await doc.fonts.ready;
                if (finished) return;
                clearTimeout(timeout);
                frame.contentWindow.addEventListener('afterprint', cleanup, { once: true });
                frame.contentWindow.focus();
                frame.contentWindow.print();
                // Some browsers omit afterprint; leave ample time for the dialog.
                window.setTimeout(cleanup, 300000);
            } catch (_) { clearTimeout(timeout); fail(); }
        }, { once: true });
        frame.addEventListener('error', () => { clearTimeout(timeout); fail(); }, { once: true });
        frame.src = printButton.dataset.reportPrintUrl;
        document.body.appendChild(frame);
    });
})();
