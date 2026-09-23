import { Head, Link, usePage } from '@inertiajs/react';
import ClientPortalLayout from '@/Layouts/ClientPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatusBadge from '@/Components/Crm/StatusBadge';
import { formatDate } from '@/lib/format';

export default function Index({ shortlists = [] }) {
    const { crm } = usePage().props;

    return (
        <ClientPortalLayout title="Influencer Shortlists">
            <Head title="Influencer Shortlists" />
            <PageHeader title="Influencer Shortlists" description="Proposals shared by Grovera Studio for your review" />

            <div className="space-y-3">
                {shortlists.map((sl) => (
                    <Link
                        key={sl.id}
                        href={route('client.shortlists.show', sl.id)}
                        className="crm-card block p-4 transition hover:border-accent/40"
                    >
                        <div className="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p className="font-medium text-ink">{sl.title}</p>
                                <p className="text-xs text-ink-muted">
                                    {sl.requirement?.requirement_number} · {sl.requirement?.title}
                                </p>
                                <p className="mt-1 text-xs text-ink-muted">{sl.items_count ?? sl.items?.length ?? 0} influencers</p>
                            </div>
                            <div className="text-right">
                                <StatusBadge status={sl.status} labels={crm.shortlist_statuses} />
                                <p className="mt-1 text-xs text-ink-muted">{formatDate(sl.shared_at)}</p>
                            </div>
                        </div>
                    </Link>
                ))}
                {!shortlists.length && (
                    <div className="crm-card px-5 py-10 text-center text-sm text-ink-muted">
                        No shortlists shared yet.
                    </div>
                )}
            </div>
        </ClientPortalLayout>
    );
}
