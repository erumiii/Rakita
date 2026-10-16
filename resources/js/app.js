import './bootstrap';

// Native <dialog> modals: [data-modal-target="id"] opens, [data-modal-close]
// closes, click on backdrop closes.
document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-modal-target]');
    if (opener) {
        const el = document.getElementById(opener.getAttribute('data-modal-target'));
        if (el) el.showModal();
        return;
    }

    if (e.target.closest('[data-modal-close]')) {
        e.target.closest('dialog[data-modal]')?.close();
        return;
    }

    const dialog = e.target.closest('dialog[data-modal]');
    if (dialog && !e.target.closest('[data-modal-frame]')) dialog.close();
});

// Reopen the modal whose form just failed validation (server re-render).
document.querySelectorAll('dialog[data-modal][data-open]').forEach((el) => el.showModal());

// Generic confirm: [data-confirm] carries the message (optional
// data-confirm-title / data-confirm-action override the dialog heading and
// button label); the dialog submits the trigger's closest form on
// [data-confirm-submit].
const confirmDialog = document.getElementById('modal-confirm');
document.addEventListener('click', (e) => {
    if (confirmDialog) {
        const trigger = e.target.closest('[data-confirm]');
        if (trigger) {
            e.preventDefault();
            confirmDialog._form = trigger.closest('form');
            confirmDialog.querySelector('[data-confirm-message]').textContent = trigger.getAttribute('data-confirm');
            confirmDialog.querySelector('[data-confirm-title]').textContent = trigger.getAttribute('data-confirm-title') || 'Confirm';
            confirmDialog.querySelector('[data-confirm-action]').textContent = trigger.getAttribute('data-confirm-action') || 'Confirm';
            confirmDialog.showModal();
            return;
        }
        if (e.target.closest('[data-confirm-submit]') && confirmDialog.open) {
            confirmDialog.close();
            confirmDialog._form?.submit();
        }
    }
});
