import { Head, Link, usePage } from '@inertiajs/react';
import ClientPortalLayout from '@/Layouts/ClientPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';

export default function Index({ items = [] }) {
    const { crm } = usePage().props;

    return (
        <ClientPortalLayout title="Approvals">
            <Head title="Approvals" />
            <PageHeader title="Approvals" description="Pending shortlist items awaiting your response" />

            <div className="space-y-3">
                {items.map((item) => (
                    <div key={item.id} className="crm-card flex flex-wrap items-center justify-between gap-3 p-4">
                        <div>
                            {item.display_name && <p className="font-medium text-ink">{item.display_name}</p>}
                            {item.show_price && <p className="text-sm"><MoneyDisplay amount={item.client_price} /></p>}
                        </div>
                        <Link href={route('client.shortlists.index')} className="crm-btn-secondary">
                            Review shortlists
                        </Link>
                    </div>
                ))}
                {!items.length && (
                    <div className="crm-card px-5 py-10 text-center text-sm text-ink-muted">
                        No pending approvals.
                    </div>
                )}
            </div>
        </ClientPortalLayout>
    );
}
