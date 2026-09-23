import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Filter, Plus } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import SearchInput from '@/Components/Crm/SearchInput';
import StatusBadge from '@/Components/Crm/StatusBadge';
import DataTable from '@/Components/Crm/DataTable';
import EmptyState from '@/Components/Crm/EmptyState';
import ActionMenu from '@/Components/Crm/ActionMenu';
import FilterDrawer from '@/Components/Crm/FilterDrawer';
import FilterChips from '@/Components/Crm/FilterChips';
import ConfirmDialog from '@/Components/Crm/ConfirmDialog';
import { labelFromMap } from '@/lib/format';

const STATUS_LABELS = { lead: 'Lead', active: 'Active', inactive: 'Inactive' };

export default function ClientsIndex({ clients, filters = {} }) {
    const { auth, crm } = usePage().props;
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [draft, setDraft] = useState({
        status: filters.status || '',
        campaign_type: filters.campaign_type || '',
        has_active_campaign: filters.has_active_campaign || '',
        follow_up_status: filters.follow_up_status || '',
    });

    const canDelete = auth?.user?.permissions?.includes('clients.delete');

    const applyFilters = (next = {}) => {
        router.get(route('clients.index'), { ...filters, ...next, page: 1 }, { preserveState: true, preserveScroll: true });
    };

    const chips = useMemo(() => {
        const list = [];
        if (filters.status) list.push({ key: 'status', label: 'Status', value: STATUS_LABELS[filters.status] || filters.status });
        if (filters.campaign_type) {
            list.push({
                key: 'campaign_type',
                label: 'Campaign type',
                value: crm.campaign_types?.[filters.campaign_type] || filters.campaign_type,
            });
        }
        if (filters.has_active_campaign) list.push({ key: 'has_active_campaign', label: 'Active campaigns', value: 'Yes' });
        if (filters.follow_up_status) list.push({ key: 'follow_up_status', label: 'Follow-up', value: filters.follow_up_status });
        return list;
    }, [filters, crm]);

    const socialSummary = (row) => {
        const links = [row.instagram_url, row.facebook_url, row.linkedin_url, row.youtube_url, row.twitter_url].filter(Boolean);
        return links.length ? `${links.length} link${links.length > 1 ? 's' : ''}` : '—';
    };

    const columns = [
        {
            key: 'company_name',
            label: 'Company Name',
            sortKey: 'company_name',
            render: (row) => (
                <Link href={route('clients.show', row.id)} className="font-medium text-accent hover:text-accent-hover">
                    {row.company_name || row.name}
                </Link>
            ),
        },
        { key: 'contact_person', label: 'Contact Person', sortKey: 'contact_person', render: (row) => row.contact_person || '—' },
        { key: 'mobile', label: 'Mobile Number', sortKey: 'mobile', render: (row) => row.mobile || row.phone || '—' },
        { key: 'email', label: 'Email', sortKey: 'email', render: (row) => row.email || '—' },
        {
            key: 'website',
            label: 'Website',
            render: (row) =>
                row.website ? (
                    <a href={row.website} target="_blank" rel="noreferrer" className="text-accent hover:text-accent-hover">
                        {row.website.replace(/^https?:\/\//, '')}
                    </a>
                ) : (
                    '—'
                ),
        },
        { key: 'social', label: 'Social Links', render: (row) => socialSummary(row) },
        {
            key: 'active_campaigns',
            label: 'Active Campaigns',
            render: (row) => row.active_campaigns_count ?? 0,
        },
        {
            key: 'follow_up',
            label: 'Follow-up',
            render: (row) => (row.pending_follow_ups_count ? `${row.pending_follow_ups_count} pending` : '—'),
        },
        {
            key: 'status',
            label: 'Status',
            sortKey: 'status',
            render: (row) => <StatusBadge status={row.status} labels={STATUS_LABELS} />,
        },
        {
            key: 'actions',
            label: '',
            cellClassName: 'text-right',
            render: (row) => (
                <ActionMenu
                    items={[
                        { label: 'View client', onClick: () => router.visit(route('clients.show', row.id)) },
                        { label: 'Edit client', onClick: () => router.visit(route('clients.edit', row.id)) },
                        { label: 'Create campaign', onClick: () => router.visit(route('campaigns.create', { client_id: row.id })) },
                        canDelete && {
                            label: 'Archive',
                            danger: true,
                            onClick: () => setDeleteTarget(row),
                        },
                    ].filter(Boolean)}
                />
            ),
        },
    ];

    return (
        <AuthenticatedLayout title="Clients">
            <Head title="Clients" />
            <PageHeader
                title="Clients"
                description="Agency client companies and contacts"
                actions={
                    <Link href={route('clients.create')} className="crm-btn-primary">
                        <Plus className="h-4 w-4" /> Add client
                    </Link>
                }
            />

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <SearchInput
                    className="max-w-sm"
                    value={filters.search || ''}
                    onChange={(value) => applyFilters({ search: value || undefined })}
                    placeholder="Search company, contact, mobile…"
                />
                <button type="button" className="crm-btn-secondary" onClick={() => setDrawerOpen(true)}>
                    <Filter className="h-4 w-4" /> Filters
                </button>
            </div>

            <FilterChips
                chips={chips}
                onClear={(key) => applyFilters({ [key]: undefined })}
                onClearAll={() => applyFilters({ status: undefined, campaign_type: undefined, has_active_campaign: undefined, follow_up_status: undefined })}
            />

            {clients.data?.length ? (
                <DataTable
                    columns={columns}
                    rows={clients.data}
                    sort={filters.sort}
                    direction={filters.direction}
                    onSort={(sort, direction) => applyFilters({ sort, direction })}
                    paginator={clients}
                />
            ) : (
                <EmptyState
                    title="No clients yet"
                    description="Add your first client company to start managing campaigns."
                    action={
                        <Link href={route('clients.create')} className="crm-btn-primary">
                            Add client
                        </Link>
                    }
                />
            )}

            <FilterDrawer open={drawerOpen} onClose={() => setDrawerOpen(false)} title="Filter clients">
                <div className="space-y-4">
                    <div>
                        <label className="crm-label">Status</label>
                        <select className="crm-input" value={draft.status} onChange={(e) => setDraft({ ...draft, status: e.target.value })}>
                            <option value="">All</option>
                            {Object.entries(STATUS_LABELS).map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="crm-label">Campaign type</label>
                        <select
                            className="crm-input"
                            value={draft.campaign_type}
                            onChange={(e) => setDraft({ ...draft, campaign_type: e.target.value })}
                        >
                            <option value="">All</option>
                            {Object.entries(crm.campaign_types || {}).map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="crm-label">Has active campaign</label>
                        <select
                            className="crm-input"
                            value={draft.has_active_campaign}
                            onChange={(e) => setDraft({ ...draft, has_active_campaign: e.target.value })}
                        >
                            <option value="">Any</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div>
                        <label className="crm-label">Follow-up status</label>
                        <select
                            className="crm-input"
                            value={draft.follow_up_status}
                            onChange={(e) => setDraft({ ...draft, follow_up_status: e.target.value })}
                        >
                            <option value="">Any</option>
                            <option value="pending">Pending</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                    <button
                        type="button"
                        className="crm-btn-primary w-full"
                        onClick={() => {
                            applyFilters(draft);
                            setDrawerOpen(false);
                        }}
                    >
                        Apply filters
                    </button>
                </div>
            </FilterDrawer>

            <ConfirmDialog
                open={Boolean(deleteTarget)}
                title="Archive client?"
                message={`Archive ${deleteTarget?.company_name || deleteTarget?.name}?`}
                confirmLabel="Archive"
                onConfirm={() => {
                    if (deleteTarget) {
                        router.delete(route('clients.destroy', deleteTarget.id));
                    }
                    setDeleteTarget(null);
                }}
                onClose={() => setDeleteTarget(null)}
            />
        </AuthenticatedLayout>
    );
}
