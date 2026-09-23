import { Head } from '@inertiajs/react';
import ClientPortalLayout from '@/Layouts/ClientPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';

export default function Edit({ client }) {
    return (
        <ClientPortalLayout title="My Profile">
            <Head title="My Profile" />
            <PageHeader title="My Profile" description="Your company contact details" />
            <div className="crm-card max-w-xl space-y-3 p-5 text-sm">
                <div>
                    <p className="text-ink-muted">Company</p>
                    <p className="font-medium">{client.company_name || client.name}</p>
                </div>
                <div>
                    <p className="text-ink-muted">Contact person</p>
                    <p className="font-medium">{client.contact_person || '—'}</p>
                </div>
                <div>
                    <p className="text-ink-muted">Email</p>
                    <p className="font-medium">{client.email || '—'}</p>
                </div>
                <div>
                    <p className="text-ink-muted">Mobile</p>
                    <p className="font-medium">{client.mobile || '—'}</p>
                </div>
            </div>
        </ClientPortalLayout>
    );
}
