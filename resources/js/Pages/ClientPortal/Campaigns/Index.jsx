import { Head, Link, usePage } from '@inertiajs/react';
import ClientPortalLayout from '@/Layouts/ClientPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatusBadge from '@/Components/Crm/StatusBadge';
import { labelFromMap } from '@/lib/format';

export default function Index({ campaigns = [] }) {
    const { crm } = usePage().props;

    return (
        <ClientPortalLayout title="My Campaigns">
            <Head title="My Campaigns" />
            <PageHeader title="My Campaigns" description="Campaigns shared with you" />

            <div className="space-y-3">
                {campaigns.map((campaign) => (
                    <Link
                        key={campaign.id}
                        href={route('client.campaigns.show', campaign.id)}
                        className="crm-card block p-4 transition hover:border-accent/40"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p className="font-medium text-ink">{campaign.campaign_name}</p>
                                {campaign.brand_name && (
                                    <p className="text-sm text-ink-muted">{campaign.brand_name}</p>
                                )}
                            </div>
                            <div className="flex items-center gap-2">
                                <StatusBadge status={campaign.status} labels={crm.campaign_statuses} />
                                <span className="text-xs text-ink-muted">
                                    {labelFromMap(crm.campaign_types, campaign.campaign_type)}
                                </span>
                            </div>
                        </div>
                    </Link>
                ))}
                {!campaigns.length && (
                    <div className="crm-card px-5 py-10 text-center text-sm text-ink-muted">
                        No campaigns visible yet.
                    </div>
                )}
            </div>
        </ClientPortalLayout>
    );
}
