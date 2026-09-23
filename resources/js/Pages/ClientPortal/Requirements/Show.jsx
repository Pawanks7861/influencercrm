import { Head, router, useForm, usePage } from '@inertiajs/react';
import ClientPortalLayout from '@/Layouts/ClientPortalLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatusBadge from '@/Components/Crm/StatusBadge';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import { formatDate, formatDateTime, labelFromMap } from '@/lib/format';

export default function Show({ requirement }) {
    const { crm } = usePage().props;
    const messageForm = useForm({ message: '' });

    return (
        <ClientPortalLayout title={requirement.title}>
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
            />

            <div className="grid gap-4 lg:grid-cols-3">
                <div className="space-y-4 lg:col-span-2">
                    <div className="crm-card space-y-3 p-5">
                        <dl className="grid gap-3 sm:grid-cols-2 text-sm">
                            <div>
                                <dt className="text-ink-muted">Brand</dt>
                                <dd className="font-medium">{requirement.brand_name || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Submitted</dt>
                                <dd className="font-medium">{formatDate(requirement.submitted_at)}</dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Budget</dt>
                                <dd className="font-medium">
                                    <MoneyDisplay amount={requirement.budget_min} /> – <MoneyDisplay amount={requirement.budget_max} />
                                </dd>
                            </div>
                        </dl>
                        {requirement.description && (
                            <p className="whitespace-pre-wrap text-sm text-ink-soft">{requirement.description}</p>
                        )}
                    </div>

                    <div className="crm-card p-5">
                        <h3 className="mb-3 font-display text-sm font-semibold">Messages</h3>
                        <ul className="mb-4 space-y-3">
                            {(requirement.messages || []).map((m) => (
                                <li key={m.id} className="rounded-lg bg-surface px-3 py-2 text-sm">
                                    <div className="mb-1 flex justify-between text-xs text-ink-muted">
                                        <span>{m.sender_type === 'client' ? 'You' : 'Grovera Studio'} · {m.user_name}</span>
                                        <span>{formatDateTime(m.created_at)}</span>
                                    </div>
                                    <p className="whitespace-pre-wrap">{m.message}</p>
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
                                messageForm.post(route('client.requirements.messages.store', requirement.id), {
                                    preserveScroll: true,
                                    onSuccess: () => messageForm.reset(),
                                });
                            }}
                        >
                            <input
                                className="crm-input flex-1"
                                placeholder="Ask a question…"
                                value={messageForm.data.message}
                                onChange={(e) => messageForm.setData('message', e.target.value)}
                            />
                            <button type="submit" className="crm-btn-primary" disabled={messageForm.processing}>Send</button>
                        </form>
                    </div>
                </div>

                <div className="crm-card space-y-2 p-5">
                    <h3 className="font-display text-sm font-semibold">Attachments</h3>
                    <ul className="space-y-1 text-sm">
                        {(requirement.attachments || []).map((a) => (
                            <li key={a.id}>
                                <a href={route('client.requirements.attachments.download', a.id)} className="text-accent hover:text-accent-hover">
                                    {a.original_filename}
                                </a>
                            </li>
                        ))}
                        {!requirement.attachments?.length && <li className="text-ink-muted">None</li>}
                    </ul>
                    <form
                        className="pt-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            const file = e.target.file.files[0];
                            if (!file) return;
                            router.post(route('client.requirements.attachments.store', requirement.id), { file }, {
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
        </ClientPortalLayout>
    );
}
