import { Head, useForm, usePage } from '@inertiajs/react';
import InfluencerPortalLayout from '@/Layouts/InfluencerPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import InputError from '@/Components/InputError';
import InfluencerTypeBadge from '@/Components/Crm/InfluencerTypeBadge';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';

export default function ProfileEdit({ influencer }) {
    const { crm } = usePage().props;
    const { data, setData, patch, processing, errors } = useForm({
        mobile: influencer.mobile || '',
        email: influencer.email || '',
        instagram_url: influencer.instagram_url || '',
        youtube_url: influencer.youtube_url || '',
        facebook_url: influencer.facebook_url || '',
        linkedin_url: influencer.linkedin_url || '',
        twitter_url: influencer.twitter_url || '',
        other_social_url: influencer.other_social_url || '',
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('influencer.profile.update'), { preserveScroll: true });
    };

    return (
        <InfluencerPortalLayout title="My Profile">
            <Head title="My Profile" />
            <PageHeader title="My Profile" description="Update your contact and social links" />

            <div className="grid gap-4 lg:grid-cols-3">
                <div className="crm-card space-y-3 p-5">
                    <h3 className="font-display text-sm font-semibold text-ink">Studio profile</h3>
                    <p className="text-lg font-semibold text-ink">{influencer.name}</p>
                    <InfluencerTypeBadge type={influencer.influencer_type} labels={crm.influencer_types} />
                    <div className="text-sm">
                        <p className="text-ink-muted">Default rate (set by studio)</p>
                        <p className="font-medium text-ink">
                            <MoneyDisplay amount={influencer.default_price} />
                        </p>
                    </div>
                    <p className="text-xs text-ink-muted">Type and pricing can only be changed by Grovera Studio.</p>
                </div>

                <form onSubmit={submit} className="crm-card space-y-4 p-5 lg:col-span-2">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label className="crm-label">Mobile</label>
                            <input className="crm-input" value={data.mobile} onChange={(e) => setData('mobile', e.target.value)} />
                            <InputError message={errors.mobile} className="mt-1" />
                        </div>
                        <div>
                            <label className="crm-label">Email</label>
                            <input type="email" className="crm-input" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                            <InputError message={errors.email} className="mt-1" />
                        </div>
                    </div>

                    {[
                        ['instagram_url', 'Instagram URL'],
                        ['youtube_url', 'YouTube URL'],
                        ['facebook_url', 'Facebook URL'],
                        ['linkedin_url', 'LinkedIn URL'],
                        ['twitter_url', 'X / Twitter URL'],
                        ['other_social_url', 'Other social URL'],
                    ].map(([key, label]) => (
                        <div key={key}>
                            <label className="crm-label">{label}</label>
                            <input className="crm-input" value={data[key]} onChange={(e) => setData(key, e.target.value)} />
                            <InputError message={errors[key]} className="mt-1" />
                        </div>
                    ))}

                    <div className="flex justify-end">
                        <button type="submit" className="crm-btn-primary" disabled={processing}>
                            Save profile
                        </button>
                    </div>
                </form>
            </div>
        </InfluencerPortalLayout>
    );
}
