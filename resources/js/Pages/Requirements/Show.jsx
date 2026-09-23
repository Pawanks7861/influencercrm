import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatusBadge from '@/Components/Crm/StatusBadge';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import Drawer from '@/Components/Crm/Drawer';
import InputError from '@/Components/InputError';
import { formatDate, formatDateTime, labelFromMap } from '@/lib/format';

export default function Show({ requirement, shortlists = [], users = [], influencers = [] }) {
    const { crm, auth } = usePage().props;
    const [drawer, setDrawer] = useState(null);
    const [search, setSearch] = useState('');
    const [selected, setSelected] = useState([]);
    const [convertSelection, setConvertSelection] = useState({});

    const statusForm = useForm({
        status: requirement.status?.value || requirement.status,
        internal_notes: requirement.internal_notes || '',
    });

    const assignForm = useForm({
        assigned_to: requirement.assigned_to || '',
    });

    const messageForm = useForm({ message: '' });

    const shortlistForm = useForm({
        title: '',
        message: '',
        items: [],
    });

    const convertForm = useForm({
        shortlist_id: '',
        item_ids: [],
        include_interested: true,
    });

    const filteredInfluencers = useMemo(() => {
        const q = search.trim().toLowerCase();
        if (!q) return influencers.slice(0, 40);
        return influencers.filter((inf) =>
            [inf.name, inf.instagram_username, inf.location].filter(Boolean).join(' ').toLowerCase().includes(q)
        ).slice(0, 40);
    }, [influencers, search]);

    const convertibleShortlists = useMemo(() => {
        const allowed = convertForm.data.include_interested ? ['selected', 'interested'] : ['selected'];
        return shortlists
            .map((sl) => ({
                ...sl,
                convertibleItems: (sl.items || []).filter((item) => allowed.includes(item.status)),
            }))
            .filter((sl) => sl.convertibleItems.length > 0);
    }, [shortlists, convertForm.data.include_interested]);

    const toggleConvertItem = (shortlistId, itemId) => {
        setConvertSelection((prev) => {
            const current = prev[shortlistId] || [];
            const next = current.includes(itemId)
                ? current.filter((id) => id !== itemId)
                : [...current, itemId];
            return { ...prev, [shortlistId]: next };
        });
    };

    const submitConvert = (shortlistId) => {
        const itemIds = convertSelection[shortlistId] || [];
        if (!itemIds.length) return;
        convertForm.transform(() => ({
            shortlist_id: shortlistId,
            item_ids: itemIds,
            include_interested: convertForm.data.include_interested,
        }));
        convertForm.post(route('requirements.convert-to-campaign', requirement.id));
    };

    const revisePrice = (shortlistId, item) => {
        const next = window.prompt('New client price', item.client_price);
        if (next === null || next === '') return;
        router.patch(route('shortlists.update', shortlistId), {
            items: [{ id: item.id, client_price: next }],
        }, { preserveScroll: true });
    };

    const toggleInfluencer = (inf) => {
        setSelected((prev) => {
            const exists = prev.find((p) => p.influencer_id === inf.id);
            if (exists) return prev.filter((p) => p.influencer_id !== inf.id);
            return [
                ...prev,
                {
                    influencer_id: inf.id,
                    name: inf.name,
                    default_price: inf.default_price,
                    client_price: inf.default_price || '',
                    description: '',
                    show_name: true,
                    show_instagram: true,
                    show_price: true,
                    show_location: false,
                    show_type: false,
                    show_note: true,
                },
            ];
        });
    };

    const submitShortlist = (e) => {
        e.preventDefault();
        shortlistForm.transform(() => ({
            title: shortlistForm.data.title,
            message: shortlistForm.data.message,
            items: selected.map((s) => ({
                influencer_id: s.influencer_id,
                client_price: s.client_price,
                description: s.description,
                show_name: s.show_name,
                show_instagram: s.show_instagram,
                show_price: s.show_price,
                show_location: s.show_location,
                show_type: s.show_type,
                show_note: s.show_note,
            })),
        }));
        shortlistForm.post(route('requirements.shortlists.store', requirement.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDrawer(null);
                setSelected([]);
                shortlistForm.reset();
                shortlistForm.transform((data) => data);
            },
        });
    };

    const canShare = auth.user?.permissions?.includes('shortlists.share') || auth.user?.roles?.includes('admin');
    const canWithdraw = auth.user?.permissions?.includes('shortlists.withdraw') || auth.user?.roles?.includes('admin');
    const canConvert = auth.user?.permissions?.includes('requirements.manage')
        || auth.user?.permissions?.includes('campaigns.create')
        || auth.user?.roles?.includes('admin');
    const alreadyConverted = Boolean(requirement.converted_campaign_id);

    return (
        <AuthenticatedLayout title={requirement.title}>
            <Head title={requirement.title} />
            <PageHeader
                title={requirement.title}
                description={
                    <span className="inline-flex flex-wrap items-center gap-2">
                        <span className="font-mono text-xs">{requirement.requirement_number}</span>
                        <StatusBadge status={requirement.status} labels={crm.requirement_statuses} />
                        <span>{labelFromMap(crm.requirement_types, requirement.requirement_type)}</span>
                    </span>
                }
                actions={
                    <button type="button" className="crm-btn-primary" onClick={() => setDrawer('shortlist')}>
                        Create shortlist
                    </button>
                }
            />

            <div className="grid gap-4 lg:grid-cols-3">
                <div className="space-y-4 lg:col-span-2">
                    <div className="crm-card space-y-3 p-5">
                        <h3 className="font-display text-sm font-semibold">Brief</h3>
                        <dl className="grid gap-3 sm:grid-cols-2 text-sm">
                            <div>
                                <dt className="text-ink-muted">Client</dt>
                                <dd className="font-medium">{requirement.client?.company_name || requirement.client?.name}</dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Brand</dt>
                                <dd className="font-medium">{requirement.brand_name || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Budget</dt>
                                <dd className="font-medium">
                                    <MoneyDisplay amount={requirement.budget_min} /> – <MoneyDisplay amount={requirement.budget_max} />
                                </dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Submitted</dt>
                                <dd className="font-medium">{formatDate(requirement.submitted_at)}</dd>
                            </div>
                        </dl>
                        {requirement.description && (
                            <p className="whitespace-pre-wrap text-sm text-ink-soft">{requirement.description}</p>
                        )}
                    </div>

                    <div className="crm-card overflow-hidden">
                        <div className="border-b border-surface-border px-5 py-3 flex flex-wrap items-center justify-between gap-2">
                            <h3 className="font-display text-sm font-semibold">Shortlists</h3>
                            {canConvert && !alreadyConverted && convertibleShortlists.length > 0 && (
                                <label className="inline-flex items-center gap-2 text-xs text-ink-soft">
                                    <input
                                        type="checkbox"
                                        checked={convertForm.data.include_interested}
                                        onChange={(e) => convertForm.setData('include_interested', e.target.checked)}
                                    />
                                    Include interested
                                </label>
                            )}
                        </div>
                        {alreadyConverted && (
                            <div className="border-b border-surface-border bg-surface px-5 py-3 text-sm text-ink-soft">
                                Converted to campaign{' '}
                                <a href={route('campaigns.edit', requirement.converted_campaign_id)} className="text-accent hover:text-accent-hover">
                                    #{requirement.converted_campaign_id}
                                </a>
                            </div>
                        )}
                        {shortlists.map((sl) => {
                            const convertible = convertibleShortlists.find((c) => c.id === sl.id);
                            const selectedIds = convertSelection[sl.id] || [];
                            return (
                            <div key={sl.id} className="border-b border-surface-border px-5 py-4 last:border-0">
                                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <p className="font-medium text-ink">{sl.title}</p>
                                        <StatusBadge status={sl.status} labels={crm.shortlist_statuses} />
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {canShare && sl.status === 'draft' && (
                                            <button
                                                type="button"
                                                className="crm-btn-secondary"
                                                onClick={() => {
                                                    if (confirm('Share this shortlist with the client?')) {
                                                        router.post(route('shortlists.share', sl.id), {}, { preserveScroll: true });
                                                    }
                                                }}
                                            >
                                                Share
                                            </button>
                                        )}
                                        {canWithdraw && ['shared', 'viewed', 'responded'].includes(sl.status) && (
                                            <button
                                                type="button"
                                                className="crm-btn-secondary"
                                                onClick={() => {
                                                    if (confirm('Withdraw this shortlist? Client will lose access.')) {
                                                        router.post(route('shortlists.withdraw', sl.id), {}, { preserveScroll: true });
                                                    }
                                                }}
                                            >
                                                Withdraw
                                            </button>
                                        )}
                                        {canConvert && !alreadyConverted && convertible && (
                                            <button
                                                type="button"
                                                className="crm-btn-primary"
                                                disabled={!selectedIds.length || convertForm.processing}
                                                onClick={() => submitConvert(sl.id)}
                                            >
                                                Convert Selected Influencers to Campaign
                                            </button>
                                        )}
                                    </div>
                                </div>
                                <table className="min-w-full text-sm">
                                    <thead className="text-xs uppercase text-ink-muted">
                                        <tr>
                                            {canConvert && !alreadyConverted && convertible && (
                                                <th className="py-2 text-left w-8" />
                                            )}
                                            <th className="py-2 text-left">Influencer</th>
                                            <th className="py-2 text-right">Internal price</th>
                                            <th className="py-2 text-right">Client price</th>
                                            <th className="py-2 text-left">Status</th>
                                            <th className="py-2 text-left">Remark</th>
                                            <th className="py-2 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {sl.items.map((item) => {
                                            const canPick = convertible?.convertibleItems?.some((c) => c.id === item.id);
                                            return (
                                            <tr key={item.id} className="border-t border-surface-border">
                                                {canConvert && !alreadyConverted && convertible && (
                                                    <td className="py-2">
                                                        {canPick && (
                                                            <input
                                                                type="checkbox"
                                                                checked={selectedIds.includes(item.id)}
                                                                onChange={() => toggleConvertItem(sl.id, item.id)}
                                                            />
                                                        )}
                                                    </td>
                                                )}
                                                <td className="py-2">{item.display_name}</td>
                                                <td className="py-2 text-right"><MoneyDisplay amount={item.internal_price} /></td>
                                                <td className="py-2 text-right"><MoneyDisplay amount={item.client_price} /></td>
                                                <td className="py-2"><StatusBadge status={item.status} labels={crm.shortlist_item_statuses} /></td>
                                                <td className="py-2 text-ink-soft">{item.client_remark || '—'}</td>
                                                <td className="py-2 text-right">
                                                    <button
                                                        type="button"
                                                        className="text-xs text-accent hover:text-accent-hover"
                                                        onClick={() => revisePrice(sl.id, item)}
                                                    >
                                                        Revise price
                                                    </button>
                                                </td>
                                            </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                            );
                        })}
                        {!shortlists.length && (
                            <p className="px-5 py-8 text-center text-sm text-ink-muted">No shortlists yet</p>
                        )}
                    </div>

                    <div className="crm-card p-5">
                        <h3 className="mb-3 font-display text-sm font-semibold">Messages</h3>
                        <ul className="mb-4 space-y-3">
                            {(requirement.messages || []).map((m) => (
                                <li key={m.id} className="rounded-lg bg-surface px-3 py-2 text-sm">
                                    <div className="mb-1 flex justify-between text-xs text-ink-muted">
                                        <span>{m.sender_type === 'client' ? 'Client' : 'Staff'} · {m.user?.name}</span>
                                        <span>{formatDateTime(m.created_at)}</span>
                                    </div>
                                    <p className="whitespace-pre-wrap text-ink">{m.message}</p>
                                </li>
                            ))}
                            {!requirement.messages?.length && (
                                <li className="text-sm text-ink-muted">No messages yet</li>
                            )}
                        </ul>
                        <form
                            className="flex gap-2"
                            onSubmit={(e) => {
                                e.preventDefault();
                                messageForm.post(route('requirements.messages.store', requirement.id), {
                                    preserveScroll: true,
                                    onSuccess: () => messageForm.reset(),
                                });
                            }}
                        >
                            <input
                                className="crm-input flex-1"
                                placeholder="Write a message…"
                                value={messageForm.data.message}
                                onChange={(e) => messageForm.setData('message', e.target.value)}
                            />
                            <button type="submit" className="crm-btn-primary" disabled={messageForm.processing}>Send</button>
                        </form>
                    </div>
                </div>

                <div className="space-y-4">
                    <div className="crm-card space-y-3 p-5">
                        <h3 className="font-display text-sm font-semibold">Status</h3>
                        <form
                            className="space-y-3"
                            onSubmit={(e) => {
                                e.preventDefault();
                                statusForm.patch(route('requirements.status', requirement.id), { preserveScroll: true });
                            }}
                        >
                            <select
                                className="crm-input"
                                value={statusForm.data.status}
                                onChange={(e) => statusForm.setData('status', e.target.value)}
                            >
                                {Object.entries(crm.requirement_statuses || {}).map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>
                            <textarea
                                className="crm-input min-h-[100px]"
                                placeholder="Internal notes (staff only)"
                                value={statusForm.data.internal_notes}
                                onChange={(e) => statusForm.setData('internal_notes', e.target.value)}
                            />
                            <button type="submit" className="crm-btn-primary w-full" disabled={statusForm.processing}>Update</button>
                        </form>
                    </div>

                    <div className="crm-card space-y-3 p-5">
                        <h3 className="font-display text-sm font-semibold">Assign</h3>
                        <form
                            className="space-y-3"
                            onSubmit={(e) => {
                                e.preventDefault();
                                assignForm.patch(route('requirements.assign', requirement.id), { preserveScroll: true });
                            }}
                        >
                            <select
                                className="crm-input"
                                value={assignForm.data.assigned_to || ''}
                                onChange={(e) => assignForm.setData('assigned_to', e.target.value)}
                            >
                                <option value="">Unassigned</option>
                                {users.map((u) => (
                                    <option key={u.id} value={u.id}>{u.name}</option>
                                ))}
                            </select>
                            <button type="submit" className="crm-btn-secondary w-full" disabled={assignForm.processing}>Save</button>
                        </form>
                    </div>

                    <div className="crm-card space-y-2 p-5">
                        <h3 className="font-display text-sm font-semibold">Attachments</h3>
                        <ul className="space-y-1 text-sm">
                            {(requirement.attachments || []).map((a) => (
                                <li key={a.id}>
                                    <a href={route('requirements.attachments.download', a.id)} className="text-accent hover:text-accent-hover">
                                        {a.original_filename}
                                    </a>
                                </li>
                            ))}
                            {!requirement.attachments?.length && (
                                <li className="text-ink-muted">None</li>
                            )}
                        </ul>
                        <form
                            className="pt-2"
                            onSubmit={(e) => {
                                e.preventDefault();
                                const file = e.target.file.files[0];
                                if (!file) return;
                                router.post(route('requirements.attachments.store', requirement.id), { file }, {
                                    forceFormData: true,
                                    preserveScroll: true,
                                    onSuccess: () => { e.target.reset(); },
                                });
                            }}
                        >
                            <input type="file" name="file" className="crm-input text-xs" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
                            <button type="submit" className="crm-btn-secondary mt-2 w-full">Upload</button>
                        </form>
                    </div>
                </div>
            </div>

            <Drawer
                open={drawer === 'shortlist'}
                onClose={() => setDrawer(null)}
                title="Create shortlist"
                width="max-w-xl"
                footer={
                    <div className="flex justify-end gap-2">
                        <button type="button" className="crm-btn-secondary" onClick={() => setDrawer(null)}>Cancel</button>
                        <button type="submit" form="create-shortlist-form" className="crm-btn-primary" disabled={shortlistForm.processing || !selected.length}>
                            Create
                        </button>
                    </div>
                }
            >
                <form id="create-shortlist-form" className="space-y-4" onSubmit={submitShortlist}>
                    <div>
                        <label className="crm-label">Title</label>
                        <input className="crm-input" value={shortlistForm.data.title} onChange={(e) => shortlistForm.setData('title', e.target.value)} required />
                        <InputError message={shortlistForm.errors.title} className="mt-1" />
                    </div>
                    <div>
                        <label className="crm-label">Message to client</label>
                        <textarea className="crm-input min-h-[60px]" value={shortlistForm.data.message} onChange={(e) => shortlistForm.setData('message', e.target.value)} />
                    </div>
                    <div>
                        <label className="crm-label">Search influencers</label>
                        <input className="crm-input" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Name or username" />
                        <ul className="mt-2 max-h-40 space-y-1 overflow-y-auto text-sm">
                            {filteredInfluencers.map((inf) => {
                                const checked = selected.some((s) => s.influencer_id === inf.id);
                                return (
                                    <li key={inf.id}>
                                        <label className="flex cursor-pointer items-center justify-between gap-2 rounded-lg px-2 py-1.5 hover:bg-surface">
                                            <span className="inline-flex items-center gap-2">
                                                <input type="checkbox" checked={checked} onChange={() => toggleInfluencer(inf)} />
                                                <span>{inf.name}</span>
                                                {inf.instagram_username && <span className="text-ink-muted">@{inf.instagram_username}</span>}
                                            </span>
                                            <MoneyDisplay amount={inf.default_price} />
                                        </label>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                    {selected.length > 0 && (
                        <div className="space-y-3">
                            <h4 className="text-sm font-semibold">Selected ({selected.length})</h4>
                            {selected.map((s, idx) => (
                                <div key={s.influencer_id} className="rounded-lg border border-surface-border p-3 space-y-2">
                                    <p className="text-sm font-medium">{s.name}</p>
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        <div>
                                            <label className="crm-label">Internal price</label>
                                            <div className="crm-input bg-surface text-ink-muted"><MoneyDisplay amount={s.default_price} /></div>
                                        </div>
                                        <div>
                                            <label className="crm-label">Client price</label>
                                            <input
                                                type="number"
                                                className="crm-input"
                                                value={s.client_price}
                                                onChange={(e) => {
                                                    const next = [...selected];
                                                    next[idx] = { ...next[idx], client_price: e.target.value };
                                                    setSelected(next);
                                                }}
                                                required
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label className="crm-label">Note for client</label>
                                        <input
                                            className="crm-input"
                                            value={s.description}
                                            onChange={(e) => {
                                                const next = [...selected];
                                                next[idx] = { ...next[idx], description: e.target.value };
                                                setSelected(next);
                                            }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                    <InputError message={shortlistForm.errors.items} className="mt-1" />
                </form>
            </Drawer>
        </AuthenticatedLayout>
    );
}
