import { useEffect, useMemo, useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import PageHeader from '@/Components/Crm/PageHeader';
import InputError from '@/Components/InputError';
import InfluencerTypeBadge from '@/Components/Crm/InfluencerTypeBadge';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import SearchInput from '@/Components/Crm/SearchInput';

function blankRow(influencer = null) {
    const cost = Number(influencer?.default_price || 0);
    return {
        influencer_id: influencer?.id || '',
        influencer_cost: cost,
        additional_cost: 0,
        grovera_fee: 0,
        final_amount: cost,
        final_amount_overridden: false,
        status: 'new_lead',
        negotiated_price: '',
        content_deadline: '',
        posting_date: '',
        remarks: '',
        _influencer: influencer,
    };
}

function blankDeliverable() {
    return {
        id: null,
        type: 'instagram_post',
        platform: 'instagram',
        quantity: 1,
        deadline: '',
        status: 'pending',
        remarks: '',
        title: '',
    };
}

export default function CampaignForm({ campaign = null, clients = [], influencers = [], prefillClientId = null }) {
    const { crm } = usePage().props;
    const isEdit = Boolean(campaign);
    const [search, setSearch] = useState('');
    const [clientMode, setClientMode] = useState('existing');
    const [newClient, setNewClient] = useState({
        company_name: '',
        contact_person: '',
        mobile: '',
        email: '',
    });

    const initialInfluencers = useMemo(() => {
        if (campaign?.campaign_influencers?.length) {
            return campaign.campaign_influencers.map((ci) => ({
                influencer_id: ci.influencer_id,
                influencer_cost: ci.influencer_cost,
                additional_cost: ci.additional_cost ?? 0,
                grovera_fee: ci.grovera_fee,
                final_amount: ci.final_amount,
                final_amount_overridden: Boolean(ci.final_amount_overridden),
                status: ci.status?.value || ci.status || 'new_lead',
                negotiated_price: ci.negotiated_price || '',
                content_deadline: ci.content_deadline ? String(ci.content_deadline).slice(0, 10) : '',
                posting_date: ci.posting_date ? String(ci.posting_date).slice(0, 10) : '',
                remarks: ci.remarks || '',
                _influencer: ci.influencer,
            }));
        }
        return [];
    }, [campaign]);

    const initialDeliverables = useMemo(() => {
        const rows = (campaign?.deliverables || []).filter((d) => !d.campaign_influencer_id);
        return rows.map((d) => ({
            id: d.id,
            type: d.type?.value || d.type || 'instagram_post',
            platform: d.platform || '',
            quantity: d.quantity || 1,
            deadline: d.deadline ? String(d.deadline).slice(0, 10) : '',
            status: d.status?.value || d.status || 'pending',
            remarks: d.remarks || '',
            title: d.title || '',
        }));
    }, [campaign]);

    const { data, setData, processing, errors, setError, clearErrors } = useForm({
        campaign_name: campaign?.campaign_name || '',
        client_id: campaign?.client_id || prefillClientId || '',
        brand_name: campaign?.brand_name || '',
        campaign_type: campaign?.campaign_type?.value || campaign?.campaign_type || 'influencer_marketing',
        platforms: campaign?.platforms || [],
        campaign_budget: campaign?.campaign_budget || '',
        service_fee: campaign?.service_fee || '',
        start_date: campaign?.start_date ? String(campaign.start_date).slice(0, 10) : '',
        deadline: campaign?.deadline ? String(campaign.deadline).slice(0, 10) : '',
        posting_date: campaign?.posting_date ? String(campaign.posting_date).slice(0, 10) : '',
        status: campaign?.status?.value || campaign?.status || 'draft',
        remarks: campaign?.remarks || '',
        influencers: initialInfluencers,
        deliverables: initialDeliverables,
    });
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        const raw = sessionStorage.getItem('crm_add_influencer');
        if (!raw || !isEdit) return;
        try {
            const payload = JSON.parse(raw);
            sessionStorage.removeItem('crm_add_influencer');
            if (!payload?.influencer_id) return;
            if (data.influencers.some((r) => String(r.influencer_id) === String(payload.influencer_id))) return;
            const match = influencers.find((i) => String(i.id) === String(payload.influencer_id));
            setData('influencers', [
                ...data.influencers,
                {
                    ...blankRow(match),
                    influencer_id: payload.influencer_id,
                    influencer_cost: payload.influencer_cost ?? match?.default_price ?? 0,
                    additional_cost: payload.additional_cost ?? 0,
                    grovera_fee: payload.grovera_fee ?? 0,
                    final_amount: payload.final_amount ?? 0,
                    final_amount_overridden: Boolean(payload.final_amount_overridden),
                    status: payload.status || 'new_lead',
                },
            ]);
        } catch {
            /* ignore */
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const showInfluencers = data.campaign_type === 'influencer_marketing' || data.campaign_type === 'both';
    const showSocial = data.campaign_type === 'social_media_marketing' || data.campaign_type === 'both';

    const filteredInfluencers = useMemo(() => {
        const q = search.toLowerCase();
        const selected = new Set(data.influencers.map((r) => String(r.influencer_id)));
        return influencers
            .filter((i) => !selected.has(String(i.id)))
            .filter(
                (i) =>
                    !q ||
                    i.name?.toLowerCase().includes(q) ||
                    i.instagram_username?.toLowerCase().includes(q)
            )
            .slice(0, 8);
    }, [influencers, data.influencers, search]);

    const updateRow = (index, field, value) => {
        const rows = [...data.influencers];
        const row = { ...rows[index], [field]: value };
        if (!row.final_amount_overridden && ['influencer_cost', 'additional_cost', 'grovera_fee'].includes(field)) {
            row.final_amount =
                Number(row.influencer_cost || 0) + Number(row.additional_cost || 0) + Number(row.grovera_fee || 0);
        }
        if (field === 'final_amount') {
            row.final_amount_overridden = true;
        }
        rows[index] = row;
        setData('influencers', rows);
    };

    const addInfluencer = (inf) => {
        setData('influencers', [...data.influencers, blankRow(inf)]);
        setSearch('');
    };

    const removeRow = (index) => {
        setData(
            'influencers',
            data.influencers.filter((_, i) => i !== index)
        );
    };

    const updateDeliverable = (index, field, value) => {
        const rows = [...data.deliverables];
        rows[index] = { ...rows[index], [field]: value };
        setData('deliverables', rows);
    };

    const togglePlatform = (value) => {
        const current = data.platforms || [];
        setData(
            'platforms',
            current.includes(value) ? current.filter((p) => p !== value) : [...current, value]
        );
    };

    const totals = data.influencers.reduce(
        (acc, row) => {
            acc.cost += Number(row.influencer_cost || 0);
            acc.additional += Number(row.additional_cost || 0);
            acc.fee += Number(row.grovera_fee || 0);
            acc.final += Number(row.final_amount || 0);
            return acc;
        },
        { cost: 0, additional: 0, fee: 0, final: 0 }
    );

    const submit = async (e) => {
        e.preventDefault();
        clearErrors();
        setSubmitting(true);

        let clientId = data.client_id;

        try {
            if (clientMode === 'new') {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch(route('clients.store'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf || '',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        ...newClient,
                        force: true,
                        status: 'active',
                    }),
                });
                if (!res.ok) {
                    setError('client_id', 'Could not create client. Check required fields and try again.');
                    setSubmitting(false);
                    return;
                }
                const created = await res.json();
                clientId = created.id;
            }

            const payload = {
                ...data,
                client_id: clientId,
                influencers: showInfluencers
                    ? data.influencers.map(({ _influencer, ...rest }) => rest)
                    : [],
                deliverables: showSocial ? data.deliverables : [],
            };

            const options = {
                onFinish: () => setSubmitting(false),
                onError: () => setSubmitting(false),
            };

            if (isEdit) {
                router.put(route('campaigns.update', campaign.id), payload, options);
            } else {
                router.post(route('campaigns.store'), payload, options);
            }
        } catch {
            setSubmitting(false);
        }
    };

    return (
        <>
            <PageHeader
                title={isEdit ? 'Edit campaign' : 'Create campaign'}
                description="Campaign details, influencers, and social services"
                actions={
                    <Link href={isEdit ? route('campaigns.show', campaign.id) : route('campaigns.index')} className="crm-btn-secondary">
                        Cancel
                    </Link>
                }
            />

            <form onSubmit={submit} className="space-y-6">
                <div className="crm-card space-y-4 p-5">
                    <h3 className="font-display text-sm font-semibold text-ink">Campaign info</h3>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <label className="crm-label">Campaign name *</label>
                            <input className="crm-input" value={data.campaign_name} onChange={(e) => setData('campaign_name', e.target.value)} required />
                            <InputError message={errors.campaign_name} className="mt-1" />
                        </div>
                        <div className="sm:col-span-2">
                            <div className="mb-2 flex gap-3 text-sm">
                                <button type="button" className={clientMode === 'existing' ? 'font-semibold text-accent' : 'text-ink-muted'} onClick={() => setClientMode('existing')}>
                                    Existing client
                                </button>
                                <button type="button" className={clientMode === 'new' ? 'font-semibold text-accent' : 'text-ink-muted'} onClick={() => setClientMode('new')}>
                                    New client
                                </button>
                            </div>
                            {clientMode === 'existing' ? (
                                <>
                                    <select className="crm-input" value={data.client_id} onChange={(e) => setData('client_id', e.target.value)} required>
                                        <option value="">Select client</option>
                                        {clients.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.company_name || c.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.client_id} className="mt-1" />
                                </>
                            ) : (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <input className="crm-input" placeholder="Company name *" value={newClient.company_name} onChange={(e) => setNewClient({ ...newClient, company_name: e.target.value })} required={clientMode === 'new'} />
                                    <input className="crm-input" placeholder="Contact person *" value={newClient.contact_person} onChange={(e) => setNewClient({ ...newClient, contact_person: e.target.value })} required={clientMode === 'new'} />
                                    <input className="crm-input" placeholder="Mobile *" value={newClient.mobile} onChange={(e) => setNewClient({ ...newClient, mobile: e.target.value })} required={clientMode === 'new'} />
                                    <input className="crm-input" placeholder="Email" value={newClient.email} onChange={(e) => setNewClient({ ...newClient, email: e.target.value })} />
                                </div>
                            )}
                        </div>
                        <div>
                            <label className="crm-label">Brand name</label>
                            <input className="crm-input" value={data.brand_name} onChange={(e) => setData('brand_name', e.target.value)} />
                        </div>
                        <div>
                            <label className="crm-label">Campaign type *</label>
                            <select className="crm-input" value={data.campaign_type} onChange={(e) => setData('campaign_type', e.target.value)} required>
                                {Object.entries(crm.campaign_types || {}).map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>
                            <InputError message={errors.campaign_type} className="mt-1" />
                        </div>
                        <div>
                            <label className="crm-label">Budget</label>
                            <input type="number" className="crm-input" value={data.campaign_budget} onChange={(e) => setData('campaign_budget', e.target.value)} />
                        </div>
                        {showSocial && (
                            <div>
                                <label className="crm-label">Service fee</label>
                                <input type="number" className="crm-input" value={data.service_fee} onChange={(e) => setData('service_fee', e.target.value)} />
                            </div>
                        )}
                        <div>
                            <label className="crm-label">Start date</label>
                            <input type="date" className="crm-input" value={data.start_date} onChange={(e) => setData('start_date', e.target.value)} />
                        </div>
                        <div>
                            <label className="crm-label">Deadline</label>
                            <input type="date" className="crm-input" value={data.deadline} onChange={(e) => setData('deadline', e.target.value)} />
                        </div>
                        <div>
                            <label className="crm-label">Status</label>
                            <select className="crm-input" value={data.status} onChange={(e) => setData('status', e.target.value)}>
                                {Object.entries(crm.campaign_statuses || {}).map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>
                        </div>
                        {showSocial && (
                            <div className="sm:col-span-2">
                                <label className="crm-label">Platforms</label>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {Object.entries(crm.platforms || {}).map(([value, label]) => (
                                        <label key={value} className="inline-flex items-center gap-2 rounded-lg border border-surface-border px-3 py-1.5 text-sm">
                                            <input
                                                type="checkbox"
                                                checked={(data.platforms || []).includes(value)}
                                                onChange={() => togglePlatform(value)}
                                            />
                                            {label}
                                        </label>
                                    ))}
                                </div>
                            </div>
                        )}
                        <div className="sm:col-span-2">
                            <label className="crm-label">Remarks</label>
                            <textarea className="crm-input" value={data.remarks} onChange={(e) => setData('remarks', e.target.value)} />
                        </div>
                    </div>
                </div>

                {showInfluencers && (
                    <div className="crm-card space-y-4 p-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <h3 className="font-display text-sm font-semibold text-ink">Influencers</h3>
                            <div className="flex flex-wrap gap-4 text-xs text-ink-muted">
                                <span>Cost <MoneyDisplay amount={totals.cost} /></span>
                                <span>Additional <MoneyDisplay amount={totals.additional} /></span>
                                <span>Fee <MoneyDisplay amount={totals.fee} /></span>
                                <span>Final <MoneyDisplay amount={totals.final} /></span>
                            </div>
                        </div>

                        <div>
                            <SearchInput value={search} onChange={setSearch} placeholder="Search influencers to add…" />
                            {search && (
                                <div className="mt-2 rounded-lg border border-surface-border bg-white">
                                    {filteredInfluencers.map((inf) => (
                                        <button
                                            key={inf.id}
                                            type="button"
                                            onClick={() => addInfluencer(inf)}
                                            className="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-surface"
                                        >
                                            <span>
                                                <span className="font-medium text-ink">{inf.name}</span>
                                                <span className="ml-2 text-ink-muted">@{inf.instagram_username}</span>
                                            </span>
                                            <span className="inline-flex items-center gap-2">
                                                <InfluencerTypeBadge type={inf.influencer_type} labels={crm.influencer_types} />
                                                <Plus className="h-3.5 w-3.5 text-accent" />
                                            </span>
                                        </button>
                                    ))}
                                    {!filteredInfluencers.length && <p className="p-3 text-sm text-ink-muted">No matches</p>}
                                </div>
                            )}
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="text-xs uppercase text-ink-muted">
                                    <tr>
                                        <th className="px-2 py-2 text-left">Influencer</th>
                                        <th className="px-2 py-2 text-left">Cost</th>
                                        <th className="px-2 py-2 text-left">Additional Cost</th>
                                        <th className="px-2 py-2 text-left">Fee</th>
                                        <th className="px-2 py-2 text-left">Final</th>
                                        <th className="px-2 py-2 text-left">Status</th>
                                        <th className="px-2 py-2" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.influencers.map((row, index) => {
                                        const inf = row._influencer || influencers.find((i) => String(i.id) === String(row.influencer_id));
                                        return (
                                            <tr key={`${row.influencer_id}-${index}`} className="border-t border-surface-border align-top">
                                                <td className="px-2 py-2">
                                                    <p className="font-medium text-ink">{inf?.name || `ID ${row.influencer_id}`}</p>
                                                    <p className="text-xs text-ink-muted">@{inf?.instagram_username}</p>
                                                </td>
                                                <td className="px-2 py-2">
                                                    <input type="number" className="crm-input !w-24" value={row.influencer_cost} onChange={(e) => updateRow(index, 'influencer_cost', e.target.value)} />
                                                </td>
                                                <td className="px-2 py-2">
                                                    <input type="number" className="crm-input !w-24" value={row.additional_cost} onChange={(e) => updateRow(index, 'additional_cost', e.target.value)} />
                                                </td>
                                                <td className="px-2 py-2">
                                                    <input type="number" className="crm-input !w-24" value={row.grovera_fee} onChange={(e) => updateRow(index, 'grovera_fee', e.target.value)} />
                                                </td>
                                                <td className="px-2 py-2">
                                                    <input type="number" className="crm-input !w-24" value={row.final_amount} onChange={(e) => updateRow(index, 'final_amount', e.target.value)} />
                                                    {row.final_amount_overridden && <p className="mt-0.5 text-[10px] text-amber-700">Overridden</p>}
                                                </td>
                                                <td className="px-2 py-2">
                                                    <select className="crm-input !w-40" value={row.status} onChange={(e) => updateRow(index, 'status', e.target.value)}>
                                                        {Object.entries(crm.collaboration_statuses || {}).map(([value, label]) => (
                                                            <option key={value} value={value}>{label}</option>
                                                        ))}
                                                    </select>
                                                </td>
                                                <td className="px-2 py-2">
                                                    <button type="button" className="rounded p-1.5 text-ink-muted hover:bg-red-50 hover:text-red-600" onClick={() => removeRow(index)}>
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {!data.influencers.length && (
                                        <tr>
                                            <td colSpan={7} className="px-2 py-6 text-center text-ink-muted">Add influencers using search above</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <InputError message={errors.influencers} />
                    </div>
                )}

                {showSocial && (
                    <div className="crm-card space-y-4 p-5">
                        <div className="flex items-center justify-between">
                            <h3 className="font-display text-sm font-semibold text-ink">Social Media Services / Deliverables</h3>
                            <button type="button" className="crm-btn-secondary" onClick={() => setData('deliverables', [...data.deliverables, blankDeliverable()])}>
                                <Plus className="h-4 w-4" /> Add deliverable
                            </button>
                        </div>
                        <div className="space-y-3">
                            {data.deliverables.map((row, index) => (
                                <div key={index} className="grid gap-3 rounded-lg border border-surface-border p-3 sm:grid-cols-6">
                                    <div className="sm:col-span-2">
                                        <label className="crm-label">Type</label>
                                        <select className="crm-input" value={row.type} onChange={(e) => updateDeliverable(index, 'type', e.target.value)}>
                                            {Object.entries(crm.deliverable_types || {}).map(([value, label]) => (
                                                <option key={value} value={value}>{label}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="crm-label">Platform</label>
                                        <select className="crm-input" value={row.platform} onChange={(e) => updateDeliverable(index, 'platform', e.target.value)}>
                                            <option value="">—</option>
                                            {Object.entries(crm.platforms || {}).map(([value, label]) => (
                                                <option key={value} value={value}>{label}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="crm-label">Qty</label>
                                        <input type="number" min="1" className="crm-input" value={row.quantity} onChange={(e) => updateDeliverable(index, 'quantity', e.target.value)} />
                                    </div>
                                    <div>
                                        <label className="crm-label">Deadline</label>
                                        <input type="date" className="crm-input" value={row.deadline} onChange={(e) => updateDeliverable(index, 'deadline', e.target.value)} />
                                    </div>
                                    <div className="flex items-end">
                                        <button type="button" className="crm-btn-secondary w-full" onClick={() => setData('deliverables', data.deliverables.filter((_, i) => i !== index))}>
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            ))}
                            {!data.deliverables.length && (
                                <p className="py-4 text-center text-sm text-ink-muted">Add social media deliverables for this campaign</p>
                            )}
                        </div>
                        <InputError message={errors.deliverables} />
                    </div>
                )}

                <div className="flex justify-end gap-2">
                    <Link href={isEdit ? route('campaigns.show', campaign.id) : route('campaigns.index')} className="crm-btn-secondary">
                        Cancel
                    </Link>
                    <button type="submit" className="crm-btn-primary" disabled={processing || submitting}>
                        {isEdit ? 'Save campaign' : 'Create campaign'}
                    </button>
                </div>
            </form>
        </>
    );
}
