import Modal from '@/Components/Modal';

export default function ConfirmDialog({
    show,
    onClose,
    onConfirm,
    title = 'Confirm',
    message = 'Are you sure?',
    confirmLabel = 'Confirm',
    processing = false,
    danger = false,
}) {
    return (
        <Modal show={show} onClose={onClose} maxWidth="sm">
            <div className="p-5">
                <h3 className="font-display text-lg font-semibold text-ink">{title}</h3>
                <p className="mt-2 text-sm text-ink-muted">{message}</p>
                <div className="mt-5 flex justify-end gap-2">
                    <button type="button" className="crm-btn-secondary" onClick={onClose} disabled={processing}>
                        Cancel
                    </button>
                    <button
                        type="button"
                        className={danger ? 'crm-btn-danger' : 'crm-btn-primary'}
                        onClick={onConfirm}
                        disabled={processing}
                    >
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </Modal>
    );
}
