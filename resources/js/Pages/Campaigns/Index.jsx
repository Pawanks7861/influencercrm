import { Head, Link, router, usePage } from '@inertiajs/react';
import { Megaphone, Plus } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import DataTable from '@/Components/Crm/DataTable';
import SearchInput from '@/Components/Crm/SearchInput';
import EmptyState from '@/Components/Crm/EmptyState';
import StatusBadge from '@/Components/Crm/StatusBadge';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import ActionMenu from '@/Components/Crm/ActionMenu';
import ConfirmDialog from '@/Components/Crm/ConfirmDialog';
import { formatDate } from '@/lib/format';
import { useState } from 'react';

export default function CampaignsIndex({ campaigns, filters = {}, clients = [] }) {
    const { crm } = usePage().props;
    const [deleteTarget, setDeleteTarget] = useState(null);

    const applyFilters = (next = {}) => {
        router.get(route('campaigns.index'), { ...filters, ...next, page: 1 }, { preserveState: true, preserveScroll: true });
    };

    const columns = [
        {
            key: 'campaign_name',
            label: 'Campaign',
            sortKey: 'campaign_name',
            render: (row) => (
                <Link href={route('campaigns.show', row.id)} className="font-medium text-ink hover:text-accent">
                    {row.campaign_name}
                </Link>
            ),
        },
        {
            key: 'client',
            label: 'Client',
            render: (row) => row.client?.name || '—',
        },
        {
            key: 'brand_name',
            label: 'Brand',
            render: (row) => row.brand_name || '—',
        },
        {
            key: 'status',
            label: 'Status',
            sortKey: 'status',
            render: (row) => <StatusBadge status={row.status} labels={crm.campaign_statuses} />,
        },
        {
            key: 'influencers',
            label: 'Influencers',
            render: (row) => row.totals?.influencer_count ?? row.campaign_influencers?.length ?? 0,
        },
        {
            key: 'value',
            label: 'Value',
            render: (row) => <MoneyDisplay amount={row.totals?.total_final_amount} />,
        },
        {
            key: 'deadline',
            label: 'Deadline',
            sortKey: 'deadline',
            render: (row) => formatDate(row.deadline),
        },
        {
            key: 'actions',
            label: '',
            cellClassName: 'text-right',
            render: (row) => (
                <ActionMenu
                    items={[
                        { label: 'View', onClick: () => router.visit(route('campaigns.show', row.id)) },
                        { label: 'Edit', onClick: () => router.visit(route('campaigns.edit', row.id)) },
                        { label: 'Archive', danger: true, onClick: () => setDeleteTarget(row) },
                    ]}
                />
            ),
        },
    ];

    return (
        <AuthenticatedLayout title="Campaigns">
            <Head title="Campaigns" />
            <PageHeader
                title="Campaigns"
                description="Plan and track brand collaborations"
                actions={
                    <Link href={route('campaigns.create')} className="crm-btn-primary">
                        <Plus className="h-4 w-4" /> Create campaign
                    </Link>
                }
            />

            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <SearchInput
                    className="sm:max-w-sm"
                    value={filters.search || ''}
                    onChange={(search) => applyFilters({ search })}
                    placeholder="Search campaigns…"
                />
                <select
                    className="crm-input sm:w-48"
                    value={filters.status || ''}
                    onChange={(e) => applyFilters({ status: e.target.value })}
                >
                    <option value="">All statuses</option>
                    {Object.entries(crm.campaign_statuses || {}).map(([value, label]) => (
                        <option key={value} value={value}>{label}</option>
                    ))}
                </select>
                <select
                    className="crm-input sm:w-56"
                    value={filters.client_id || ''}
                    onChange={(e) => applyFilters({ client_id: e.target.value })}
                >
                    <option value="">All clients</option>
                    {clients.map((c) => (
                        <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                </select>
            </div>

            <DataTable
                columns={columns}
                rows={campaigns.data || []}
                paginator={campaigns}
                sort={filters.sort || 'created_at'}
                direction={filters.direction || 'desc'}
                onSort={(sortKey) => {
                    const direction = filters.sort === sortKey && filters.direction === 'asc' ? 'desc' : 'asc';
                    applyFilters({ sort: sortKey, direction });
                }}
                empty={
                    <EmptyState
                        icon={Megaphone}
                        title="No campaigns yet"
                        description="Create your first campaign to get started."
                        action={
                            <Link href={route('campaigns.create')} className="crm-btn-primary">
                                Create campaign
                            </Link>
                        }
                    />
                }
            />

            <ConfirmDialog
                show={Boolean(deleteTarget)}
                onClose={() => setDeleteTarget(null)}
                title="Archive campaign?"
                message={`Archive ${deleteTarget?.campaign_name}?`}
                confirmLabel="Archive"
                danger
                onConfirm={() => {
                    router.delete(route('campaigns.destroy', deleteTarget.id), {
                        onFinish: () => setDeleteTarget(null),
                    });
                }}
            />
        </AuthenticatedLayout>
    );
}
