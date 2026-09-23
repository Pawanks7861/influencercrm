import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import CampaignForm from '@/Components/Crm/CampaignForm';

export default function Create({ clients, influencers, prefillClientId = null }) {
    return (
        <AuthenticatedLayout title="Create campaign">
            <Head title="Create campaign" />
            <CampaignForm clients={clients} influencers={influencers} prefillClientId={prefillClientId} />
        </AuthenticatedLayout>
    );
}
