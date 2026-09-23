import { Head, Link, usePage } from '@inertiajs/react';
import ClientPortalLayout from '@/Layouts/ClientPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatusBadge from '@/Components/Crm/StatusBadge';
import { formatDate, labelFromMap } from '@/lib/format';

export default function Index({ requirements = [] }) {
    const { crm } = usePage().props;
    const rows = Array.isArray(requirements) ? requirements : (requirements.data || []);

    return (
        <ClientPortalLayout title="My Requirements">
            <Head title="My Requirements" />
            <PageHeader
                title="My Requirements"
                description="Briefs you have submitted to Grovera Studio"
                actions={
                    <Link href={route('client.requirements.create')} className="crm-btn-primary">
                        New requirement
                    </Link>
                }
            />

            <div className="space-y-3">
                {rows.map((r) => (
                    <Link
                        key={r.id}
                        href={route('client.requirements.show', r.id)}
                        className="crm-card block p-4 transition hover:border-accent/40"
                    >
                        <div className="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p className="font-mono text-[11px] text-ink-muted">{r.requirement_number}</p>
                                <p className="font-medium text-ink">{r.title}</p>
                                <p className="text-xs text-ink-muted">{labelFromMap(crm.requirement_types, r.requirement_type)}</p>
                            </div>
                            <div className="text-right">
                                <StatusBadge status={r.status} labels={crm.requirement_statuses} />
                                <p className="mt-1 text-xs text-ink-muted">{formatDate(r.submitted_at)}</p>
                            </div>
                        </div>
                    </Link>
                ))}
                {!rows.length && (
                    <div className="crm-card px-5 py-10 text-center text-sm text-ink-muted">
                        No requirements yet. Submit your first brief to get started.
                    </div>
                )}
            </div>
        </ClientPortalLayout>
    );
}
