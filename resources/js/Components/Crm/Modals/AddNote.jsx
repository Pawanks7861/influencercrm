import { useForm } from '@inertiajs/react';
import Drawer from '@/Components/Crm/Drawer';
import InputError from '@/Components/InputError';

export default function AddNote({ open, onClose, notableType, notableId }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        notable_type: notableType,
        notable_id: notableId,
        note: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('notes.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset('note');
                onClose();
            },
        });
    };

    return (
        <Drawer
            open={open}
            onClose={onClose}
            title="Add note"
            footer={
                <div className="flex justify-end gap-2">
                    <button type="button" className="crm-btn-secondary" onClick={onClose}>
                        Cancel
                    </button>
                    <button type="submit" form="add-note-form" className="crm-btn-primary" disabled={processing}>
                        Save note
                    </button>
                </div>
            }
        >
            <form id="add-note-form" onSubmit={submit} className="space-y-4">
                <div>
                    <label className="crm-label">Note</label>
                    <textarea
                        className="crm-input min-h-[140px]"
                        value={data.note}
                        onChange={(e) => setData('note', e.target.value)}
                        required
                    />
                    <InputError message={errors.note} className="mt-1" />
                </div>
            </form>
        </Drawer>
    );
}
