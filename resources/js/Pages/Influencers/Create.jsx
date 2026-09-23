import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InfluencerForm from '@/Components/Crm/InfluencerForm';

export default function Create() {
    return (
        <AuthenticatedLayout title="Add influencer">
            <Head title="Add influencer" />
            <InfluencerForm />
        </AuthenticatedLayout>
    );
}
