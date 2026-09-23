import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download, Plus } from 'lucide-react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import DataTable from '@/Components/Crm/DataTable';
import SearchInput from '@/Components/Crm/SearchInput';
import EmptyState from '@/Components/Crm/EmptyState';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import ActionMenu from '@/Components/Crm/ActionMenu';
import ConfirmDialog from '@/Components/Crm/ConfirmDialog';
import RecordPayment from '@/Components/Crm/Modals/RecordPayment';
import { formatDate, labelFromMap } from '@/lib/format';

export default function PaymentsIndex({ payments, filters = {} }) {
    const { crm } = usePage().props;
    const [recordOpen, setRecordOpen] = useState(false);
    const [voidTarget, setVoidTarget] = useState(null);
    const [campaignInfluencerId, setCampaignInfluencerId] = useState('');

    const applyFilters = (next = {}) => {
        router.get(route('payments.index'), { ...filters, ...next, page: 1 }, { preserveState: true, preserveScroll: true });
    };

    const columns = [
        {
            key: 'payment_date',
            label: 'Date',
            render: (row) => formatDate(row.payment_date),
        },
        {
            key: 'influencer',
            label: 'Influencer',
            render: (row) =>
                row.influencer ? (
                    <Link href={route('influencers.show', row.influencer.id)} className="font-medium text-accent hover:text-accent-hover">
                        {row.influencer.name}
                    </Link>
                ) : (
                    '—'
                ),
        },
        {
            key: 'campaign',
            label: 'Campaign',
            render: (row) =>
                row.campaign ? (
                    <Link href={route('campaigns.show', row.campaign.id)} className="text-ink-soft hover:text-accent">
                        {row.campaign.campaign_name}
                    </Link>
                ) : (
                    '—'
                ),
        },
        {
            key: 'amount',
            label: 'Amount',
            render: (row) => <MoneyDisplay amount={row.amount} />,
        },
        {
            key: 'payment_method',
            label: 'Method',
            render: (row) => labelFromMap(crm.payment_methods, row.payment_method),
        },
        {
            key: 'transaction_reference',
            label: 'Reference',
            render: (row) => row.transaction_reference || '—',
        },
        {
            key: 'actions',
            label: '',
            cellClassName: 'text-right',
            render: (row) => (
                <ActionMenu
                    items={[
                        {
                            label: 'Void payment',
                            danger: true,
                            onClick: () => setVoidTarget(row),
                        },
                    ]}
                />
            ),
        },
    ];

    return (
        <AuthenticatedLayout title="Payments">
            <Head title="Payments" />
            <PageHeader
                title="Payments"
                description="Track influencer payouts"
                actions={
                    <>
                        <a href={route('exports.payments', filters)} className="crm-btn-secondary">
                            <Download className="h-4 w-4" /> Export
                        </a>
                        <button
                            type="button"
                            className="crm-btn-primary"
                            onClick={() => {
                                setCampaignInfluencerId('');
                                setRecordOpen(true);
                            }}
                        >
                            <Plus className="h-4 w-4" /> Record payment
                        </button>
                    </>
                }
            />

            <div className="mb-4 flex flex-col gap-3 sm:flex-row">
                <SearchInput
                    className="sm:max-w-sm"
                    value={filters.search || ''}
                    onChange={(search) => applyFilters({ search })}
                    placeholder="Search payments…"
                />
                <input type="date" className="crm-input sm:w-auto" value={filters.from || ''} onChange={(e) => applyFilters({ from: e.target.value })} />
                <input type="date" className="crm-input sm:w-auto" value={filters.to || ''} onChange={(e) => applyFilters({ to: e.target.value })} />
            </div>

            <DataTable
                columns={columns}
                rows={payments.data || []}
                paginator={payments}
                empty={<EmptyState title="No payments" description="Record a payment from a campaign influencer row or here." />}
            />

            {recordOpen && (
                <div>
                    {!campaignInfluencerId ? (
                        <RecordPaymentPrompt
                            onClose={() => setRecordOpen(false)}
                            onContinue={(id) => setCampaignInfluencerId(id)}
                        />
                    ) : (
                        <RecordPayment
                            open
                            campaignInfluencerId={campaignInfluencerId}
                            onClose={() => {
                                setRecordOpen(false);
                                setCampaignInfluencerId('');
                            }}
                        />
                    )}
                </div>
            )}

            <ConfirmDialog
                show={Boolean(voidTarget)}
                onClose={() => setVoidTarget(null)}
                title="Void payment?"
                message="This will void the selected payment record."
                confirmLabel="Void"
                danger
                onConfirm={() => {
                    router.delete(route('payments.destroy', voidTarget.id), {
                        onFinish: () => setVoidTarget(null),
                    });
                }}
            />
        </AuthenticatedLayout>
    );
}

function RecordPaymentPrompt({ onClose, onContinue }) {
    const [id, setId] = useState('');
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 px-4">
            <div className="w-full max-w-md rounded-xl bg-white p-5 shadow-soft">
                <h3 className="font-display text-lg font-semibold text-ink">Record payment</h3>
                <p className="mt-1 text-sm text-ink-muted">
                    Enter the campaign influencer ID, or open a campaign and use Record payment from the influencers table.
                </p>
                <input
                    className="crm-input mt-4"
                    placeholder="Campaign influencer ID"
                    value={id}
                    onChange={(e) => setId(e.target.value)}
                />
                <div className="mt-4 flex justify-end gap-2">
                    <button type="button" className="crm-btn-secondary" onClick={onClose}>Cancel</button>
                    <button type="button" className="crm-btn-primary" disabled={!id} onClick={() => onContinue(id)}>Continue</button>
                </div>
            </div>
        </div>
    );
}
