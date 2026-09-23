import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Plus } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import SearchInput from '@/Components/Crm/SearchInput';
import StatusBadge from '@/Components/Crm/StatusBadge';
import DataTable from '@/Components/Crm/DataTable';
import EmptyState from '@/Components/Crm/EmptyState';
import ActionMenu from '@/Components/Crm/ActionMenu';
import AddFollowUp from '@/Components/Crm/Modals/AddFollowUp';
import Modal from '@/Components/Modal';
import { formatDate, cn } from '@/lib/format';

export default function FollowUpsIndex({ followUps, filters = {}, counts = {}, users = [], influencers = [], clients = [] }) {
    const [createOpen, setCreateOpen] = useState(false);
    const [rescheduleTarget, setRescheduleTarget] = useState(null);
    const { data, setData, post, processing, reset } = useForm({
        follow_up_date: '',
        follow_up_time: '10:00',
    });

    const tabs = [
        { key: '', label: 'All', count: null },
        { key: 'today', label: 'Today', count: counts.today },
        { key: 'upcoming', label: 'Upcoming', count: null },
        { key: 'overdue', label: 'Overdue', count: counts.overdue },
        { key: 'pending', label: 'Pending', count: null, status: true },
    ];

    const entityTabs = [
        { key: '', label: 'All entities' },
        { key: 'influencer', label: 'Influencer' },
        { key: 'client', label: 'Client' },
        { key: 'campaign', label: 'Campaign' },
    ];

    const activeFilter = filters.filter || (filters.status === 'pending' ? 'pending' : '');

    const applyFilters = (next = {}) => {
        router.get(route('follow-ups.index'), { ...filters, ...next, page: 1 }, { preserveState: true, preserveScroll: true });
    };

    const columns = [
        {
            key: 'follow_up_date',
            label: 'When',
            render: (row) => (
                <span>
                    {formatDate(row.follow_up_date)}
                    {row.follow_up_time ? ` · ${String(row.follow_up_time).slice(0, 5)}` : ''}
                </span>
            ),
        },
        {
            key: 'related_to',
            label: 'Related To',
            render: (row) => {
                if (row.influencer) {
                    return (
                        <Link href={route('influencers.show', row.influencer.id)} className="font-medium text-accent hover:text-accent-hover">
                            Influencer: {row.influencer.name}
                        </Link>
                    );
                }
                if (row.client) {
                    return (
                        <Link href={route('clients.show', row.client.id)} className="font-medium text-accent hover:text-accent-hover">
                            Client: {row.client.company_name || row.client.name}
                        </Link>
                    );
                }
                if (row.campaign) {
                    return (
                        <Link href={route('campaigns.show', row.campaign.id)} className="font-medium text-accent hover:text-accent-hover">
                            Campaign: {row.campaign.campaign_name}
                        </Link>
                    );
                }
                return row.related_to || '—';
            },
        },
        {
            key: 'campaign',
            label: 'Campaign',
            render: (row) =>
                row.campaign && (row.influencer || row.client) ? (
                    <Link href={route('campaigns.show', row.campaign.id)} className="text-ink-soft hover:text-accent">
                        {row.campaign.campaign_name}
                    </Link>
                ) : (
                    '—'
                ),
        },
        {
            key: 'assignee',
            label: 'Assignee',
            render: (row) => row.assignee?.name || '—',
        },
        {
            key: 'status',
            label: 'Status',
            render: (row) => <StatusBadge status={row.status} />,
        },
        {
            key: 'note',
            label: 'Note',
            render: (row) => <span className="line-clamp-2 max-w-xs text-ink-soft">{row.note || '—'}</span>,
        },
        {
            key: 'actions',
            label: '',
            cellClassName: 'text-right',
            render: (row) => {
                const pending = (row.status?.value || row.status) === 'pending';
                return (
                    <ActionMenu
                        items={[
                            pending && {
                                label: 'Mark complete',
                                onClick: () => router.post(route('follow-ups.complete', row.id), {}, { preserveScroll: true }),
                            },
                            pending && {
                                label: 'Reschedule',
                                onClick: () => {
                                    setRescheduleTarget(row);
                                    setData({
                                        follow_up_date: String(row.follow_up_date).slice(0, 10),
                                        follow_up_time: row.follow_up_time ? String(row.follow_up_time).slice(0, 5) : '10:00',
                                    });
                                },
                            },
                            pending && {
                                label: 'Cancel',
                                danger: true,
                                onClick: () => router.post(route('follow-ups.cancel', row.id), {}, { preserveScroll: true }),
                            },
                            row.influencer && {
                                label: 'Open influencer',
                                onClick: () => router.visit(route('influencers.show', row.influencer.id)),
                            },
                            row.client && {
                                label: 'Open client',
                                onClick: () => router.visit(route('clients.show', row.client.id)),
                            },
                        ].filter(Boolean)}
                    />
                );
            },
        },
    ];

    return (
        <AuthenticatedLayout title="Follow-ups">
            <Head title="Follow-ups" />
            <PageHeader
                title="Follow-ups"
                description="Stay on top of outreach and reminders"
                actions={
                    <button type="button" className="crm-btn-primary" onClick={() => setCreateOpen(true)}>
                        <Plus className="h-4 w-4" /> Add follow-up
                    </button>
                }
            />

            <div className="mb-4 flex flex-wrap gap-2">
                {tabs.map((tab) => {
                    const active = activeFilter === tab.key;
                    return (
                        <button
                            key={tab.key || 'all'}
                            type="button"
                            onClick={() => {
                                if (tab.status) {
                                    applyFilters({ filter: '', status: 'pending' });
                                } else if (tab.key) {
                                    applyFilters({ filter: tab.key, status: '' });
                                } else {
                                    applyFilters({ filter: '', status: '' });
                                }
                            }}
                            className={cn(
                                'rounded-lg px-3 py-1.5 text-sm font-medium border',
                                active ? 'border-ink bg-ink text-white' : 'border-surface-border bg-white text-ink-soft hover:bg-surface'
                            )}
                        >
                            {tab.label}
                            {tab.count != null && <span className="ml-1.5 opacity-70">{tab.count}</span>}
                        </button>
                    );
                })}
            </div>

            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <SearchInput
                    className="sm:max-w-sm"
                    value={filters.search || ''}
                    onChange={(search) => applyFilters({ search })}
                    placeholder="Search influencer, client, campaign…"
                />
                <select
                    className="crm-input sm:w-48"
                    value={filters.entity || ''}
                    onChange={(e) => applyFilters({ entity: e.target.value })}
                >
                    {entityTabs.map((tab) => (
                        <option key={tab.key || 'all'} value={tab.key}>{tab.label}</option>
                    ))}
                </select>
                <select
                    className="crm-input sm:w-56"
                    value={filters.assigned_to || ''}
                    onChange={(e) => applyFilters({ assigned_to: e.target.value })}
                >
                    <option value="">All assignees</option>
                    {users.map((u) => (
                        <option key={u.id} value={u.id}>{u.name}</option>
                    ))}
                </select>
            </div>

            <DataTable
                columns={columns}
                rows={followUps.data || []}
                paginator={followUps}
                empty={<EmptyState title="No follow-ups" description="You're all caught up." />}
            />

            <AddFollowUp
                open={createOpen}
                onClose={() => setCreateOpen(false)}
                influencers={influencers}
                clients={clients}
                users={users}
            />

            <Modal show={Boolean(rescheduleTarget)} onClose={() => setRescheduleTarget(null)} maxWidth="sm">
                <form
                    className="space-y-4 p-5"
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(route('follow-ups.reschedule', rescheduleTarget.id), {
                            preserveScroll: true,
                            onSuccess: () => {
                                setRescheduleTarget(null);
                                reset();
                            },
                        });
                    }}
                >
                    <h3 className="font-display text-lg font-semibold text-ink">Reschedule follow-up</h3>
                    <div>
                        <label className="crm-label">Date</label>
                        <input type="date" className="crm-input" value={data.follow_up_date} onChange={(e) => setData('follow_up_date', e.target.value)} required />
                    </div>
                    <div>
                        <label className="crm-label">Time</label>
                        <input type="time" className="crm-input" value={data.follow_up_time} onChange={(e) => setData('follow_up_time', e.target.value)} />
                    </div>
                    <div className="flex justify-end gap-2">
                        <button type="button" className="crm-btn-secondary" onClick={() => setRescheduleTarget(null)}>Cancel</button>
                        <button type="submit" className="crm-btn-primary" disabled={processing}>Save</button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
