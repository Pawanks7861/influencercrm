import { Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import PageHeader from '@/Components/Crm/PageHeader';
import InputError from '@/Components/InputError';

function InfluencerForm({ influencer = null }) {
    const { crm } = usePage().props;
    const isEdit = Boolean(influencer);
    const [duplicates, setDuplicates] = useState([]);
    const [checking, setChecking] = useState(false);

    const { data, setData, post, put, processing, errors, clearErrors, transform } = useForm({
        name: influencer?.name || '',
        instagram_url: influencer?.instagram_url || '',
        instagram_username: influencer?.instagram_username || '',
        youtube_url: influencer?.youtube_url || '',
        facebook_url: influencer?.facebook_url || '',
        linkedin_url: influencer?.linkedin_url || '',
        twitter_url: influencer?.twitter_url || '',
        other_social_url: influencer?.other_social_url || '',
        mobile: influencer?.mobile || '',
        email: influencer?.email || '',
        location: influencer?.location || '',
        influencer_type: influencer?.influencer_type?.value || influencer?.influencer_type || 'medium',
        default_price: influencer?.default_price ?? '',
        notes_summary: influencer?.notes_summary || '',
        status: influencer?.status || 'active',
        force: false,
    });

    useEffect(() => {
        if (errors.duplicates || errors.duplicate_ids) {
            checkDuplicates(true);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [errors.duplicates, errors.duplicate_ids]);

    const updateField = (field, value) => {
        setData(field, value);
        if (errors[field]) {
            clearErrors(field);
        }
    };

    const checkDuplicates = async (forceShow = false) => {
        if (!data.instagram_username && !data.instagram_url && !data.email && !data.mobile) {
            setDuplicates([]);
            return;
        }
        setChecking(true);
        try {
            const params = new URLSearchParams({
                instagram_username: data.instagram_username || '',
                instagram_url: data.instagram_url || '',
                email: data.email || '',
                mobile: data.mobile || '',
            });
            if (influencer?.id) params.set('exclude_id', influencer.id);
            const res = await fetch(`${route('influencers.check-duplicate')}?${params}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (json.has_duplicates || forceShow) {
                setDuplicates(json.duplicates || []);
            } else {
                setDuplicates([]);
            }
        } catch {
            /* ignore */
        } finally {
            setChecking(false);
        }
    };

    const submit = (e, force = false) => {
        e.preventDefault();
        transform((form) => ({ ...form, force }));
        const options = {
            onError: () => checkDuplicates(true),
            onSuccess: () => setDuplicates([]),
        };
        if (isEdit) {
            put(route('influencers.update', influencer.id), options);
        } else {
            post(route('influencers.store'), options);
        }
    };

    return (
        <>
            <PageHeader
                title={isEdit ? 'Edit influencer' : 'Add influencer'}
                description={isEdit ? 'Update profile details' : 'Create a new influencer profile'}
                actions={
                    <Link href={route('influencers.index')} className="crm-btn-secondary">
                        Cancel
                    </Link>
                }
            />

            {(duplicates.length > 0 || errors.duplicates) && (
                <div className="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p className="text-sm font-semibold text-amber-900">Potential duplicates found</p>
                    <p className="mt-1 text-sm text-amber-800">
                        {typeof errors.duplicates === 'string' ? errors.duplicates : 'An existing profile may match this data.'}
                    </p>
                    <ul className="mt-3 space-y-2">
                        {duplicates.map((dup) => (
                            <li key={dup.id} className="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white px-3 py-2 text-sm">
                                <div>
                                    <p className="font-medium text-ink">{dup.name}</p>
                                    <p className="text-xs text-ink-muted">
                                        {dup.instagram_username ? `@${dup.instagram_username}` : ''}
                                        {dup.mobile ? ` · ${dup.mobile}` : ''}
                                        {dup.email ? ` · ${dup.email}` : ''}
                                    </p>
                                </div>
                                <Link href={route('influencers.show', dup.id)} className="crm-btn-secondary !py-1.5 text-xs">
                                    Open existing profile
                                </Link>
                            </li>
                        ))}
                    </ul>
                    <div className="mt-3 flex flex-wrap gap-2">
                        <button type="button" className="crm-btn-primary" disabled={processing} onClick={(e) => submit(e, true)}>
                            Save anyway
                        </button>
                        <button type="button" className="crm-btn-secondary" onClick={() => checkDuplicates(true)} disabled={checking}>
                            Refresh matches
                        </button>
                    </div>
                </div>
            )}

            <form onSubmit={(e) => submit(e, false)} className="crm-card max-w-3xl space-y-5 p-5 sm:p-6" noValidate>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="sm:col-span-2">
                        <label className="crm-label">Influencer Name *</label>
                        <input className="crm-input" value={data.name} onChange={(e) => updateField('name', e.target.value)} required />
                        <InputError message={errors.name} className="mt-1" />
                    </div>
                    <div>
                        <label className="crm-label">Instagram *</label>
                        <input
                            className="crm-input"
                            value={data.instagram_username}
                            onChange={(e) => updateField('instagram_username', e.target.value)}
                            onBlur={() => checkDuplicates()}
                            placeholder="@username"
                            required={!data.instagram_url}
                        />
                        <InputError message={errors.instagram_username} className="mt-1" />
                    </div>
                    <div>
                        <label className="crm-label">Instagram URL</label>
                        <input
                            className="crm-input"
                            value={data.instagram_url}
                            onChange={(e) => updateField('instagram_url', e.target.value)}
                            onBlur={() => checkDuplicates()}
                            required={!data.instagram_username}
                        />
                        <InputError message={errors.instagram_url} className="mt-1" />
                    </div>
                    <div>
                        <label className="crm-label">Mobile Number *</label>
                        <input
                            className="crm-input"
                            value={data.mobile}
                            onChange={(e) => updateField('mobile', e.target.value)}
                            onBlur={() => checkDuplicates()}
                            required
                        />
                        <InputError message={errors.mobile} className="mt-1" />
                    </div>
                    <div>
                        <label className="crm-label">Email</label>
                        <input
                            type="email"
                            className="crm-input"
                            value={data.email}
                            onChange={(e) => updateField('email', e.target.value)}
                            onBlur={() => checkDuplicates()}
                        />
                        <InputError message={errors.email} className="mt-1" />
                    </div>
                    <div>
                        <label className="crm-label">Location *</label>
                        <input
                            className="crm-input"
                            value={data.location}
                            onChange={(e) => updateField('location', e.target.value)}
                            required
                        />
                        <InputError message={errors.location} className="mt-1" />
                    </div>
                    <div>
                        <label className="crm-label">Influencer Type *</label>
                        <select
                            className="crm-input"
                            value={data.influencer_type}
                            onChange={(e) => updateField('influencer_type', e.target.value)}
                            required
                        >
                            {Object.entries(crm.influencer_types || {}).map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                        <InputError message={errors.influencer_type} className="mt-1" />
                    </div>
                    <div>
                        <label className="crm-label">Influencer Price *</label>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            className="crm-input"
                            value={data.default_price}
                            onChange={(e) => updateField('default_price', e.target.value)}
                            required
                        />
                        <InputError message={errors.default_price} className="mt-1" />
                    </div>
                    <div>
                        <label className="crm-label">Status</label>
                        <select className="crm-input" value={data.status} onChange={(e) => updateField('status', e.target.value)}>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>

                <div className="border-t border-surface-border pt-5">
                    <h3 className="mb-3 font-display text-sm font-semibold text-ink">Social profiles</h3>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label className="crm-label">YouTube URL</label>
                            <input className="crm-input" value={data.youtube_url} onChange={(e) => updateField('youtube_url', e.target.value)} />
                            <InputError message={errors.youtube_url} className="mt-1" />
                        </div>
                        <div>
                            <label className="crm-label">Facebook URL</label>
                            <input className="crm-input" value={data.facebook_url} onChange={(e) => updateField('facebook_url', e.target.value)} />
                            <InputError message={errors.facebook_url} className="mt-1" />
                        </div>
                        <div>
                            <label className="crm-label">LinkedIn URL</label>
                            <input className="crm-input" value={data.linkedin_url} onChange={(e) => updateField('linkedin_url', e.target.value)} />
                            <InputError message={errors.linkedin_url} className="mt-1" />
                        </div>
                        <div>
                            <label className="crm-label">X / Twitter URL</label>
                            <input className="crm-input" value={data.twitter_url} onChange={(e) => updateField('twitter_url', e.target.value)} />
                            <InputError message={errors.twitter_url} className="mt-1" />
                        </div>
                        <div className="sm:col-span-2">
                            <label className="crm-label">Other Social Link</label>
                            <input className="crm-input" value={data.other_social_url} onChange={(e) => updateField('other_social_url', e.target.value)} />
                            <InputError message={errors.other_social_url} className="mt-1" />
                        </div>
                    </div>
                </div>

                <div>
                    <label className="crm-label">Notes summary</label>
                    <textarea className="crm-input min-h-[100px]" value={data.notes_summary} onChange={(e) => updateField('notes_summary', e.target.value)} />
                    <InputError message={errors.notes_summary} className="mt-1" />
                </div>

                <div className="flex justify-end gap-2 border-t border-surface-border pt-4">
                    <Link href={isEdit ? route('influencers.show', influencer.id) : route('influencers.index')} className="crm-btn-secondary">
                        Cancel
                    </Link>
                    <button type="submit" className="crm-btn-primary" disabled={processing}>
                        {isEdit ? 'Save changes' : 'Create influencer'}
                    </button>
                </div>
            </form>
        </>
    );
}

export default InfluencerForm;
