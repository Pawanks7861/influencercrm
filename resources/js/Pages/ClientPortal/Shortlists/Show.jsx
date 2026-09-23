import { Head, useForm, usePage } from '@inertiajs/react';
import ClientPortalLayout from '@/Layouts/ClientPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatusBadge from '@/Components/Crm/StatusBadge';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import { labelFromMap } from '@/lib/format';

const RESPONSES = [
    { value: 'interested', label: 'Interested' },
    { value: 'selected', label: 'Select' },
    { value: 'need_more_details', label: 'Need details' },
    { value: 'rejected', label: 'Reject' },
];

export default function Show({ shortlist }) {
    const { crm } = usePage().props;
    const expired = Boolean(shortlist.is_expired);
    const canRespond = shortlist.can_respond !== false && !expired;

    return (
        <ClientPortalLayout title={shortlist.title}>
            <Head title={shortlist.title} />
            <PageHeader
                title={shortlist.title}
                description={
                    <span className="inline-flex flex-wrap items-center gap-2">
                        <StatusBadge status={shortlist.status} labels={crm.shortlist_statuses} />
                        {shortlist.requirement && (
                            <span className="text-sm text-ink-muted">
                                {shortlist.requirement.requirement_number} · {shortlist.requirement.title}
                            </span>
                        )}
                    </span>
                }
            />

            {expired && (
                <div className="crm-card mb-4 border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    This shortlist has expired ({shortlist.expires_at}). You can view the options below, but responses are closed.
                </div>
            )}

            {shortlist.message && (
                <div className="crm-card mb-4 p-4 text-sm text-ink-soft">{shortlist.message}</div>
            )}

            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                {(shortlist.items || []).map((item) => (
                    <ShortlistCard key={item.id} item={item} crm={crm} canRespond={canRespond} />
                ))}
            </div>
        </ClientPortalLayout>
    );
}

function ShortlistCard({ item, crm, canRespond }) {
    const form = useForm({
        status: item.status === 'pending' ? 'interested' : item.status,
        client_remark: item.client_remark || '',
    });

    return (
        <div className="crm-card flex flex-col p-4">
            <div className="mb-3 flex items-start justify-between gap-2">
                <div>
                    {item.show_name !== false && item.display_name && (
                        <p className="font-medium text-ink">{item.display_name}</p>
                    )}
                    {item.show_instagram && item.instagram_username && (
                        <a
                            href={item.instagram_url || `https://instagram.com/${item.instagram_username}`}
                            target="_blank"
                            rel="noreferrer"
                            className="text-xs text-accent hover:text-accent-hover"
                        >
                            @{item.instagram_username}
                        </a>
                    )}
                    {item.show_location && item.location && (
                        <p className="text-xs text-ink-muted">{item.location}</p>
                    )}
                    {item.show_type && item.influencer_type && (
                        <p className="text-xs text-ink-muted">{labelFromMap(crm.influencer_types, item.influencer_type)}</p>
                    )}
                </div>
                <StatusBadge status={item.status} labels={crm.shortlist_item_statuses} />
            </div>

            {item.show_price && (
                <p className="mb-2 text-lg font-semibold text-ink">
                    <MoneyDisplay amount={item.client_price} />
                </p>
            )}

            {item.show_note && item.description && (
                <p className="mb-3 text-sm text-ink-soft">{item.description}</p>
            )}

            {canRespond ? (
                <form
                    className="mt-auto space-y-2 border-t border-surface-border pt-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(route('client.shortlists.respond', item.id), { preserveScroll: true });
                    }}
                >
                    <div className="grid grid-cols-2 gap-1.5">
                        {RESPONSES.map((r) => (
                            <button
                                key={r.value}
                                type="button"
                                className={`rounded-lg border px-2 py-1.5 text-xs font-medium ${
                                    form.data.status === r.value
                                        ? 'border-accent bg-accent/10 text-accent'
                                        : 'border-surface-border text-ink-soft hover:bg-surface'
                                }`}
                                onClick={() => form.setData('status', r.value)}
                            >
                                {r.label}
                            </button>
                        ))}
                    </div>
                    <textarea
                        className="crm-input min-h-[60px] text-sm"
                        placeholder="Optional remark"
                        value={form.data.client_remark}
                        onChange={(e) => form.setData('client_remark', e.target.value)}
                    />
                    <button type="submit" className="crm-btn-primary w-full" disabled={form.processing}>
                        Save response
                    </button>
                </form>
            ) : (
                <p className="mt-auto border-t border-surface-border pt-3 text-xs text-ink-muted">
                    Responses are closed for this shortlist.
                </p>
            )}
        </div>
    );
}
