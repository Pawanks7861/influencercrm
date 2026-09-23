import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Edit3, NotebookPen, Wallet } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatCard from '@/Components/Crm/StatCard';
import StatusBadge from '@/Components/Crm/StatusBadge';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import PaymentStatusBadge from '@/Components/Crm/PaymentStatusBadge';
import NotesPanel from '@/Components/Crm/NotesPanel';
import AddNote from '@/Components/Crm/Modals/AddNote';
import RecordPayment from '@/Components/Crm/Modals/RecordPayment';
import ManageDeliverables from '@/Components/Crm/Modals/ManageDeliverables';
import ActionMenu from '@/Components/Crm/ActionMenu';
import { formatDate, labelFromMap } from '@/lib/format';

export default function Show({ campaign, totals, clientVisibility }) {
    const { crm } = usePage().props;
    const [noteOpen, setNoteOpen] = useState(false);
    const [paymentTarget, setPaymentTarget] = useState(null);
    const [deliverableTarget, setDeliverableTarget] = useState(null);

    const visibilityForm = useForm({
        show_budget: clientVisibility?.show_budget ?? false,
        show_deliverables: clientVisibility?.show_deliverables ?? true,
        show_influencers: clientVisibility?.show_influencers ?? true,
        show_posting_dates: clientVisibility?.show_posting_dates ?? true,
        show_content_links: clientVisibility?.show_content_links ?? false,
        show_payment_summary: clientVisibility?.show_payment_summary ?? false,
        show_notes: clientVisibility?.show_notes ?? false,
    });

    const updateStatus = (ci, status) => {
        router.patch(route('campaign-influencers.status', ci.id), { status }, { preserveScroll: true });
    };

    const saveVisibilityFlags = (e) => {
        e.preventDefault();
        visibilityForm.patch(route('campaigns.visibility', campaign.id), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout title={campaign.campaign_name}>
            <Head title={campaign.campaign_name} />

            <PageHeader
                title={campaign.campaign_name}
                description={
                    <span className="inline-flex flex-wrap items-center gap-2">
                        <StatusBadge status={campaign.status} labels={crm.campaign_statuses} />
                        {campaign.campaign_type && (
                            <StatusBadge status={campaign.campaign_type} labels={crm.campaign_types} />
                        )}
                        <span>{campaign.client?.company_name || campaign.client?.name}</span>
                        {campaign.brand_name && <span>· {campaign.brand_name}</span>}
                    </span>
                }
                actions={
                    <>
                        <button type="button" className="crm-btn-secondary" onClick={() => setNoteOpen(true)}>
                            <NotebookPen className="h-4 w-4" /> Note
                        </button>
                        <Link href={route('campaigns.edit', campaign.id)} className="crm-btn-primary">
                            <Edit3 className="h-4 w-4" /> Edit
                        </Link>
                    </>
                }
            />

            <div className="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <StatCard label="Influencers" value={totals?.influencer_count ?? 0} />
                <StatCard label="Influencer cost" value={<MoneyDisplay amount={totals?.total_influencer_cost} />} />
                <StatCard label="Additional cost" value={<MoneyDisplay amount={totals?.total_additional_cost} />} />
                <StatCard label="Grovera fee" value={<MoneyDisplay amount={totals?.total_grovera_fee} />} />
                <StatCard label="Campaign value" value={<MoneyDisplay amount={totals?.total_final_amount} />} />
                <StatCard label="Margin" value={<MoneyDisplay amount={totals?.margin} />} tone="accent" />
            </div>

            <div className="crm-card mb-6 p-5">
                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 className="font-display text-sm font-semibold text-ink">Client Visibility</h3>
                        <p className="text-xs text-ink-muted">
                            {clientVisibility?.visible_to_client
                                ? `Shared${clientVisibility.shared_at ? ` · ${formatDate(clientVisibility.shared_at)}` : ''}`
                                : 'Not shared with client'}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {clientVisibility?.visible_to_client ? (
                            <button
                                type="button"
                                className="crm-btn-secondary"
                                onClick={() => {
                                    if (confirm('Unshare this campaign from the client portal?')) {
                                        router.post(route('campaigns.unshare-with-client', campaign.id), {}, { preserveScroll: true });
                                    }
                                }}
                            >
                                Unshare
                            </button>
                        ) : (
                            <button
                                type="button"
                                className="crm-btn-primary"
                                onClick={() => {
                                    router.post(route('campaigns.share-with-client', campaign.id), visibilityForm.data, { preserveScroll: true });
                                }}
                            >
                                Share with Client
                            </button>
                        )}
                    </div>
                </div>
                <form className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 text-sm" onSubmit={saveVisibilityFlags}>
                    {[
                        ['show_budget', 'Budget'],
                        ['show_deliverables', 'Deliverables'],
                        ['show_influencers', 'Influencers'],
                        ['show_posting_dates', 'Posting dates'],
                        ['show_content_links', 'Content links'],
                        ['show_payment_summary', 'Payment summary'],
                        ['show_notes', 'Notes'],
                    ].map(([key, label]) => (
                        <label key={key} className="inline-flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={!!visibilityForm.data[key]}
                                onChange={(e) => visibilityForm.setData(key, e.target.checked)}
                            />
                            {label}
                        </label>
                    ))}
                    <div className="sm:col-span-2 lg:col-span-4">
                        <button type="submit" className="crm-btn-secondary" disabled={visibilityForm.processing}>
                            Save visibility flags
                        </button>
                    </div>
                </form>
            </div>

            <div className="mb-6 grid gap-4 lg:grid-cols-3 text-sm">
                <div className="crm-card p-4">
                    <p className="text-ink-muted">Start</p>
                    <p className="mt-1 font-medium text-ink">{formatDate(campaign.start_date)}</p>
                </div>
                <div className="crm-card p-4">
                    <p className="text-ink-muted">Deadline</p>
                    <p className="mt-1 font-medium text-ink">{formatDate(campaign.deadline)}</p>
                </div>
                <div className="crm-card p-4">
                    <p className="text-ink-muted">Budget</p>
                    <p className="mt-1"><MoneyDisplay amount={campaign.campaign_budget} /></p>
                </div>
            </div>

            <div className="crm-card mb-6 overflow-hidden">
                <div className="border-b border-surface-border px-4 py-3">
                    <h3 className="font-display text-sm font-semibold text-ink">Influencers</h3>
                </div>
                <div className="overflow-x-auto">
                    <table className="min-w-full text-sm">
                        <thead className="bg-surface text-xs uppercase text-ink-muted">
                            <tr>
                                <th className="px-4 py-3 text-left">Influencer</th>
                                <th className="px-4 py-3 text-left">Status</th>
                                <th className="px-4 py-3 text-right">Cost</th>
                                <th className="px-4 py-3 text-right">Additional</th>
                                <th className="px-4 py-3 text-right">Fee</th>
                                <th className="px-4 py-3 text-right">Final</th>
                                <th className="px-4 py-3 text-left">Deliverables</th>
                                <th className="px-4 py-3 text-left">Payment</th>
                                <th className="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(campaign.campaign_influencers || []).map((ci) => {
                                const done = (ci.deliverables || []).filter((d) => ['approved', 'posted'].includes(d.status?.value || d.status)).length;
                                const total = (ci.deliverables || []).length;
                                return (
                                    <tr key={ci.id} className="border-t border-surface-border">
                                        <td className="px-4 py-3">
                                            {ci.influencer ? (
                                                <Link href={route('influencers.show', ci.influencer.id)} className="font-medium text-accent hover:text-accent-hover">
                                                    {ci.influencer.name}
                                                </Link>
                                            ) : '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <select
                                                className="crm-input !py-1.5 text-xs"
                                                value={ci.status?.value || ci.status}
                                                onChange={(e) => updateStatus(ci, e.target.value)}
                                            >
                                                {Object.entries(crm.collaboration_statuses || {}).map(([value, label]) => (
                                                    <option key={value} value={value}>{label}</option>
                                                ))}
                                            </select>
                                        </td>
                                        <td className="px-4 py-3 text-right"><MoneyDisplay amount={ci.influencer_cost} /></td>
                                        <td className="px-4 py-3 text-right"><MoneyDisplay amount={ci.additional_cost} /></td>
                                        <td className="px-4 py-3 text-right"><MoneyDisplay amount={ci.grovera_fee} /></td>
                                        <td className="px-4 py-3 text-right"><MoneyDisplay amount={ci.final_amount} /></td>
                                        <td className="px-4 py-3 text-ink-soft">{done}/{total}</td>
                                        <td className="px-4 py-3">
                                            <PaymentStatusBadge status={ci.derived_payment_status} />
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <ActionMenu
                                                items={[
                                                    { label: 'Record payment', icon: Wallet, onClick: () => setPaymentTarget(ci) },
                                                    { label: 'Deliverables', onClick: () => setDeliverableTarget(ci) },
                                                ]}
                                            />
                                        </td>
                                    </tr>
                                );
                            })}
                            {!campaign.campaign_influencers?.length && (
                                <tr><td colSpan={9} className="px-4 py-8 text-center text-ink-muted">No influencers on this campaign</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {(campaign.deliverables || []).filter((d) => !d.campaign_influencer_id).length > 0 && (
                <div className="crm-card mb-6 overflow-hidden">
                    <div className="border-b border-surface-border px-4 py-3">
                        <h3 className="font-display text-sm font-semibold text-ink">Social Media Deliverables</h3>
                    </div>
                    <table className="min-w-full text-sm">
                        <thead className="bg-surface text-xs uppercase text-ink-muted">
                            <tr>
                                <th className="px-4 py-3 text-left">Type</th>
                                <th className="px-4 py-3 text-left">Platform</th>
                                <th className="px-4 py-3 text-left">Qty</th>
                                <th className="px-4 py-3 text-left">Deadline</th>
                                <th className="px-4 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(campaign.deliverables || []).filter((d) => !d.campaign_influencer_id).map((d) => (
                                <tr key={d.id} className="border-t border-surface-border">
                                    <td className="px-4 py-3">{labelFromMap(crm.deliverable_types, d.type)}</td>
                                    <td className="px-4 py-3">{labelFromMap(crm.platforms, d.platform) || d.platform || '—'}</td>
                                    <td className="px-4 py-3">{d.quantity}</td>
                                    <td className="px-4 py-3">{formatDate(d.deadline)}</td>
                                    <td className="px-4 py-3"><StatusBadge status={d.status} labels={crm.deliverable_statuses} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            <div className="grid gap-6 lg:grid-cols-2">
                <div className="crm-card overflow-hidden">
                    <div className="border-b border-surface-border px-4 py-3">
                        <h3 className="font-display text-sm font-semibold text-ink">Payments</h3>
                    </div>
                    <table className="min-w-full text-sm">
                        <thead className="bg-surface text-xs uppercase text-ink-muted">
                            <tr>
                                <th className="px-4 py-3 text-left">Date</th>
                                <th className="px-4 py-3 text-left">Influencer</th>
                                <th className="px-4 py-3 text-right">Amount</th>
                                <th className="px-4 py-3 text-left">Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(campaign.payments || []).map((p) => (
                                <tr key={p.id} className="border-t border-surface-border">
                                    <td className="px-4 py-3">{formatDate(p.payment_date)}</td>
                                    <td className="px-4 py-3">{p.influencer?.name || '—'}</td>
                                    <td className="px-4 py-3 text-right"><MoneyDisplay amount={p.amount} /></td>
                                    <td className="px-4 py-3">{labelFromMap(crm.payment_methods, p.payment_method)}</td>
                                </tr>
                            ))}
                            {!campaign.payments?.length && (
                                <tr><td colSpan={4} className="px-4 py-8 text-center text-ink-muted">No payments yet</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="crm-card p-5">
                    <h3 className="mb-3 font-display text-sm font-semibold text-ink">Notes</h3>
                    <NotesPanel notes={campaign.notes || []} onAdd={() => setNoteOpen(true)} />
                </div>
            </div>

            {campaign.remarks && (
                <div className="crm-card mt-6 p-5">
                    <h3 className="mb-2 font-display text-sm font-semibold text-ink">Remarks</h3>
                    <p className="whitespace-pre-wrap text-sm text-ink-soft">{campaign.remarks}</p>
                </div>
            )}

            <AddNote open={noteOpen} onClose={() => setNoteOpen(false)} notableType="campaign" notableId={campaign.id} />
            {paymentTarget && (
                <RecordPayment
                    open
                    onClose={() => setPaymentTarget(null)}
                    campaignInfluencerId={paymentTarget.id}
                    defaultAmount={paymentTarget.amount_pending || ''}
                />
            )}
            {deliverableTarget && (
                <ManageDeliverables open onClose={() => setDeliverableTarget(null)} campaignInfluencer={deliverableTarget} />
            )}
        </AuthenticatedLayout>
    );
}
