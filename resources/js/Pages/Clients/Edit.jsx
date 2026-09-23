import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ClientForm from '@/Components/Crm/ClientForm';

export default function Edit({ client }) {
    return (
        <AuthenticatedLayout title={`Edit ${client.company_name || client.name}`}>
            <Head title={`Edit ${client.company_name || client.name}`} />
            <ClientForm client={client} />
        </AuthenticatedLayout>
    );
}
