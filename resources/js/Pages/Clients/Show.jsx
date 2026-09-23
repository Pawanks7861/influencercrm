import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ClipboardList, Edit3, KeyRound, Megaphone, NotebookPen, Phone } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatusBadge from '@/Components/Crm/StatusBadge';
import NotesPanel from '@/Components/Crm/NotesPanel';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import AddFollowUp from '@/Components/Crm/Modals/AddFollowUp';
import AddNote from '@/Components/Crm/Modals/AddNote';
import ManageClientLogin from '@/Components/Crm/Modals/ManageClientLogin';
import Drawer from '@/Components/Crm/Drawer';
import InputError from '@/Components/InputError';
import { formatDate, formatDateTime, labelFromMap, cn } from '@/lib/format';
import { useForm } from '@inertiajs/react';

const TABS = ['Overview', 'Campaigns', 'Follow-ups', 'Activities', 'Notes'];

export default function Show({ client, users = [], clientActivityTypes = {}, clientNotes = [], canManageLogin = false }) {
    const { crm } = usePage().props;
    const [tab, setTab] = useState('Overview');
    const [modal, setModal] = useState(null);
    const hasLogin = Boolean(client.user_id || client.user);
    const loginEnabled = Boolean(client.login_enabled);

    const activityForm = useForm({
        activity_type: 'called_client',
        activity_at: new Date().toISOString().slice(0, 16),
        note: '',
    });

    const socials = [
        { label: 'Instagram', href: client.instagram_url },
        { label: 'Facebook', href: client.facebook_url },
        { label: 'LinkedIn', href: client.linkedin_url },
        { label: 'YouTube', href: client.youtube_url },
        { label: 'X / Twitter', href: client.twitter_url },
        { label: 'Website', href: client.website },
    ].filter((s) => s.href);

    const title = client.company_name || client.name;

    return (
        <AuthenticatedLayout title={title}>
            <Head title={title} />

            <PageHeader
                title={title}
                description={
                    <span className="inline-flex flex-wrap items-center gap-2">
                        {client.contact_person && <span>{client.contact_person}</span>}
                        <StatusBadge status={client.status} labels={{ lead: 'Lead', active: 'Active', inactive: 'Inactive' }} />
                    </span>
                }
                actions={
                    <>
                        <button type="button" className="crm-btn-secondary" onClick={() => setModal('activity')}>
                            <Phone className="h-4 w-4" /> Activity
                        </button>
                        <button type="button" className="crm-btn-secondary" onClick={() => setModal('followup')}>
                            <ClipboardList className="h-4 w-4" /> Follow-up
                        </button>
                        <button type="button" className="crm-btn-secondary" onClick={() => setModal('note')}>
                            <NotebookPen className="h-4 w-4" /> Note
                        </button>
                        <Link href={route('campaigns.create', { client_id: client.id })} className="crm-btn-secondary">
                            <Megaphone className="h-4 w-4" /> Create Campaign
                        </Link>
                        <Link href={route('clients.edit', client.id)} className="crm-btn-primary">
                            <Edit3 className="h-4 w-4" /> Edit
                        </Link>
                    </>
                }
            />

            <div className="mb-6 flex gap-1 overflow-x-auto border-b border-surface-border">
                {TABS.map((name) => (
                    <button
                        key={name}
                        type="button"
                        onClick={() => setTab(name)}
                        className={cn(
                            'whitespace-nowrap px-3 py-2.5 text-sm font-medium border-b-2 -mb-px transition',
                            tab === name ? 'border-accent text-accent' : 'border-transparent text-ink-muted hover:text-ink'
                        )}
                    >
                        {name}
                    </button>
                ))}
            </div>

            {tab === 'Overview' && (
                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="crm-card space-y-3 p-5 lg:col-span-2">
                        <h3 className="font-display text-sm font-semibold text-ink">Company information</h3>
                        <dl className="grid gap-3 sm:grid-cols-2 text-sm">
                            <div>
                                <dt className="text-ink-muted">Contact person</dt>
                                <dd className="font-medium text-ink">{client.contact_person || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Mobile</dt>
                                <dd className="font-medium text-ink">{client.mobile || client.phone || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Email</dt>
                                <dd className="font-medium text-ink">{client.email || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Website</dt>
                                <dd className="font-medium text-ink">
                                    {client.website ? (
                                        <a href={client.website} target="_blank" rel="noreferrer" className="text-accent hover:text-accent-hover">
                                            {client.website}
                                        </a>
                                    ) : '—'}
                                </dd>
                            </div>
                            <div className="sm:col-span-2">
                                <dt className="text-ink-muted">Address</dt>
                                <dd className="font-medium text-ink whitespace-pre-wrap">{client.address || '—'}</dd>
                            </div>
                        </dl>
                        {socials.length > 0 && (
                            <div className="border-t border-surface-border pt-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-ink-muted">Social media</p>
                                <ul className="mt-2 flex flex-wrap gap-2">
                                    {socials.map((s) => (
                                        <li key={s.label}>
                                            <a
                                                href={s.href}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="inline-flex rounded-lg border border-surface-border px-2.5 py-1 text-xs font-medium text-accent hover:bg-surface"
                                            >
                                                {s.label}
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                        {client.notes && (
                            <div className="border-t border-surface-border pt-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-ink-muted">Notes</p>
                                <p className="mt-1 whitespace-pre-wrap text-sm text-ink-soft">{client.notes}</p>
                            </div>
                        )}
                    </div>
                    <div className="crm-card p-5">
                        <h3 className="mb-3 font-display text-sm font-semibold text-ink">Quick stats</h3>
                        <ul className="space-y-2 text-sm text-ink-soft">
                            <li className="flex justify-between"><span>Campaigns</span><span className="font-medium text-ink">{client.campaigns?.length || 0}</span></li>
                            <li className="flex justify-between"><span>Follow-ups</span><span className="font-medium text-ink">{client.follow_ups?.length || 0}</span></li>
                            <li className="flex justify-between"><span>Activities</span><span className="font-medium text-ink">{client.activities?.length || 0}</span></li>
                        </ul>
                    </div>

                    {canManageLogin && (
                        <div className="crm-card space-y-3 p-5 lg:col-span-3">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 className="font-display text-sm font-semibold text-ink">Portal login</h3>
                                    <p className="text-xs text-ink-muted">Create or manage this client&apos;s portal access. Passwords are never displayed.</p>
                                </div>
                                {hasLogin && (
                                    <span
                                        className={cn(
                                            'inline-flex rounded-md px-2 py-1 text-xs font-medium',
                                            loginEnabled ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'
                                        )}
                                    >
                                        {loginEnabled ? 'Login Enabled' : 'Login Disabled'}
                                    </span>
                                )}
                            </div>

                            {!hasLogin ? (
                                <button type="button" className="crm-btn-primary" onClick={() => setModal('create-login')}>
                                    <KeyRound className="h-4 w-4" /> Create Login
                                </button>
                            ) : (
                                <div className="flex flex-wrap gap-2">
                                    <button type="button" className="crm-btn-secondary" onClick={() => setModal('reset-password')}>
                                        Reset Password
                                    </button>
                                    {loginEnabled ? (
                                        <button
                                            type="button"
                                            className="crm-btn-secondary"
                                            onClick={() => {
                                                if (confirm('Disable portal login for this client?')) {
                                                    router.post(route('clients.login.disable', client.id), {}, { preserveScroll: true });
                                                }
                                            }}
                                        >
                                            Disable Login
                                        </button>
                                    ) : (
                                        <button
                                            type="button"
                                            className="crm-btn-secondary"
                                            onClick={() => router.post(route('clients.login.enable', client.id), {}, { preserveScroll: true })}
                                        >
                                            Enable Login
                                        </button>
                                    )}
                                </div>
                            )}
                            {hasLogin && client.user?.email && (
                                <p className="text-xs text-ink-muted">Login email: {client.user.email}</p>
                            )}
                        </div>
                    )}
                </div>
            )}

            {tab === 'Campaigns' && (
                <div className="crm-card overflow-hidden">
                    <div className="flex justify-end border-b border-surface-border px-4 py-3">
                        <Link href={route('campaigns.create', { client_id: client.id })} className="crm-btn-primary">
                            Create campaign
                        </Link>
                    </div>
                    <table className="min-w-full text-sm">
                        <thead className="bg-surface text-xs uppercase text-ink-muted">
                            <tr>
                                <th className="px-4 py-3 text-left">Campaign</th>
                                <th className="px-4 py-3 text-left">Type</th>
                                <th className="px-4 py-3 text-left">Start</th>
                                <th className="px-4 py-3 text-left">Deadline</th>
                                <th className="px-4 py-3 text-left">Status</th>
                                <th className="px-4 py-3 text-right">Budget</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(client.campaigns || []).map((c) => (
                                <tr key={c.id} className="border-t border-surface-border">
                                    <td className="px-4 py-3">
                                        <Link href={route('campaigns.show', c.id)} className="font-medium text-accent hover:text-accent-hover">
                                            {c.campaign_name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3 text-ink-soft">{labelFromMap(crm.campaign_types, c.campaign_type) || c.campaign_type || '—'}</td>
                                    <td className="px-4 py-3">{formatDate(c.start_date)}</td>
                                    <td className="px-4 py-3">{formatDate(c.deadline)}</td>
                                    <td className="px-4 py-3"><StatusBadge status={c.status} labels={crm.campaign_statuses} /></td>
                                    <td className="px-4 py-3 text-right"><MoneyDisplay amount={c.campaign_budget} /></td>
                                </tr>
                            ))}
                            {!client.campaigns?.length && (
                                <tr><td colSpan={6} className="px-4 py-8 text-center text-ink-muted">No campaigns</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            {tab === 'Follow-ups' && (
                <div className="crm-card overflow-hidden">
                    <div className="flex justify-end border-b border-surface-border px-4 py-3">
                        <button type="button" className="crm-btn-primary" onClick={() => setModal('followup')}>Schedule follow-up</button>
                    </div>
                    <table className="min-w-full text-sm">
                        <thead className="bg-surface text-xs uppercase text-ink-muted">
                            <tr>
                                <th className="px-4 py-3 text-left">Date</th>
                                <th className="px-4 py-3 text-left">Status</th>
                                <th className="px-4 py-3 text-left">Assignee</th>
                                <th className="px-4 py-3 text-left">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(client.follow_ups || []).map((f) => (
                                <tr key={f.id} className="border-t border-surface-border">
                                    <td className="px-4 py-3">{formatDate(f.follow_up_date)}{f.follow_up_time ? ` ${String(f.follow_up_time).slice(0, 5)}` : ''}</td>
                                    <td className="px-4 py-3"><StatusBadge status={f.status} /></td>
                                    <td className="px-4 py-3 text-ink-soft">{f.assignee?.name || '—'}</td>
                                    <td className="px-4 py-3 text-ink-soft">{f.note || '—'}</td>
                                </tr>
                            ))}
                            {!client.follow_ups?.length && (
                                <tr><td colSpan={4} className="px-4 py-8 text-center text-ink-muted">No follow-ups</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            {tab === 'Activities' && (
                <div className="crm-card p-5">
                    <div className="mb-4 flex justify-end">
                        <button type="button" className="crm-btn-primary" onClick={() => setModal('activity')}>Log activity</button>
                    </div>
                    <ul className="space-y-3">
                        {(client.activities || []).map((a) => (
                            <li key={a.id} className="rounded-lg border border-surface-border px-4 py-3 text-sm">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <span className="font-medium text-ink">{labelFromMap(clientActivityTypes, a.activity_type) || a.activity_type}</span>
                                    <span className="text-xs text-ink-muted">{formatDateTime(a.activity_at)}</span>
                                </div>
                                {a.note && <p className="mt-1 text-ink-soft">{a.note}</p>}
                                {a.creator && <p className="mt-1 text-xs text-ink-muted">by {a.creator.name}</p>}
                            </li>
                        ))}
                        {!client.activities?.length && (
                            <li className="py-8 text-center text-ink-muted">No activities yet</li>
                        )}
                    </ul>
                </div>
            )}

            {tab === 'Notes' && (
                <div className="crm-card p-5">
                    <NotesPanel notes={clientNotes} onAdd={() => setModal('note')} />
                </div>
            )}

            {modal === 'followup' && (
                <AddFollowUp open clientId={client.id} users={users} onClose={() => setModal(null)} />
            )}
            {modal === 'note' && (
                <AddNote open notableType="client" notableId={client.id} onClose={() => setModal(null)} />
            )}
            {(modal === 'create-login' || modal === 'reset-password') && (
                <ManageClientLogin
                    open
                    client={client}
                    mode={modal === 'create-login' ? 'create' : 'reset'}
                    onClose={() => setModal(null)}
                />
            )}
            {modal === 'activity' && (
                <Drawer
                    open
                    onClose={() => setModal(null)}
                    title="Log client activity"
                    footer={
                        <div className="flex justify-end gap-2">
                            <button type="button" className="crm-btn-secondary" onClick={() => setModal(null)}>Cancel</button>
                            <button
                                type="submit"
                                form="client-activity-form"
                                className="crm-btn-primary"
                                disabled={activityForm.processing}
                            >
                                Save
                            </button>
                        </div>
                    }
                >
                    <form
                        id="client-activity-form"
                        className="space-y-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            activityForm.post(route('clients.activities.store', client.id), {
                                preserveScroll: true,
                                onSuccess: () => setModal(null),
                            });
                        }}
                    >
                        <div>
                            <label className="crm-label">Type</label>
                            <select
                                className="crm-input"
                                value={activityForm.data.activity_type}
                                onChange={(e) => activityForm.setData('activity_type', e.target.value)}
                            >
                                {Object.entries(clientActivityTypes).map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>
                            <InputError message={activityForm.errors.activity_type} className="mt-1" />
                        </div>
                        <div>
                            <label className="crm-label">When</label>
                            <input
                                type="datetime-local"
                                className="crm-input"
                                value={activityForm.data.activity_at}
                                onChange={(e) => activityForm.setData('activity_at', e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="crm-label">Note</label>
                            <textarea
                                className="crm-input min-h-[100px]"
                                value={activityForm.data.note}
                                onChange={(e) => activityForm.setData('note', e.target.value)}
                            />
                        </div>
                    </form>
                </Drawer>
            )}
        </AuthenticatedLayout>
    );
}
