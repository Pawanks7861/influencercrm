import { Head, Link } from '@inertiajs/react';
import {
    CheckCircle2,
    ClipboardList,
    Megaphone,
    Share2,
    Users,
} from 'lucide-react';
import ClientPortalLayout from '@/Layouts/ClientPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatCard from '@/Components/Crm/StatCard';
import StatusBadge from '@/Components/Crm/StatusBadge';
import { formatDate, labelFromMap } from '@/lib/format';
import { usePage } from '@inertiajs/react';

export default function Dashboard({
    stats,
    client,
    recent_requirements = [],
    recent_shortlists = [],
    current_campaigns = [],
    notifications = [],
}) {
    const { crm } = usePage().props;

    return (
        <ClientPortalLayout title="Dashboard">
            <Head title="Client Portal" />

            <PageHeader
                title={`Welcome${client?.name ? `, ${client.name}` : ''}`}
                description="Your requirements, shortlists, and campaigns at a glance"
            />

            <div className="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Open requirements" value={stats.open_requirements} icon={ClipboardList} tone="accent" />
                <StatCard label="Influencer requirements" value={stats.influencer_requirements} icon={Users} />
                <StatCard label="Social media requirements" value={stats.social_media_requirements} icon={Share2} />
                <StatCard label="Shortlists awaiting response" value={stats.shortlists_awaiting_response} icon={Users} tone="warning" />
                <StatCard label="Active campaigns" value={stats.active_campaigns} icon={Megaphone} />
                <StatCard label="Pending approvals" value={stats.pending_approvals} icon={CheckCircle2} tone="warning" />
                <StatCard label="Completed campaigns" value={stats.completed_campaigns} icon={Megaphone} />
                <StatCard label="Shared campaigns" value={stats.visible_campaigns} icon={Megaphone} />
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <div className="crm-card p-5">
                    <h3 className="mb-3 font-display text-sm font-semibold">Recent requirements</h3>
                    <ul className="space-y-2 text-sm">
                        {recent_requirements.map((r) => (
                            <li key={r.id}>
                                <Link href={route('client.requirements.show', r.id)} className="font-medium text-accent hover:text-accent-hover">
                                    {r.requirement_number}
                                </Link>
                                <p className="text-ink-soft">{r.title}</p>
                                <StatusBadge status={r.status} labels={crm.requirement_statuses} />
                            </li>
                        ))}
                        {!recent_requirements.length && <li className="text-ink-muted">None yet</li>}
                    </ul>
                </div>

                <div className="crm-card p-5">
                    <h3 className="mb-3 font-display text-sm font-semibold">Recently shared shortlists</h3>
                    <ul className="space-y-2 text-sm">
                        {recent_shortlists.map((s) => (
                            <li key={s.id}>
                                <Link href={route('client.shortlists.show', s.id)} className="font-medium text-accent hover:text-accent-hover">
                                    {s.title}
                                </Link>
                                <p className="text-xs text-ink-muted">{formatDate(s.shared_at)}</p>
                            </li>
                        ))}
                        {!recent_shortlists.length && <li className="text-ink-muted">None yet</li>}
                    </ul>
                </div>

                <div className="crm-card p-5">
                    <h3 className="mb-3 font-display text-sm font-semibold">Current campaigns</h3>
                    <ul className="space-y-2 text-sm">
                        {current_campaigns.map((c) => (
                            <li key={c.id}>
                                <Link href={route('client.campaigns.show', c.id)} className="font-medium text-accent hover:text-accent-hover">
                                    {c.campaign_name}
                                </Link>
                                <p className="text-xs text-ink-muted">{labelFromMap(crm.campaign_statuses, c.status)}</p>
                            </li>
                        ))}
                        {!current_campaigns.length && <li className="text-ink-muted">None shared yet</li>}
                    </ul>
                </div>
            </div>

            {notifications.length > 0 && (
                <div className="crm-card mt-4 p-5">
                    <h3 className="mb-3 font-display text-sm font-semibold">Notifications</h3>
                    <ul className="space-y-2 text-sm">
                        {notifications.map((n) => (
                            <li key={n.id} className={n.read_at ? 'text-ink-muted' : 'text-ink'}>
                                <p className="font-medium">{n.title}</p>
                                {n.body && <p className="text-ink-soft">{n.body}</p>}
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </ClientPortalLayout>
    );
}
