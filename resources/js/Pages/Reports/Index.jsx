import { Head, router, usePage } from '@inertiajs/react';
import { Download } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatCard from '@/Components/Crm/StatCard';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import InfluencerTypeBadge from '@/Components/Crm/InfluencerTypeBadge';
import DataTable from '@/Components/Crm/DataTable';

export default function ReportsIndex({
    summary,
    location_wise,
    type_wise,
    monthly_revenue,
    performance,
    filters = {},
    clients = [],
    campaigns = [],
}) {
    const { crm } = usePage().props;

    const applyFilters = (next = {}) => {
        router.get(route('reports.index'), { ...filters, ...next }, { preserveState: true, preserveScroll: true });
    };

    const columns = [
        {
            key: 'influencer',
            label: 'Influencer',
            render: (row) => (
                <div>
                    <p className="font-medium text-ink">{row.influencer?.name}</p>
                    <p className="text-xs text-ink-muted">@{row.influencer?.instagram_username}</p>
                </div>
            ),
        },
        {
            key: 'type',
            label: 'Type',
            render: (row) => <InfluencerTypeBadge type={row.influencer?.influencer_type} labels={crm.influencer_types} />,
        },
        { key: 'campaigns_count', label: 'Campaigns', render: (row) => row.campaigns_count },
        { key: 'completed_count', label: 'Completed', render: (row) => row.completed_count },
        { key: 'cancelled_count', label: 'Cancelled', render: (row) => row.cancelled_count },
        { key: 'total_spend', label: 'Spend', render: (row) => <MoneyDisplay amount={row.total_spend} /> },
        { key: 'average_cost', label: 'Avg cost', render: (row) => <MoneyDisplay amount={row.average_cost} /> },
    ];

    return (
        <AuthenticatedLayout title="Reports">
            <Head title="Reports" />
            <PageHeader
                title="Reports"
                description="Performance and financial overview"
                actions={
                    <a href={route('exports.campaigns', filters)} className="crm-btn-secondary">
                        <Download className="h-4 w-4" /> Export campaigns
                    </a>
                }
            />

            <div className="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4">
                <div className="sm:col-span-2 lg:col-span-4 crm-card p-4">
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                        <div>
                            <label className="crm-label">From</label>
                            <input type="date" className="crm-input" value={filters.from || ''} onChange={(e) => applyFilters({ from: e.target.value })} />
                        </div>
                        <div>
                            <label className="crm-label">To</label>
                            <input type="date" className="crm-input" value={filters.to || ''} onChange={(e) => applyFilters({ to: e.target.value })} />
                        </div>
                        <div>
                            <label className="crm-label">Client</label>
                            <select className="crm-input" value={filters.client_id || ''} onChange={(e) => applyFilters({ client_id: e.target.value })}>
                                <option value="">All</option>
                                {clients.map((c) => (
                                    <option key={c.id} value={c.id}>{c.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="crm-label">Campaign</label>
                            <select className="crm-input" value={filters.campaign_id || ''} onChange={(e) => applyFilters({ campaign_id: e.target.value })}>
                                <option value="">All</option>
                                {campaigns.map((c) => (
                                    <option key={c.id} value={c.id}>{c.campaign_name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="crm-label">Type</label>
                            <select className="crm-input" value={filters.influencer_type || ''} onChange={(e) => applyFilters({ influencer_type: e.target.value })}>
                                <option value="">All</option>
                                {Object.entries(crm.influencer_types || {}).map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="crm-label">Status</label>
                            <select className="crm-input" value={filters.status || ''} onChange={(e) => applyFilters({ status: e.target.value })}>
                                <option value="">All</option>
                                {Object.entries(crm.campaign_statuses || {}).map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div className="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Campaigns" value={summary.total_campaigns} />
                <StatCard label="Influencers used" value={summary.total_influencers_used} />
                <StatCard label="Influencer spend" value={<MoneyDisplay amount={summary.total_influencer_spend} />} />
                <StatCard label="Additional cost" value={<MoneyDisplay amount={summary.total_additional_cost} />} />
                <StatCard label="Grovera revenue" value={<MoneyDisplay amount={summary.total_grovera_revenue} />} tone="accent" />
                <StatCard label="Campaign value" value={<MoneyDisplay amount={summary.total_campaign_value} />} />
                <StatCard label="Margin" value={<MoneyDisplay amount={summary.total_margin} />} tone="accent" />
                <StatCard label="Completed payments" value={<MoneyDisplay amount={summary.completed_payments} />} />
                <StatCard label="Pending payments" value={<MoneyDisplay amount={summary.pending_payments} />} tone="warning" />
                <StatCard label="Completed campaigns" value={summary.completed_campaigns} />
            </div>

            <div className="mb-6 grid gap-4 lg:grid-cols-2">
                <div className="crm-card p-4">
                    <h3 className="mb-4 font-display text-sm font-semibold text-ink">Location wise</h3>
                    <div className="h-64">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={location_wise || []}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#E5E8EC" />
                                <XAxis dataKey="location" tick={{ fontSize: 11 }} />
                                <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                <Tooltip />
                                <Bar dataKey="count" fill="#0D9488" radius={[4, 4, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>
                <div className="crm-card p-4">
                    <h3 className="mb-4 font-display text-sm font-semibold text-ink">Monthly revenue</h3>
                    <div className="h-64">
                        <ResponsiveContainer width="100%" height="100%">
                            <LineChart data={monthly_revenue || []}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#E5E8EC" />
                                <XAxis dataKey="month" tick={{ fontSize: 11 }} />
                                <YAxis tick={{ fontSize: 11 }} />
                                <Tooltip />
                                <Line type="monotone" dataKey="campaign_value" stroke="#0D9488" strokeWidth={2} dot={false} />
                                <Line type="monotone" dataKey="grovera_revenue" stroke="#1A2332" strokeWidth={2} dot={false} />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                {(type_wise || []).map((t) => (
                    <div key={t.type} className="rounded-lg border border-surface-border bg-white px-3 py-2 text-sm">
                        <span className="text-ink-muted">{t.label}</span>
                        <span className="ml-2 font-semibold text-ink">{t.count}</span>
                    </div>
                ))}
            </div>

            <h3 className="mb-3 font-display text-sm font-semibold text-ink">Influencer performance</h3>
            <DataTable columns={columns} rows={performance || []} empty={<p className="text-center text-ink-muted">No performance data</p>} />
        </AuthenticatedLayout>
    );
}
