import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import CampaignForm from '@/Components/Crm/CampaignForm';

export default function Edit({ campaign, clients, influencers }) {
    return (
        <AuthenticatedLayout title="Edit campaign">
            <Head title={`Edit ${campaign.campaign_name}`} />
            <CampaignForm campaign={campaign} clients={clients} influencers={influencers} />
        </AuthenticatedLayout>
    );
}
