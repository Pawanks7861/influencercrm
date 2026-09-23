import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ClientForm from '@/Components/Crm/ClientForm';

export default function Create() {
    return (
        <AuthenticatedLayout title="Add client">
            <Head title="Add client" />
            <ClientForm />
        </AuthenticatedLayout>
    );
}
