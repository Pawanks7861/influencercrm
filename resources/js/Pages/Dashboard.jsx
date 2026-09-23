import { Head, Link, router } from '@inertiajs/react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Line,
    LineChart,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import {
    AlertTriangle,
    ClipboardList,
    Megaphone,
    Plus,
    Users,
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatCard from '@/Components/Crm/StatCard';
import ActivityTimeline from '@/Components/Crm/ActivityTimeline';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';

const RANGES = [
    { value: 'this_month', label: 'This month' },
    { value: 'last_month', label: 'Last month' },
    { value: 'this_quarter', label: 'This quarter' },
    { value: 'this_year', label: 'This year' },
    { value: 'custom', label: 'Custom' },
];

const CHART_COLORS = ['#0D9488', '#1A2332', '#14B8A6', '#5C6B7A', '#0F766E', '#94A3B8'];

export default function Dashboard({ kpis, charts, recent_activity, date_range }) {
    const { crm } = usePage().props;
    const [range, setRange] = useState(date_range?.range || 'this_month');
    const [from, setFrom] = useState(date_range?.from || '');
    const [to, setTo] = useState(date_range?.to || '');

    const applyRange = (nextRange, nextFrom = from, nextTo = to) => {
        setRange(nextRange);
        router.get(
            route('dashboard'),
            {
                range: nextRange,
                ...(nextRange === 'custom' ? { from: nextFrom, to: nextTo } : {}),
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    return (
        <AuthenticatedLayout title="Dashboard">
            <Head title="Dashboard" />

            <PageHeader
                title="Dashboard"
                description="Overview of influencer collaborations and revenue"
                actions={
                    <div className="flex flex-wrap items-center gap-2">
                        <select
                            className="crm-input !w-auto"
                            value={range}
                            onChange={(e) => {
                                if (e.target.value === 'custom') {
                                    setRange('custom');
                                } else {
                                    applyRange(e.target.value);
                                }
                            }}
                        >
                            {RANGES.map((r) => (
                                <option key={r.value} value={r.value}>
                                    {r.label}
                                </option>
                            ))}
                        </select>
                        {range === 'custom' && (
                            <>
                                <input type="date" className="crm-input !w-auto" value={from} onChange={(e) => setFrom(e.target.value)} />
                                <input type="date" className="crm-input !w-auto" value={to} onChange={(e) => setTo(e.target.value)} />
                                <button type="button" className="crm-btn-primary" onClick={() => applyRange('custom', from, to)}>
                                    Apply
                                </button>
                            </>
                        )}
                    </div>
                }
            />

            {kpis.overdue_follow_ups > 0 && (
                <Link
                    href={route('follow-ups.index', { filter: 'overdue' })}
                    className="mb-6 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 hover:bg-red-100"
                >
                    <AlertTriangle className="h-4 w-4 shrink-0" />
                    <span>
                        <strong>{kpis.overdue_follow_ups}</strong> overdue follow-up{kpis.overdue_follow_ups === 1 ? '' : 's'} need attention
                    </span>
                </Link>
            )}

            <div className="mb-6 flex flex-wrap gap-2">
                <Link href={route('influencers.create')} className="crm-btn-primary">
                    <Plus className="h-4 w-4" /> Add Influencer
                </Link>
                <Link href={route('campaigns.create')} className="crm-btn-secondary">
                    <Megaphone className="h-4 w-4" /> Create Campaign
                </Link>
                <Link href={route('follow-ups.index')} className="crm-btn-secondary">
                    <ClipboardList className="h-4 w-4" /> Add Follow-up
                </Link>
            </div>

            <section className="mb-6">
                <h2 className="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">Influencers</h2>
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard label="Total" value={kpis.total_influencers} icon={Users} />
                    <StatCard label="Premium" value={kpis.premium_influencers} />
                    <StatCard label="Medium" value={kpis.medium_influencers} />
                    <StatCard label="Low" value={kpis.low_influencers} />
                </div>
            </section>

            <section className="mb-6">
                <h2 className="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">Collaborations</h2>
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard label="Active" value={kpis.active_collaborations} tone="accent" />
                    <StatCard label="Pending" value={kpis.pending_collaborations} tone="warning" />
                    <StatCard label="Completed" value={kpis.completed_collaborations} />
                    <StatCard label="Cancelled" value={kpis.cancelled_collaborations} />
                </div>
            </section>

            <section className="mb-6">
                <h2 className="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">Client portal</h2>
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard label="New requirements" value={kpis.new_client_requirements ?? 0} />
                    <StatCard label="Under review" value={kpis.requirements_under_review ?? 0} />
                    <StatCard label="Shortlists awaiting response" value={kpis.shortlists_awaiting_client_response ?? 0} tone="warning" />
                    <StatCard label="Selections awaiting campaign" value={kpis.client_selections_awaiting_campaign ?? 0} tone="accent" />
                </div>
            </section>

            <section className="mb-6">
                <h2 className="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">Financials</h2>
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <StatCard label="Influencer cost" value={<MoneyDisplay amount={kpis.total_influencer_cost} />} />
                    <StatCard label="Additional cost" value={<MoneyDisplay amount={kpis.total_additional_cost} />} />
                    <StatCard label="Grovera fee" value={<MoneyDisplay amount={kpis.total_grovera_fee} />} />
                    <StatCard label="Campaign value" value={<MoneyDisplay amount={kpis.total_final_amount} />} />
                    <StatCard label="Pending payments" value={<MoneyDisplay amount={kpis.pending_payments} />} tone="warning" />
                    <StatCard label="Completed payments" value={<MoneyDisplay amount={kpis.completed_payments} />} />
                </div>
            </section>

            <div className="mb-6 grid gap-4 lg:grid-cols-2">
                <div className="crm-card p-4">
                    <h3 className="mb-4 font-display text-sm font-semibold text-ink">Influencers by location</h3>
                    <div className="h-64">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={charts.influencers_by_location || []}>
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
                    <h3 className="mb-4 font-display text-sm font-semibold text-ink">Influencers by type</h3>
                    <div className="h-64">
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie data={charts.influencers_by_type || []} dataKey="count" nameKey="label" innerRadius={50} outerRadius={80}>
                                    {(charts.influencers_by_type || []).map((_, i) => (
                                        <Cell key={i} fill={CHART_COLORS[i % CHART_COLORS.length]} />
                                    ))}
                                </Pie>
                                <Tooltip />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                <div className="crm-card p-4">
                    <h3 className="mb-4 font-display text-sm font-semibold text-ink">Collaboration status</h3>
                    <div className="h-64">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={(charts.collaboration_status || []).filter((d) => d.count > 0)} layout="vertical" margin={{ left: 20 }}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#E5E8EC" />
                                <XAxis type="number" allowDecimals={false} tick={{ fontSize: 11 }} />
                                <YAxis type="category" dataKey="label" width={110} tick={{ fontSize: 10 }} />
                                <Tooltip />
                                <Bar dataKey="count" fill="#1A2332" radius={[0, 4, 4, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                <div className="crm-card p-4">
                    <h3 className="mb-4 font-display text-sm font-semibold text-ink">Monthly collaboration value</h3>
                    <div className="h-64">
                        <ResponsiveContainer width="100%" height="100%">
                            <LineChart data={charts.monthly_collaboration_value || []}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#E5E8EC" />
                                <XAxis dataKey="month" tick={{ fontSize: 11 }} />
                                <YAxis tick={{ fontSize: 11 }} />
                                <Tooltip />
                                <Line type="monotone" dataKey="total_value" stroke="#0D9488" strokeWidth={2} dot={false} />
                                <Line type="monotone" dataKey="grovera_revenue" stroke="#1A2332" strokeWidth={2} dot={false} />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>

            <div className="crm-card p-5">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="font-display text-sm font-semibold text-ink">Recent activity</h3>
                    <div className="flex gap-3 text-xs text-ink-muted">
                        <span>Today follow-ups: {kpis.today_follow_ups}</span>
                        <span>Overdue: {kpis.overdue_follow_ups}</span>
                    </div>
                </div>
                <ActivityTimeline activities={recent_activity || []} activityTypes={crm?.activity_types || {}} />
            </div>
        </AuthenticatedLayout>
    );
}
