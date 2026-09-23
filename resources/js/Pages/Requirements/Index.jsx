import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatusBadge from '@/Components/Crm/StatusBadge';
import { formatDate, labelFromMap } from '@/lib/format';

export default function Index({ requirements, filters = {} }) {
    const { crm } = usePage().props;

    return (
        <AuthenticatedLayout title="Requirements">
            <Head title="Requirements" />
            <PageHeader
                title="Requirements"
                description="Client briefs awaiting shortlists and campaigns"
                actions={
                    <Link href={route('requirements.create')} className="crm-btn-primary">
                        New requirement
                    </Link>
                }
            />

            <div className="mb-4 flex flex-wrap gap-2">
                <input
                    className="crm-input max-w-xs"
                    placeholder="Search…"
                    defaultValue={filters.search || ''}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') {
                            router.get(route('requirements.index'), { ...filters, search: e.target.value }, { preserveState: true });
                        }
                    }}
                />
                <select
                    className="crm-input max-w-[200px]"
                    value={filters.status || ''}
                    onChange={(e) => router.get(route('requirements.index'), { ...filters, status: e.target.value || undefined }, { preserveState: true })}
                >
                    <option value="">All statuses</option>
                    {Object.entries(crm.requirement_statuses || {}).map(([value, label]) => (
                        <option key={value} value={value}>{label}</option>
                    ))}
                </select>
            </div>

            <div className="crm-card overflow-hidden">
                <table className="min-w-full text-sm">
                    <thead className="bg-surface text-xs uppercase text-ink-muted">
                        <tr>
                            <th className="px-4 py-3 text-left">Number</th>
                            <th className="px-4 py-3 text-left">Title</th>
                            <th className="px-4 py-3 text-left">Client</th>
                            <th className="px-4 py-3 text-left">Type</th>
                            <th className="px-4 py-3 text-left">Status</th>
                            <th className="px-4 py-3 text-left">Assigned</th>
                            <th className="px-4 py-3 text-left">Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        {(requirements.data || []).map((r) => (
                            <tr key={r.id} className="border-t border-surface-border">
                                <td className="px-4 py-3 font-mono text-xs">{r.requirement_number}</td>
                                <td className="px-4 py-3">
                                    <Link href={route('requirements.show', r.id)} className="font-medium text-accent hover:text-accent-hover">
                                        {r.title}
                                    </Link>
                                </td>
                                <td className="px-4 py-3 text-ink-soft">{r.client?.company_name || r.client?.name || '—'}</td>
                                <td className="px-4 py-3">{labelFromMap(crm.requirement_types, r.requirement_type)}</td>
                                <td className="px-4 py-3"><StatusBadge status={r.status} labels={crm.requirement_statuses} /></td>
                                <td className="px-4 py-3 text-ink-soft">{r.assignee?.name || '—'}</td>
                                <td className="px-4 py-3">{formatDate(r.submitted_at)}</td>
                            </tr>
                        ))}
                        {!requirements.data?.length && (
                            <tr><td colSpan={7} className="px-4 py-8 text-center text-ink-muted">No requirements yet</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AuthenticatedLayout>
    );
}
