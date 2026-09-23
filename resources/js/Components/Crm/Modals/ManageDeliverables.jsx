import { useForm, usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import Drawer from '@/Components/Crm/Drawer';
import InputError from '@/Components/InputError';
import StatusBadge from '@/Components/Crm/StatusBadge';
import { Trash2 } from 'lucide-react';

export default function ManageDeliverables({ open, onClose, campaignInfluencer }) {
    const { crm } = usePage().props;
    const deliverableTypes = crm?.deliverable_types || {};
    const deliverableStatuses = {
        pending: 'Pending',
        in_progress: 'In Progress',
        submitted: 'Submitted',
        approved: 'Approved',
        posted: 'Posted',
    };

    const { data, setData, post, processing, errors, reset } = useForm({
        campaign_influencer_id: campaignInfluencer?.id,
        type: 'instagram_reel',
        quantity: 1,
        title: '',
        description: '',
        deadline: '',
        status: 'pending',
        remarks: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('deliverables.store'), {
            preserveScroll: true,
            onSuccess: () => reset('title', 'description', 'deadline', 'remarks'),
        });
    };

    return (
        <Drawer
            open={open}
            onClose={onClose}
            title="Manage deliverables"
            width="max-w-xl"
            footer={
                <div className="flex justify-end">
                    <button type="button" className="crm-btn-secondary" onClick={onClose}>
                        Close
                    </button>
                </div>
            }
        >
            <div className="space-y-6">
                <div className="space-y-2">
                    {(campaignInfluencer?.deliverables || []).map((d) => (
                        <div key={d.id} className="flex items-start justify-between gap-3 rounded-lg border border-surface-border px-3 py-2">
                            <div>
                                <p className="text-sm font-medium text-ink">{d.title || deliverableTypes[d.type] || d.type}</p>
                                <p className="text-xs text-ink-muted">
                                    Qty {d.quantity || 1}
                                    {d.deadline ? ` · Due ${d.deadline}` : ''}
                                </p>
                                <div className="mt-1">
                                    <StatusBadge status={d.status} labels={deliverableStatuses} />
                                </div>
                            </div>
                            <button
                                type="button"
                                className="rounded p-1 text-ink-muted hover:bg-red-50 hover:text-red-600"
                                onClick={() => {
                                    if (confirm('Delete deliverable?')) {
                                        router.delete(route('deliverables.destroy', d.id), { preserveScroll: true });
                                    }
                                }}
                            >
                                <Trash2 className="h-3.5 w-3.5" />
                            </button>
                        </div>
                    ))}
                    {!campaignInfluencer?.deliverables?.length && (
                        <p className="text-sm text-ink-muted">No deliverables yet.</p>
                    )}
                </div>

                <form onSubmit={submit} className="space-y-3 border-t border-surface-border pt-4">
                    <h4 className="text-sm font-semibold text-ink">Add deliverable</h4>
                    <div>
                        <label className="crm-label">Type</label>
                        <select className="crm-input" value={data.type} onChange={(e) => setData('type', e.target.value)}>
                            {Object.entries(deliverableTypes).map(([value, label]) => (
                                <option key={value} value={value}>
                                    {label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="crm-label">Quantity</label>
                            <input
                                type="number"
                                min="1"
                                className="crm-input"
                                value={data.quantity}
                                onChange={(e) => setData('quantity', e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="crm-label">Deadline</label>
                            <input
                                type="date"
                                className="crm-input"
                                value={data.deadline}
                                onChange={(e) => setData('deadline', e.target.value)}
                            />
                        </div>
                    </div>
                    <div>
                        <label className="crm-label">Title</label>
                        <input type="text" className="crm-input" value={data.title} onChange={(e) => setData('title', e.target.value)} />
                    </div>
                    <InputError message={errors.type} />
                    <button type="submit" className="crm-btn-primary" disabled={processing}>
                        Add deliverable
                    </button>
                </form>
            </div>
        </Drawer>
    );
}
