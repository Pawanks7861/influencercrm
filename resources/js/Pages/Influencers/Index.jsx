import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
    Columns3,
    Download,
    Filter,
    Plus,
    Users,
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import DataTable from '@/Components/Crm/DataTable';
import SearchInput from '@/Components/Crm/SearchInput';
import FilterDrawer from '@/Components/Crm/FilterDrawer';
import FilterChips from '@/Components/Crm/FilterChips';
import EmptyState from '@/Components/Crm/EmptyState';
import ActionMenu from '@/Components/Crm/ActionMenu';
import InfluencerTypeBadge from '@/Components/Crm/InfluencerTypeBadge';
import StatusBadge from '@/Components/Crm/StatusBadge';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import ConfirmDialog from '@/Components/Crm/ConfirmDialog';
import AddActivity from '@/Components/Crm/Modals/AddActivity';
import AddFollowUp from '@/Components/Crm/Modals/AddFollowUp';
import AddNote from '@/Components/Crm/Modals/AddNote';
import AddToCampaign from '@/Components/Crm/Modals/AddToCampaign';
import { formatDate } from '@/lib/format';

const ALL_COLUMNS = [
    { key: 'name', label: 'Name', sortKey: 'name', default: true },
    { key: 'instagram_username', label: 'Instagram', sortKey: 'instagram_username', default: true },
    { key: 'mobile', label: 'Mobile', sortKey: 'mobile', default: true },
    { key: 'email', label: 'Email', sortKey: 'email', default: false },
    { key: 'location', label: 'Location', sortKey: 'location', default: true },
    { key: 'influencer_type', label: 'Type', sortKey: 'influencer_type', default: true },
    { key: 'default_price', label: 'Default price', sortKey: 'default_price', default: true },
    { key: 'status', label: 'Status', sortKey: 'status', default: true },
    { key: 'created_at', label: 'Created', sortKey: 'created_at', default: false },
    { key: 'actions', label: '', default: true },
];

