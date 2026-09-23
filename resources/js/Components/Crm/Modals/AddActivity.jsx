import { useForm, usePage } from '@inertiajs/react';
import Drawer from '@/Components/Crm/Drawer';
import InputError from '@/Components/InputError';

export default function AddActivity({ open, onClose, influencerId, campaignId = null }) {
    const { crm } = usePage().props;
    const { data, setData, post, processing, errors, reset } = useForm({
        influencer_id: influencerId,
        campaign_id: campaignId,
        activity_type: 'called',
        activity_at: new Date().toISOString().slice(0, 16),
        note: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('activities.store'), {
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
            title="Log activity"
            footer={
                <div className="flex justify-end gap-2">
                    <button type="button" className="crm-btn-secondary" onClick={onClose}>
                        Cancel
                    </button>
                    <button type="submit" form="add-activity-form" className="crm-btn-primary" disabled={processing}>
                        Save activity
                    </button>
                </div>
            }
        >
            <form id="add-activity-form" onSubmit={submit} className="space-y-4">
                <div>
                    <label className="crm-label">Activity type</label>
                    <select className="crm-input" value={data.activity_type} onChange={(e) => setData('activity_type', e.target.value)}>
                        {Object.entries(crm?.activity_types || {}).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.activity_type} className="mt-1" />
                </div>
                <div>
                    <label className="crm-label">When</label>
                    <input
                        type="datetime-local"
                        className="crm-input"
                        value={data.activity_at}
                        onChange={(e) => setData('activity_at', e.target.value)}
                    />
                    <InputError message={errors.activity_at} className="mt-1" />
                </div>
                <div>
                    <label className="crm-label">Note</label>
                    <textarea className="crm-input min-h-[100px]" value={data.note} onChange={(e) => setData('note', e.target.value)} />
                    <InputError message={errors.note} className="mt-1" />
                </div>
            </form>
        </Drawer>
    );
}
