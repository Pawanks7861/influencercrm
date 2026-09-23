import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import { Head } from '@inertiajs/react';

export default function Edit({ mustVerifyEmail, status }) {
    return (
        <AuthenticatedLayout title="Profile">
            <Head title="Profile" />
            <PageHeader title="Profile" description="Manage your account" />

            <div className="mx-auto max-w-3xl space-y-6">
                <div className="crm-card p-5 sm:p-6">
                    <UpdateProfileInformationForm mustVerifyEmail={mustVerifyEmail} status={status} className="max-w-xl" />
                </div>
                <div className="crm-card p-5 sm:p-6">
                    <UpdatePasswordForm className="max-w-xl" />
                </div>
                <div className="crm-card p-5 sm:p-6">
                    <DeleteUserForm className="max-w-xl" />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
