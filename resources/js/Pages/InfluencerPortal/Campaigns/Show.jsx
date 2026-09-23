import { Head, Link, usePage } from '@inertiajs/react';
import InfluencerPortalLayout from '@/Layouts/InfluencerPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatusBadge from '@/Components/Crm/StatusBadge';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import { formatDate, labelFromMap } from '@/lib/format';

export default function CampaignShow({ campaign }) {
    const { crm } = usePage().props;

    return (
        <InfluencerPortalLayout title={campaign.campaign_name}>
            <Head title={campaign.campaign_name} />
            <PageHeader
                title={campaign.campaign_name}
                description={
                    <span className="inline-flex flex-wrap items-center gap-2">
                        {campaign.brand_name && <span>{campaign.brand_name}</span>}
                        <StatusBadge status={campaign.status} labels={crm.collaboration_statuses} />
                        {campaign.campaign_type && (
                            <StatusBadge status={campaign.campaign_type} labels={crm.campaign_types} />
                        )}
                    </span>
                }
                actions={
                    <Link href={route('influencer.campaigns.index')} className="crm-btn-secondary">
                        Back to campaigns
                    </Link>
                }
            />

            <div className="mb-6 grid gap-4 lg:grid-cols-3">
                <div className="crm-card space-y-3 p-5 lg:col-span-2">
                    <h3 className="font-display text-sm font-semibold text-ink">Brief</h3>
                    <dl className="grid gap-3 sm:grid-cols-2 text-sm">
                        <div>
                            <dt className="text-ink-muted">Client</dt>
                            <dd className="font-medium text-ink">{campaign.client_name || '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-ink-muted">Platforms</dt>
                            <dd className="font-medium text-ink">
                                {(campaign.platforms || []).map((p) => labelFromMap(crm.platforms, p) || p).join(', ') || '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-ink-muted">Start date</dt>
                            <dd className="font-medium text-ink">{formatDate(campaign.start_date)}</dd>
                        </div>
                        <div>
                            <dt className="text-ink-muted">Campaign deadline</dt>
                            <dd className="font-medium text-ink">{formatDate(campaign.deadline)}</dd>
                        </div>
                        <div>
                            <dt className="text-ink-muted">Content deadline</dt>
                            <dd className="font-medium text-ink">{formatDate(campaign.content_deadline)}</dd>
                        </div>
                        <div>
                            <dt className="text-ink-muted">Posting date</dt>
                            <dd className="font-medium text-ink">{formatDate(campaign.posting_date)}</dd>
                        </div>
                    </dl>
                    {(campaign.remarks || campaign.campaign_remarks) && (
                        <div className="border-t border-surface-border pt-3">
                            <p className="text-xs font-medium uppercase tracking-wide text-ink-muted">Notes</p>
                            <p className="mt-1 whitespace-pre-wrap text-sm text-ink-soft">
                                {campaign.remarks || campaign.campaign_remarks}
                            </p>
                        </div>
                    )}
                </div>

                <div className="crm-card space-y-3 p-5">
                    <h3 className="font-display text-sm font-semibold text-ink">Your package</h3>
                    <div className="text-sm">
                        <p className="text-ink-muted">Your fee</p>
                        <p className="text-lg font-semibold text-ink">
                            <MoneyDisplay amount={campaign.influencer_cost} />
                        </p>
                    </div>
                    <div className="text-sm">
                        <p className="text-ink-muted">Paid</p>
                        <p className="font-medium text-ink">
                            <MoneyDisplay amount={campaign.amount_paid} />
                        </p>
                    </div>
                    <div className="text-sm">
                        <p className="text-ink-muted">Pending</p>
                        <p className="font-medium text-ink">
                            <MoneyDisplay amount={campaign.amount_pending} />
                        </p>
                    </div>
                    {campaign.content_url && (
                        <a href={campaign.content_url} target="_blank" rel="noreferrer" className="text-sm text-accent hover:text-accent-hover">
                            View content link
                        </a>
                    )}
                </div>
            </div>

            <div className="crm-card overflow-hidden">
                <div className="border-b border-surface-border px-5 py-3">
                    <h3 className="font-display text-sm font-semibold text-ink">Deliverables</h3>
                </div>
                <table className="min-w-full text-sm">
                    <thead className="bg-surface text-xs uppercase text-ink-muted">
                        <tr>
                            <th className="px-4 py-3 text-left">Title</th>
                            <th className="px-4 py-3 text-left">Type</th>
                            <th className="px-4 py-3 text-left">Deadline</th>
                            <th className="px-4 py-3 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {(campaign.deliverables || []).map((d) => (
                            <tr key={d.id} className="border-t border-surface-border">
                                <td className="px-4 py-3 font-medium text-ink">{d.title || '—'}</td>
                                <td className="px-4 py-3">{labelFromMap(crm.deliverable_types, d.type) || d.type}</td>
                                <td className="px-4 py-3">{formatDate(d.deadline)}</td>
                                <td className="px-4 py-3">
                                    <StatusBadge status={d.status} labels={crm.deliverable_statuses} />
                                </td>
                            </tr>
                        ))}
                        {!campaign.deliverables?.length && (
                            <tr>
                                <td colSpan={4} className="px-4 py-8 text-center text-ink-muted">
                                    No deliverables yet
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </InfluencerPortalLayout>
    );
}
