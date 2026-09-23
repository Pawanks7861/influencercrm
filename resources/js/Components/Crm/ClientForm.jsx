import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import PageHeader from '@/Components/Crm/PageHeader';
import InputError from '@/Components/InputError';

const STATUS_OPTIONS = [
    { value: 'lead', label: 'Lead' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

export default function ClientForm({ client = null }) {
    const isEdit = Boolean(client);
    const [duplicates, setDuplicates] = useState([]);
    const [checking, setChecking] = useState(false);

    const { data, setData, post, put, processing, errors, clearErrors, transform } = useForm({
        company_name: client?.company_name || client?.name || '',
        contact_person: client?.contact_person || '',
        mobile: client?.mobile || client?.phone || '',
        alternate_mobile: client?.alternate_mobile || '',
        email: client?.email || '',
        website: client?.website || '',
        instagram_url: client?.instagram_url || '',
        facebook_url: client?.facebook_url || '',
        linkedin_url: client?.linkedin_url || '',
        youtube_url: client?.youtube_url || '',
        twitter_url: client?.twitter_url || '',
        address: client?.address || '',
        notes: client?.notes || '',
        status: client?.status?.value || client?.status || 'active',
        force: false,
    });

    const updateField = (field, value) => {
        setData(field, value);
        if (errors[field]) clearErrors(field);
    };

    const checkDuplicates = async (forceShow = false) => {
        if (!data.company_name && !data.mobile && !data.email) {
            setDuplicates([]);
            return;
        }
        setChecking(true);
        try {
            const params = new URLSearchParams({
                company_name: data.company_name || '',
                mobile: data.mobile || '',
                email: data.email || '',
            });
            if (client?.id) params.set('exclude_id', client.id);
            const res = await fetch(`${route('clients.check-duplicate')}?${params}`, {
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
            put(route('clients.update', client.id), options);
        } else {
            post(route('clients.store'), options);
        }
    };

    return (
        <>
            <PageHeader
                title={isEdit ? 'Edit client' : 'Add client'}
                description={isEdit ? 'Update client profile' : 'Create a new client company'}
                actions={
                    <Link href={route('clients.index')} className="crm-btn-secondary">
                        Cancel
                    </Link>
                }
            />

            {(duplicates.length > 0 || errors.duplicates) && (
                <div className="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p className="text-sm font-semibold text-amber-900">This client may already exist.</p>
                    <ul className="mt-3 space-y-2">
                        {duplicates.map((dup) => (
                            <li key={dup.id} className="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white px-3 py-2 text-sm">
                                <div>
                                    <p className="font-medium text-ink">{dup.company_name || dup.name}</p>
                                    <p className="text-xs text-ink-muted">
                                        {dup.contact_person || ''}
                                        {dup.mobile ? ` · ${dup.mobile}` : ''}
                                        {dup.email ? ` · ${dup.email}` : ''}
                                    </p>
                                </div>
                                <Link href={route('clients.show', dup.id)} className="crm-btn-secondary !py-1.5 text-xs">
                                    Open existing client
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
                        <label className="crm-label">Company Name *</label>
                        <input
                            className="crm-input"
                            value={data.company_name}
                            onChange={(e) => updateField('company_name', e.target.value)}
                            onBlur={() => checkDuplicates()}
                            required
                        />
                        <InputError message={errors.company_name} className="mt-1" />
                    </div>
                    <div>
                        <label className="crm-label">Contact Person *</label>
                        <input
                            className="crm-input"
                            value={data.contact_person}
                            onChange={(e) => updateField('contact_person', e.target.value)}
                            required
                        />
                        <InputError message={errors.contact_person} className="mt-1" />
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
                        <label className="crm-label">Alternate Mobile</label>
                        <input className="crm-input" value={data.alternate_mobile} onChange={(e) => updateField('alternate_mobile', e.target.value)} />
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
                        <label className="crm-label">Website</label>
                        <input className="crm-input" value={data.website} onChange={(e) => updateField('website', e.target.value)} />
                    </div>
                    <div>
                        <label className="crm-label">Status</label>
                        <select className="crm-input" value={data.status} onChange={(e) => updateField('status', e.target.value)}>
                            {STATUS_OPTIONS.map((opt) => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="border-t border-surface-border pt-5">
                    <h3 className="mb-3 font-display text-sm font-semibold text-ink">Social profiles</h3>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label className="crm-label">Instagram</label>
                            <input className="crm-input" value={data.instagram_url} onChange={(e) => updateField('instagram_url', e.target.value)} />
                        </div>
                        <div>
                            <label className="crm-label">Facebook</label>
                            <input className="crm-input" value={data.facebook_url} onChange={(e) => updateField('facebook_url', e.target.value)} />
                        </div>
                        <div>
                            <label className="crm-label">LinkedIn</label>
                            <input className="crm-input" value={data.linkedin_url} onChange={(e) => updateField('linkedin_url', e.target.value)} />
                        </div>
                        <div>
                            <label className="crm-label">YouTube</label>
                            <input className="crm-input" value={data.youtube_url} onChange={(e) => updateField('youtube_url', e.target.value)} />
                        </div>
                        <div className="sm:col-span-2">
                            <label className="crm-label">X / Twitter</label>
                            <input className="crm-input" value={data.twitter_url} onChange={(e) => updateField('twitter_url', e.target.value)} />
                        </div>
                    </div>
                </div>

                <div>
                    <label className="crm-label">Address</label>
                    <textarea className="crm-input min-h-[80px]" value={data.address} onChange={(e) => updateField('address', e.target.value)} />
                </div>
                <div>
                    <label className="crm-label">Notes</label>
                    <textarea className="crm-input min-h-[100px]" value={data.notes} onChange={(e) => updateField('notes', e.target.value)} />
                </div>

                <div className="flex justify-end gap-2 border-t border-surface-border pt-4">
                    <Link href={isEdit ? route('clients.show', client.id) : route('clients.index')} className="crm-btn-secondary">
                        Cancel
                    </Link>
                    <button type="submit" className="crm-btn-primary" disabled={processing}>
                        {isEdit ? 'Save changes' : 'Create client'}
                    </button>
                </div>
            </form>
        </>
    );
}
