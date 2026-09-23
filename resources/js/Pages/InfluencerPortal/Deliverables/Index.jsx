import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import InfluencerPortalLayout from '@/Layouts/InfluencerPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatusBadge from '@/Components/Crm/StatusBadge';
import InputError from '@/Components/InputError';
import { formatDate, labelFromMap } from '@/lib/format';

export default function DeliverablesIndex({ deliverables }) {
    const { crm } = usePage().props;
    const rows = deliverables?.data || [];
    const [editing, setEditing] = useState(null);
    const [contentUrl, setContentUrl] = useState('');
    const [status, setStatus] = useState('in_progress');
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    const openEdit = (row) => {
        setEditing(row);
        setContentUrl(row.content_url || '');
        const current = row.status?.value || row.status;
        setStatus(current === 'submitted' ? 'submitted' : 'in_progress');
        setErrors({});
    };

    const submit = (e) => {
        e.preventDefault();
        if (!editing) return;
        setProcessing(true);
        router.patch(
            route('influencer.deliverables.update', editing.id),
            { content_url: contentUrl, status },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setEditing(null);
                    setProcessing(false);
                },
                onError: (err) => {
                    setErrors(err);
                    setProcessing(false);
                },
                onFinish: () => setProcessing(false),
            }
        );
    };

    return (
        <InfluencerPortalLayout title="My Deliverables">
            <Head title="My Deliverables" />
            <PageHeader title="My Deliverables" description="Update content links and submission status" />

            <div className="crm-card overflow-hidden">
                <table className="min-w-full text-sm">
                    <thead className="bg-surface text-xs uppercase text-ink-muted">
                        <tr>
                            <th className="px-4 py-3 text-left">Deliverable</th>
                            <th className="px-4 py-3 text-left">Campaign</th>
                            <th className="px-4 py-3 text-left">Deadline</th>
                            <th className="px-4 py-3 text-left">Status</th>
                            <th className="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.id} className="border-t border-surface-border">
                                <td className="px-4 py-3">
                                    <p className="font-medium text-ink">{row.title || labelFromMap(crm.deliverable_types, row.type) || 'Deliverable'}</p>
                                    {row.content_url && (
                                        <a href={row.content_url} target="_blank" rel="noreferrer" className="text-xs text-accent">
                                            Content link
                                        </a>
                                    )}
                                </td>
                                <td className="px-4 py-3 text-ink-soft">{row.campaign?.campaign_name || '—'}</td>
                                <td className="px-4 py-3">{formatDate(row.deadline)}</td>
                                <td className="px-4 py-3">
                                    <StatusBadge status={row.status} labels={crm.deliverable_statuses} />
                                </td>
                                <td className="px-4 py-3 text-right">
                                    <button type="button" className="crm-btn-secondary !px-2.5 !py-1.5 text-xs" onClick={() => openEdit(row)}>
                                        Update
                                    </button>
                                </td>
                            </tr>
                        ))}
                        {!rows.length && (
                            <tr>
                                <td colSpan={5} className="px-4 py-10 text-center text-ink-muted">
                                    No deliverables yet
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {editing && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4">
                    <div className="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
                        <h3 className="font-display text-lg font-semibold text-ink">Update deliverable</h3>
                        <p className="mt-1 text-sm text-ink-muted">{editing.title || 'Deliverable'}</p>
                        <form onSubmit={submit} className="mt-4 space-y-4">
                            <div>
                                <label className="crm-label">Content URL</label>
                                <input
                                    type="url"
                                    className="crm-input"
                                    value={contentUrl}
                                    onChange={(e) => setContentUrl(e.target.value)}
                                    placeholder="https://"
                                />
                                <InputError message={errors.content_url} className="mt-1" />
                            </div>
                            <div>
                                <label className="crm-label">Status</label>
                                <select className="crm-input" value={status} onChange={(e) => setStatus(e.target.value)}>
                                    <option value="in_progress">In progress</option>
                                    <option value="submitted">Submitted</option>
                                </select>
                                <InputError message={errors.status} className="mt-1" />
                                <p className="mt-1 text-xs text-ink-muted">Approval and posted statuses are managed by Grovera.</p>
                            </div>
                            <div className="flex justify-end gap-2">
                                <button type="button" className="crm-btn-secondary" onClick={() => setEditing(null)}>
                                    Cancel
                                </button>
                                <button type="submit" className="crm-btn-primary" disabled={processing}>
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </InfluencerPortalLayout>
    );
}
