import { Head, Link } from '@inertiajs/react';
import {
    CheckCircle2,
    ClipboardList,
    CreditCard,
    Megaphone,
} from 'lucide-react';
import InfluencerPortalLayout from '@/Layouts/InfluencerPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatCard from '@/Components/Crm/StatCard';
import StatusBadge from '@/Components/Crm/StatusBadge';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import { formatDate } from '@/lib/format';
import { usePage } from '@inertiajs/react';

export default function Dashboard({ stats, upcoming_deadlines = [], recent_campaigns = [], influencer }) {
    const { crm } = usePage().props;

    return (
        <InfluencerPortalLayout title="Dashboard">
            <Head title="Portal Dashboard" />

            <PageHeader
                title={`Welcome${influencer?.name ? `, ${influencer.name}` : ''}`}
                description="Your campaigns, deliverables, and payments at a glance"
            />

            <div className="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard label="Active campaigns" value={stats.active_campaigns} icon={Megaphone} tone="accent" />
                <StatCard label="Pending deliverables" value={stats.pending_deliverables} icon={ClipboardList} tone="warning" />
                <StatCard label="Completed deliverables" value={stats.completed_deliverables} icon={CheckCircle2} />
                <StatCard
                    label="Pending payments"
                    value={<MoneyDisplay amount={stats.pending_payments_amount} />}
                    icon={CreditCard}
                    tone="warning"
                />
                <StatCard label="Unpaid campaigns" value={stats.pending_payments_count} />
                <StatCard label="Completed campaigns" value={stats.completed_campaigns} />
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <div className="crm-card overflow-hidden">
                    <div className="border-b border-surface-border px-5 py-3">
                        <h3 className="font-display text-sm font-semibold text-ink">Upcoming deadlines</h3>
                    </div>
                    <ul className="divide-y divide-surface-border">
                        {upcoming_deadlines.map((item) => (
                            <li key={item.id} className="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                <div>
                                    <p className="font-medium text-ink">{item.title}</p>
                                    <p className="text-xs text-ink-muted">{item.campaign_name}</p>
                                </div>
                                <div className="text-right">
                                    <p className="font-medium text-ink">{formatDate(item.deadline)}</p>
                                    <StatusBadge status={item.status} labels={crm.deliverable_statuses} />
                                </div>
                            </li>
                        ))}
                        {!upcoming_deadlines.length && (
                            <li className="px-5 py-8 text-center text-sm text-ink-muted">No upcoming deadlines</li>
                        )}
                    </ul>
                </div>

                <div className="crm-card overflow-hidden">
                    <div className="flex items-center justify-between border-b border-surface-border px-5 py-3">
                        <h3 className="font-display text-sm font-semibold text-ink">Recent campaigns</h3>
                        <Link href={route('influencer.campaigns.index')} className="text-xs font-medium text-accent hover:text-accent-hover">
                            View all
                        </Link>
                    </div>
                    <ul className="divide-y divide-surface-border">
                        {recent_campaigns.map((item) => (
                            <li key={item.id}>
                                <Link
                                    href={route('influencer.campaigns.show', item.id)}
                                    className="flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-surface"
                                >
                                    <div>
                                        <p className="font-medium text-ink">{item.campaign_name}</p>
                                        <p className="text-xs text-ink-muted">{item.brand_name || '—'}</p>
                                    </div>
                                    <div className="text-right">
                                        <StatusBadge status={item.status} labels={crm.collaboration_statuses} />
                                        <p className="mt-1 text-xs text-ink-muted">{formatDate(item.deadline || item.content_deadline)}</p>
                                    </div>
                                </Link>
                            </li>
                        ))}
                        {!recent_campaigns.length && (
                            <li className="px-5 py-8 text-center text-sm text-ink-muted">No campaigns yet</li>
                        )}
                    </ul>
                </div>
            </div>
        </InfluencerPortalLayout>
    );
}
