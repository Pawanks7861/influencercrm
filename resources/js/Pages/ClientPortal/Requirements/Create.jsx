import { Head, useForm, usePage } from '@inertiajs/react';
import ClientPortalLayout from '@/Layouts/ClientPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import InputError from '@/Components/InputError';

export default function Create() {
    const { crm } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        requirement_type: 'influencer_marketing',
        title: '',
        brand_name: '',
        description: '',
        budget_min: '',
        budget_max: '',
        preferred_start_date: '',
        preferred_end_date: '',
        expected_posting_date: '',
        preferred_location: '',
        preferred_category: '',
        preferred_platforms: [],
        influencers_required: '',
        target_audience: '',
        services_required: [],
        duration: '',
        posting_frequency: '',
        goals: '',
        additional_instructions: '',
        client_notes: '',
        deliverables: [],
    });

    const type = data.requirement_type;
    const showIm = type === 'influencer_marketing' || type === 'both';
    const showSmm = type === 'social_media_marketing' || type === 'both';

    const toggleArray = (field, value) => {
        const current = data[field] || [];
        setData(field, current.includes(value) ? current.filter((v) => v !== value) : [...current, value]);
    };

    return (
        <ClientPortalLayout title="New requirement">
            <Head title="New requirement" />
            <PageHeader title="New requirement" description="Tell us what you need — we will shortlist and propose options" />

            <form
                className="crm-card max-w-3xl space-y-4 p-5"
                onSubmit={(e) => {
                    e.preventDefault();
                    post(route('client.requirements.store'));
                }}
            >
                <div>
                    <label className="crm-label">Type</label>
                    <select className="crm-input" value={data.requirement_type} onChange={(e) => setData('requirement_type', e.target.value)}>
                        {Object.entries(crm.requirement_types || {}).map(([value, label]) => (
                            <option key={value} value={value}>{label}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label className="crm-label">Title</label>
                    <input className="crm-input" value={data.title} onChange={(e) => setData('title', e.target.value)} required />
                    <InputError message={errors.title} className="mt-1" />
                </div>
                <div>
                    <label className="crm-label">Brand name</label>
                    <input className="crm-input" value={data.brand_name} onChange={(e) => setData('brand_name', e.target.value)} />
                </div>
                <div>
                    <label className="crm-label">Description</label>
                    <textarea className="crm-input min-h-[100px]" value={data.description} onChange={(e) => setData('description', e.target.value)} />
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label className="crm-label">Budget min</label>
                        <input type="number" className="crm-input" value={data.budget_min} onChange={(e) => setData('budget_min', e.target.value)} />
                    </div>
                    <div>
                        <label className="crm-label">Budget max</label>
                        <input type="number" className="crm-input" value={data.budget_max} onChange={(e) => setData('budget_max', e.target.value)} />
                    </div>
                </div>

                {showIm && (
                    <div className="space-y-3 rounded-lg border border-surface-border p-4">
                        <h3 className="font-display text-sm font-semibold">Influencer marketing details</h3>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label className="crm-label">Influencers needed</label>
                                <input type="number" className="crm-input" value={data.influencers_required} onChange={(e) => setData('influencers_required', e.target.value)} />
                            </div>
                            <div>
                                <label className="crm-label">Preferred location</label>
                                <input className="crm-input" value={data.preferred_location} onChange={(e) => setData('preferred_location', e.target.value)} />
                            </div>
                        </div>
                        <div>
                            <label className="crm-label">Category</label>
                            <select className="crm-input" value={data.preferred_category} onChange={(e) => setData('preferred_category', e.target.value)}>
                                <option value="">Select</option>
                                {Object.entries(crm.preferred_categories || {}).map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="crm-label">Platforms</label>
                            <div className="mt-1 flex flex-wrap gap-2">
                                {Object.entries(crm.platforms || {}).map(([value, label]) => (
                                    <label key={value} className="inline-flex items-center gap-1.5 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={(data.preferred_platforms || []).includes(value)}
                                            onChange={() => toggleArray('preferred_platforms', value)}
                                        />
                                        {label}
                                    </label>
                                ))}
                            </div>
                        </div>
                        <div>
                            <label className="crm-label">Expected posting date</label>
                            <input type="date" className="crm-input" value={data.expected_posting_date} onChange={(e) => setData('expected_posting_date', e.target.value)} />
                        </div>
                    </div>
                )}

                {showSmm && (
                    <div className="space-y-3 rounded-lg border border-surface-border p-4">
                        <h3 className="font-display text-sm font-semibold">Social media marketing details</h3>
                        <div>
                            <label className="crm-label">Services needed</label>
                            <div className="mt-1 flex flex-wrap gap-2">
                                {Object.entries(crm.services || {}).map(([value, label]) => (
                                    <label key={value} className="inline-flex items-center gap-1.5 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={(data.services_required || []).includes(value)}
                                            onChange={() => toggleArray('services_required', value)}
                                        />
                                        {label}
                                    </label>
                                ))}
                            </div>
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label className="crm-label">Duration</label>
                                <input className="crm-input" value={data.duration} onChange={(e) => setData('duration', e.target.value)} placeholder="e.g. 3 months" />
                            </div>
                            <div>
                                <label className="crm-label">Posting frequency</label>
                                <input className="crm-input" value={data.posting_frequency} onChange={(e) => setData('posting_frequency', e.target.value)} placeholder="e.g. 3 posts / week" />
                            </div>
                        </div>
                        <div>
                            <label className="crm-label">Goals</label>
                            <textarea className="crm-input min-h-[80px]" value={data.goals} onChange={(e) => setData('goals', e.target.value)} />
                        </div>
                    </div>
                )}

                <div>
                    <label className="crm-label">Additional notes</label>
                    <textarea className="crm-input min-h-[80px]" value={data.client_notes} onChange={(e) => setData('client_notes', e.target.value)} />
                </div>

                <div className="flex justify-end">
                    <button type="submit" className="crm-btn-primary" disabled={processing}>Submit requirement</button>
                </div>
            </form>
        </ClientPortalLayout>
    );
}
