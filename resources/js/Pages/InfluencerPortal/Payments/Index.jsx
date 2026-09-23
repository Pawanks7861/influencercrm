import { Head, usePage } from '@inertiajs/react';
import InfluencerPortalLayout from '@/Layouts/InfluencerPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import PaymentStatusBadge from '@/Components/Crm/PaymentStatusBadge';
import { formatDate, labelFromMap } from '@/lib/format';

export default function PaymentsIndex({ payments }) {
    const { crm } = usePage().props;
    const rows = payments?.data || [];

    return (
        <InfluencerPortalLayout title="My Payments">
            <Head title="My Payments" />
            <PageHeader title="My Payments" description="Your fees and payment status per campaign" />

            <div className="crm-card overflow-hidden">
                <table className="min-w-full text-sm">
                    <thead className="bg-surface text-xs uppercase text-ink-muted">
                        <tr>
                            <th className="px-4 py-3 text-left">Campaign</th>
                            <th className="px-4 py-3 text-right">Your fee</th>
                            <th className="px-4 py-3 text-right">Paid</th>
                            <th className="px-4 py-3 text-right">Pending</th>
                            <th className="px-4 py-3 text-left">Status</th>
                            <th className="px-4 py-3 text-left">Latest payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.id} className="border-t border-surface-border">
                                <td className="px-4 py-3">
                                    <p className="font-medium text-ink">{row.campaign_name}</p>
                                    <p className="text-xs text-ink-muted">{row.brand_name || '—'}</p>
                                    {(row.payments || []).length > 0 && (
                                        <ul className="mt-2 space-y-1 text-xs text-ink-muted">
                                            {row.payments.map((p) => (
                                                <li key={p.id}>
                                                    {formatDate(p.payment_date)} · <MoneyDisplay amount={p.amount} />
                                                    {p.payment_method ? ` · ${labelFromMap(crm.payment_methods, p.payment_method)}` : ''}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </td>
                                <td className="px-4 py-3 text-right">
                                    <MoneyDisplay amount={row.influencer_cost} />
                                </td>
                                <td className="px-4 py-3 text-right">
                                    <MoneyDisplay amount={row.amount_paid} />
                                </td>
                                <td className="px-4 py-3 text-right">
                                    <MoneyDisplay amount={row.amount_pending} />
                                </td>
                                <td className="px-4 py-3">
                                    <PaymentStatusBadge status={row.payment_status} />
                                </td>
                                <td className="px-4 py-3">{formatDate(row.latest_payment_date)}</td>
                            </tr>
                        ))}
                        {!rows.length && (
                            <tr>
                                <td colSpan={6} className="px-4 py-10 text-center text-ink-muted">
                                    No payment records yet
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </InfluencerPortalLayout>
    );
}
