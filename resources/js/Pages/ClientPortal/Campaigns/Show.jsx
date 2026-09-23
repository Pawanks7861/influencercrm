import { Head, usePage } from '@inertiajs/react';
import ClientPortalLayout from '@/Layouts/ClientPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatusBadge from '@/Components/Crm/StatusBadge';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import { formatDate, labelFromMap } from '@/lib/format';

export default function Show({ campaign }) {
    const { crm } = usePage().props;
    const visibility = campaign.visibility || {};

    return (
        <ClientPortalLayout title={campaign.campaign_name}>
            <Head title={campaign.campaign_name} />
            <PageHeader
                title={campaign.campaign_name}
                description={
                    <span className="inline-flex flex-wrap items-center gap-2">
                        <StatusBadge status={campaign.status} labels={crm.campaign_statuses} />
                        <span>{labelFromMap(crm.campaign_types, campaign.campaign_type)}</span>
                        {campaign.brand_name && <span>· {campaign.brand_name}</span>}
                    </span>
                }
            />

            <div className="mb-6 grid gap-3 sm:grid-cols-3 text-sm">
                {visibility.show_budget && (
                    <div className="crm-card p-4">
                        <p className="text-ink-muted">Budget</p>
                        <p className="mt-1 font-medium"><MoneyDisplay amount={campaign.campaign_budget} /></p>
                    </div>
                )}
                {visibility.show_posting_dates && (
                    <>
                        <div className="crm-card p-4">
                            <p className="text-ink-muted">Start</p>
                            <p className="mt-1 font-medium">{formatDate(campaign.start_date)}</p>
                        </div>
                        <div className="crm-card p-4">
                            <p className="text-ink-muted">Deadline</p>
                            <p className="mt-1 font-medium">{formatDate(campaign.deadline)}</p>
                        </div>
                    </>
                )}
            </div>

            {visibility.show_influencers && (
                <div className="crm-card mb-6 overflow-hidden">
                    <div className="border-b border-surface-border px-4 py-3">
                        <h3 className="font-display text-sm font-semibold">Approved influencers</h3>
                    </div>
                    <ul className="divide-y divide-surface-border">
                        {(campaign.influencers || []).map((inf) => (
                            <li key={inf.id} className="px-4 py-3 text-sm">
                                <p className="font-medium text-ink">{inf.display_name || 'Influencer'}</p>
                                {inf.instagram_username && (
                                    <a
                                        href={inf.instagram_url || `https://instagram.com/${inf.instagram_username}`}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-xs text-accent hover:text-accent-hover"
                                    >
                                        @{inf.instagram_username}
                                    </a>
                                )}
                                {visibility.show_posting_dates && inf.posting_date && (
                                    <p className="text-xs text-ink-muted">Posting: {formatDate(inf.posting_date)}</p>
                                )}
                            </li>
                        ))}
                        {!campaign.influencers?.length && (
                            <li className="px-4 py-8 text-center text-ink-muted">No influencers to show</li>
                        )}
                    </ul>
                </div>
            )}

            {visibility.show_deliverables && (
                <div className="crm-card mb-6 overflow-hidden">
                    <div className="border-b border-surface-border px-4 py-3">
                        <h3 className="font-display text-sm font-semibold">Deliverables</h3>
                    </div>
                    <table className="min-w-full text-sm">
                        <thead className="bg-surface text-xs uppercase text-ink-muted">
                            <tr>
                                <th className="px-4 py-3 text-left">Type</th>
                                <th className="px-4 py-3 text-left">Platform</th>
                                <th className="px-4 py-3 text-left">Qty</th>
                                <th className="px-4 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(campaign.deliverables || []).map((d) => (
                                <tr key={d.id} className="border-t border-surface-border">
                                    <td className="px-4 py-3">{labelFromMap(crm.deliverable_types, d.type)}</td>
                                    <td className="px-4 py-3">{labelFromMap(crm.platforms, d.platform) || d.platform || '—'}</td>
                                    <td className="px-4 py-3">{d.quantity}</td>
                                    <td className="px-4 py-3"><StatusBadge status={d.status} labels={crm.deliverable_statuses} /></td>
                                </tr>
                            ))}
                            {!campaign.deliverables?.length && (
                                <tr><td colSpan={4} className="px-4 py-8 text-center text-ink-muted">No deliverables</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            {visibility.show_payment_summary && campaign.payment_summary && (
                <div className="crm-card mb-6 grid gap-3 p-5 sm:grid-cols-3 text-sm">
                    <div>
                        <p className="text-ink-muted">Campaign value</p>
                        <p className="font-medium"><MoneyDisplay amount={campaign.payment_summary.total_campaign_value} /></p>
                    </div>
                    <div>
                        <p className="text-ink-muted">Paid</p>
                        <p className="font-medium"><MoneyDisplay amount={campaign.payment_summary.amount_paid} /></p>
                    </div>
                    <div>
                        <p className="text-ink-muted">Pending</p>
                        <p className="font-medium"><MoneyDisplay amount={campaign.payment_summary.amount_pending} /></p>
                    </div>
                </div>
            )}

            {visibility.show_notes && campaign.remarks && (
                <div className="crm-card p-5">
                    <h3 className="mb-2 font-display text-sm font-semibold">Notes</h3>
                    <p className="whitespace-pre-wrap text-sm text-ink-soft">{campaign.remarks}</p>
                </div>
            )}
        </ClientPortalLayout>
    );
}
