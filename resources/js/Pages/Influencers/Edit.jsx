import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InfluencerForm from '@/Components/Crm/InfluencerForm';

export default function Edit({ influencer }) {
    return (
        <AuthenticatedLayout title="Edit influencer">
            <Head title={`Edit ${influencer.name}`} />
            <InfluencerForm influencer={influencer} />
        </AuthenticatedLayout>
    );
}