export default function InfluencersIndex({ influencers, filters = {}, preferences, locations = [] }) {
    const { crm } = usePage().props;
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [columnsOpen, setColumnsOpen] = useState(false);
    const [draft, setDraft] = useState({
        influencer_type: filters.influencer_type || '',
        location: filters.location || '',
        status: filters.status || '',
        price_min: filters.price_min || '',
        price_max: filters.price_max || '',
    });
    const [visibleKeys, setVisibleKeys] = useState(() => {
        if (Array.isArray(preferences) && preferences.length) return preferences;
        return ALL_COLUMNS.filter((c) => c.default).map((c) => c.key);
    });
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [modal, setModal] = useState({ type: null, influencer: null });

    const applyFilters = (next = {}) => {
        router.get(
            route('influencers.index'),
            { ...filters, ...next, page: 1 },
            { preserveState: true, preserveScroll: true }
        );
    };

    const chips = useMemo(() => {
        const list = [];
        if (filters.influencer_type) {
            list.push({ key: 'influencer_type', label: 'Type', value: crm.influencer_types?.[filters.influencer_type] || filters.influencer_type });
        }
        if (filters.location) list.push({ key: 'location', label: 'Location', value: filters.location });
        if (filters.status) list.push({ key: 'status', label: 'Status', value: filters.status });
        if (filters.price_min) list.push({ key: 'price_min', label: 'Min price', value: filters.price_min });
        if (filters.price_max) list.push({ key: 'price_max', label: 'Max price', value: filters.price_max });
        return list;
    }, [filters, crm]);

    const saveColumns = (keys) => {
        setVisibleKeys(keys);
        router.post(route('influencers.preferences'), { table_key: 'influencers', columns: keys }, { preserveScroll: true });
    };

    const columns = ALL_COLUMNS.filter((c) => visibleKeys.includes(c.key)).map((col) => {
        if (col.key === 'name') {
            return {
                ...col,
                render: (row) => (
                    <Link href={route('influencers.show', row.id)} className="font-medium text-ink hover:text-accent">
                        {row.name}
                    </Link>
                ),
            };
        }
        if (col.key === 'instagram_username') {
            return {
                ...col,
                render: (row) => (row.instagram_username ? `@${row.instagram_username}` : '—'),
            };
        }
        if (col.key === 'influencer_type') {
            return {
                ...col,
                render: (row) => <InfluencerTypeBadge type={row.influencer_type} labels={crm.influencer_types} />,
            };
        }
        if (col.key === 'default_price') {
            return { ...col, render: (row) => <MoneyDisplay amount={row.default_price} /> };
        }
        if (col.key === 'status') {
            return { ...col, render: (row) => <StatusBadge status={row.status} /> };
        }
        if (col.key === 'created_at') {
            return { ...col, render: (row) => formatDate(row.created_at) };
        }
        if (col.key === 'actions') {
            return {
                ...col,
                cellClassName: 'text-right',
                render: (row) => (
                    <ActionMenu
                        items={[
                            { label: 'View', onClick: () => router.visit(route('influencers.show', row.id)) },
                            { label: 'Edit', onClick: () => router.visit(route('influencers.edit', row.id)) },
                            { label: 'Add to campaign', onClick: () => setModal({ type: 'campaign', influencer: row }) },
                            { label: 'Add activity', onClick: () => setModal({ type: 'activity', influencer: row }) },
                            { label: 'Add follow-up', onClick: () => setModal({ type: 'followup', influencer: row }) },
                            { label: 'Add note', onClick: () => setModal({ type: 'note', influencer: row }) },
                            { label: 'Delete', danger: true, onClick: () => setDeleteTarget(row) },
                        ]}
                    />
                ),
            };
        }
        return col;
    });

    return (
        <AuthenticatedLayout title="Influencers">
            <Head title="Influencers" />

            <PageHeader
                title="Influencers"
                description="Manage your creator roster"
                actions={
                    <>
                        <a href={route('exports.influencers', filters)} className="crm-btn-secondary">
                            <Download className="h-4 w-4" /> Export
                        </a>
                        <Link href={route('import.index')} className="crm-btn-secondary">
                            Import
                        </Link>
                        <Link href={route('influencers.create')} className="crm-btn-primary">
                            <Plus className="h-4 w-4" /> Add influencer
                        </Link>
                    </>
                }
            />

            <div className="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center">
                <SearchInput
                    className="lg:max-w-sm"
                    value={filters.search || ''}
                    onChange={(search) => applyFilters({ search })}
                    placeholder="Search name, Instagram, mobile, email…"
                />
                <div className="flex flex-wrap gap-2 lg:ml-auto">
                    <button type="button" className="crm-btn-secondary" onClick={() => setDrawerOpen(true)}>
                        <Filter className="h-4 w-4" /> More filters
                    </button>
                    <button type="button" className="crm-btn-secondary" onClick={() => setColumnsOpen((v) => !v)}>
                        <Columns3 className="h-4 w-4" /> Columns
                    </button>
                </div>
            </div>

            <div className="mb-4">
                <FilterChips
                    chips={chips}
                    onRemove={(key) => applyFilters({ [key]: '' })}
                    onClear={() =>
                        applyFilters({
                            influencer_type: '',
                            location: '',
                            status: '',
                            price_min: '',
                            price_max: '',
                        })
                    }
                />
            </div>

            {columnsOpen && (
                <div className="crm-card mb-4 p-4">
                    <p className="mb-3 text-sm font-medium text-ink">Visible columns</p>
                    <div className="flex flex-wrap gap-3">
                        {ALL_COLUMNS.filter((c) => c.key !== 'actions').map((col) => (
                            <label key={col.key} className="inline-flex items-center gap-2 text-sm text-ink-soft">
                                <input
                                    type="checkbox"
                                    checked={visibleKeys.includes(col.key)}
                                    onChange={(e) => {
                                        const next = e.target.checked
                                            ? [...visibleKeys, col.key]
                                            : visibleKeys.filter((k) => k !== col.key);
                                        if (!next.includes('actions')) next.push('actions');
                                        saveColumns(next);
                                    }}
                                    className="rounded border-surface-border text-accent focus:ring-accent/30"
                                />
                                {col.label}
                            </label>
                        ))}
                    </div>
                </div>
            )}

            <DataTable
                columns={columns}
                rows={influencers.data || []}
                paginator={influencers}
                sort={filters.sort || 'created_at'}
                direction={filters.direction || 'desc'}
                onSort={(sortKey) => {
                    const direction =
                        filters.sort === sortKey && filters.direction === 'asc' ? 'desc' : 'asc';
                    applyFilters({ sort: sortKey, direction });
                }}
                empty={
                    <EmptyState
                        icon={Users}
                        title="No influencers found"
                        description="Try adjusting filters or add your first influencer."
                        action={
                            <Link href={route('influencers.create')} className="crm-btn-primary">
                                Add influencer
                            </Link>
                        }
                    />
                }
            />

            <FilterDrawer
                open={drawerOpen}
                onClose={() => setDrawerOpen(false)}
                onReset={() =>
                    setDraft({ influencer_type: '', location: '', status: '', price_min: '', price_max: '' })
                }
                onApply={() => {
                    applyFilters(draft);
                    setDrawerOpen(false);
                }}
            >
                <div>
                    <label className="crm-label">Type</label>
                    <select className="crm-input" value={draft.influencer_type} onChange={(e) => setDraft({ ...draft, influencer_type: e.target.value })}>
                        <option value="">All</option>
                        {Object.entries(crm.influencer_types || {}).map(([value, label]) => (
                            <option key={value} value={value}>{label}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label className="crm-label">Location</label>
                    <select className="crm-input" value={draft.location} onChange={(e) => setDraft({ ...draft, location: e.target.value })}>
                        <option value="">All</option>
                        {locations.map((loc) => (
                            <option key={loc} value={loc}>{loc}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label className="crm-label">Status</label>
                    <select className="crm-input" value={draft.status} onChange={(e) => setDraft({ ...draft, status: e.target.value })}>
                        <option value="">Active & inactive</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <label className="crm-label">Min price</label>
                        <input type="number" className="crm-input" value={draft.price_min} onChange={(e) => setDraft({ ...draft, price_min: e.target.value })} />
                    </div>
                    <div>
                        <label className="crm-label">Max price</label>
                        <input type="number" className="crm-input" value={draft.price_max} onChange={(e) => setDraft({ ...draft, price_max: e.target.value })} />
                    </div>
                </div>
            </FilterDrawer>

            <ConfirmDialog
                show={Boolean(deleteTarget)}
                onClose={() => setDeleteTarget(null)}
                title="Delete influencer?"
                message={`Remove ${deleteTarget?.name}? Influencers with campaign history cannot be deleted — archive them instead.`}
                confirmLabel="Delete"
                danger
                onConfirm={() => {
                    router.delete(route('influencers.destroy', deleteTarget.id), {
                        onFinish: () => setDeleteTarget(null),
                    });
                }}
            />

            {modal.type === 'activity' && (
                <AddActivity open influencerId={modal.influencer.id} onClose={() => setModal({ type: null })} />
            )}
            {modal.type === 'followup' && (
                <AddFollowUp open influencerId={modal.influencer.id} onClose={() => setModal({ type: null })} />
            )}
            {modal.type === 'note' && (
                <AddNote open notableType="influencer" notableId={modal.influencer.id} onClose={() => setModal({ type: null })} />
            )}
            {modal.type === 'campaign' && (
                <AddToCampaign open influencer={modal.influencer} onClose={() => setModal({ type: null })} />
            )}
        </AuthenticatedLayout>
    );
}
