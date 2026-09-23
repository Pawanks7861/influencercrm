import { Head, Link, usePage } from '@inertiajs/react';
import InfluencerPortalLayout from '@/Layouts/InfluencerPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatusBadge from '@/Components/Crm/StatusBadge';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import { formatDate } from '@/lib/format';

export default function CampaignsIndex({ campaigns }) {
    const { crm } = usePage().props;
    const rows = campaigns?.data || [];

    return (
        <InfluencerPortalLayout title="My Campaigns">
            <Head title="My Campaigns" />
            <PageHeader title="My Campaigns" description="Campaigns you are assigned to" />

            <div className="crm-card overflow-hidden">
                <table className="min-w-full text-sm">
                    <thead className="bg-surface text-xs uppercase text-ink-muted">
                        <tr>
                            <th className="px-4 py-3 text-left">Campaign</th>
                            <th className="px-4 py-3 text-left">Brand</th>
                            <th className="px-4 py-3 text-left">Status</th>
                            <th className="px-4 py-3 text-right">Your fee</th>
                            <th className="px-4 py-3 text-left">Deadline</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.id} className="border-t border-surface-border">
                                <td className="px-4 py-3">
                                    <Link href={route('influencer.campaigns.show', row.id)} className="font-medium text-accent hover:text-accent-hover">
                                        {row.campaign_name}
                                    </Link>
                                    <p className="text-xs text-ink-muted">{row.client_name}</p>
                                </td>
                                <td className="px-4 py-3 text-ink-soft">{row.brand_name || '—'}</td>
                                <td className="px-4 py-3">
                                    <StatusBadge status={row.status} labels={crm.collaboration_statuses} />
                                </td>
                                <td className="px-4 py-3 text-right">
                                    <MoneyDisplay amount={row.influencer_cost} />
                                </td>
                                <td className="px-4 py-3">{formatDate(row.content_deadline || row.deadline)}</td>
                            </tr>
                        ))}
                        {!rows.length && (
                            <tr>
                                <td colSpan={5} className="px-4 py-10 text-center text-ink-muted">
                                    No campaigns assigned yet
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </InfluencerPortalLayout>
    );
}
