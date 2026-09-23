import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    ClipboardList,
    Edit3,
    KeyRound,
    NotebookPen,
    Phone,
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import InfluencerTypeBadge from '@/Components/Crm/InfluencerTypeBadge';
import StatusBadge from '@/Components/Crm/StatusBadge';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';
import ActivityTimeline from '@/Components/Crm/ActivityTimeline';
import NotesPanel from '@/Components/Crm/NotesPanel';
import PaymentStatusBadge from '@/Components/Crm/PaymentStatusBadge';
import AddActivity from '@/Components/Crm/Modals/AddActivity';
import AddFollowUp from '@/Components/Crm/Modals/AddFollowUp';
import AddNote from '@/Components/Crm/Modals/AddNote';
import ManageInfluencerLogin from '@/Components/Crm/Modals/ManageInfluencerLogin';
import { formatDate, formatDateTime, labelFromMap } from '@/lib/format';
import { cn } from '@/lib/format';

const TABS = ['Overview', 'Campaign History', 'Activities', 'Notes', 'Payments', 'Follow-ups'];

export default function Show({ influencer, canManageLogin = false }) {
    const { crm } = usePage().props;
    const [tab, setTab] = useState('Overview');
    const [modal, setModal] = useState(null);
    const hasLogin = Boolean(influencer.user_id || influencer.user);
    const loginEnabled = Boolean(influencer.login_enabled);

    return (
        <AuthenticatedLayout title={influencer.name}>
            <Head title={influencer.name} />

            <PageHeader
                title={influencer.name}
                description={
                    <span className="inline-flex flex-wrap items-center gap-2">
                        {influencer.instagram_username && (
                            <a
                                href={influencer.instagram_url || `https://instagram.com/${influencer.instagram_username}`}
                                target="_blank"
                                rel="noreferrer"
                                className="text-accent hover:text-accent-hover"
                            >
                                @{influencer.instagram_username}
                            </a>
                        )}
                        <InfluencerTypeBadge type={influencer.influencer_type} labels={crm.influencer_types} />
                        <StatusBadge status={influencer.status} />
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
                        <Link href={route('influencers.edit', influencer.id)} className="crm-btn-primary">
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
                        <h3 className="font-display text-sm font-semibold text-ink">Profile</h3>
                        <dl className="grid gap-3 sm:grid-cols-2 text-sm">
                            <div>
                                <dt className="text-ink-muted">Mobile</dt>
                                <dd className="font-medium text-ink">{influencer.mobile || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Email</dt>
                                <dd className="font-medium text-ink">{influencer.email || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Location</dt>
                                <dd className="font-medium text-ink">{influencer.location || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Default price</dt>
                                <dd><MoneyDisplay amount={influencer.default_price} /></dd>
                            </div>
                        </dl>
                        {(() => {
                            const socials = [
                                { label: 'Instagram', href: influencer.instagram_url || (influencer.instagram_username ? `https://instagram.com/${influencer.instagram_username}` : null) },
                                { label: 'YouTube', href: influencer.youtube_url },
                                { label: 'Facebook', href: influencer.facebook_url },
                                { label: 'LinkedIn', href: influencer.linkedin_url },
                                { label: 'X / Twitter', href: influencer.twitter_url },
                                { label: 'Other', href: influencer.other_social_url },
                            ].filter((s) => s.href);
                            if (!socials.length) return null;
                            return (
                                <div className="border-t border-surface-border pt-3">
                                    <p className="text-xs font-medium uppercase tracking-wide text-ink-muted">Social profiles</p>
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
                            );
                        })()}
                        {influencer.notes_summary && (
                            <div className="border-t border-surface-border pt-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-ink-muted">Notes summary</p>
                                <p className="mt-1 whitespace-pre-wrap text-sm text-ink-soft">{influencer.notes_summary}</p>
                            </div>
                        )}
                    </div>
                    <div className="crm-card p-5">
                        <h3 className="mb-3 font-display text-sm font-semibold text-ink">Quick stats</h3>
                        <ul className="space-y-2 text-sm text-ink-soft">
                            <li className="flex justify-between"><span>Campaigns</span><span className="font-medium text-ink">{influencer.campaign_influencers?.length || 0}</span></li>
                            <li className="flex justify-between"><span>Activities</span><span className="font-medium text-ink">{influencer.activities?.length || 0}</span></li>
                            <li className="flex justify-between"><span>Open follow-ups</span><span className="font-medium text-ink">{(influencer.follow_ups || []).filter((f) => f.status === 'pending' || f.status?.value === 'pending').length}</span></li>
                        </ul>
                    </div>

                    {canManageLogin && (
                        <div className="crm-card space-y-3 p-5 lg:col-span-3">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 className="font-display text-sm font-semibold text-ink">Portal login</h3>
                                    <p className="text-xs text-ink-muted">Create or manage this influencer&apos;s portal access. Passwords are never displayed.</p>
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
                                                if (confirm('Disable portal login for this influencer?')) {
                                                    router.post(route('influencers.login.disable', influencer.id), {}, { preserveScroll: true });
                                                }
                                            }}
                                        >
                                            Disable Login
                                        </button>
                                    ) : (
                                        <button
                                            type="button"
                                            className="crm-btn-primary"
                                            onClick={() => router.post(route('influencers.login.enable', influencer.id), {}, { preserveScroll: true })}
                                        >
                                            Re-enable Login
                                        </button>
                                    )}
                                </div>
                            )}
                            {hasLogin && influencer.user?.email && (
                                <p className="text-xs text-ink-muted">Login email: {influencer.user.email}</p>
                            )}
                        </div>
                    )}
                </div>
            )}

            {tab === 'Campaign History' && (
                <div className="crm-card overflow-hidden">
                    <table className="min-w-full text-sm">
                        <thead className="bg-surface text-xs uppercase text-ink-muted">
                            <tr>
                                <th className="px-4 py-3 text-left">Campaign</th>
                                <th className="px-4 py-3 text-left">Client</th>
                                <th className="px-4 py-3 text-left">Status</th>
                                <th className="px-4 py-3 text-right">Cost</th>
                                <th className="px-4 py-3 text-right">Final</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(influencer.campaign_influencers || []).map((ci) => (
                                <tr key={ci.id} className="border-t border-surface-border">
                                    <td className="px-4 py-3">
                                        {ci.campaign ? (
                                            <Link href={route('campaigns.show', ci.campaign.id)} className="font-medium text-accent hover:text-accent-hover">
                                                {ci.campaign.campaign_name}
                                            </Link>
                                        ) : '—'}
                                    </td>
                                    <td className="px-4 py-3 text-ink-soft">{ci.campaign?.client?.name || '—'}</td>
                                    <td className="px-4 py-3">
                                        <StatusBadge status={ci.status} labels={crm.collaboration_statuses} />
                                    </td>
                                    <td className="px-4 py-3 text-right"><MoneyDisplay amount={ci.influencer_cost} /></td>
                                    <td className="px-4 py-3 text-right"><MoneyDisplay amount={ci.final_amount} /></td>
                                </tr>
                            ))}
                            {!influencer.campaign_influencers?.length && (
                                <tr><td colSpan={5} className="px-4 py-8 text-center text-ink-muted">No campaign history</td></tr>
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
                    <ActivityTimeline activities={influencer.activities || []} activityTypes={crm.activity_types} />
                </div>
            )}

            {tab === 'Notes' && (
                <div className="crm-card p-5">
                    <NotesPanel notes={influencer.notes || []} onAdd={() => setModal('note')} />
                </div>
            )}

            {tab === 'Payments' && (
                <div className="crm-card overflow-hidden">
                    <table className="min-w-full text-sm">
                        <thead className="bg-surface text-xs uppercase text-ink-muted">
                            <tr>
                                <th className="px-4 py-3 text-left">Date</th>
                                <th className="px-4 py-3 text-right">Amount</th>
                                <th className="px-4 py-3 text-left">Method</th>
                                <th className="px-4 py-3 text-left">Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(influencer.payments || []).map((p) => (
                                <tr key={p.id} className="border-t border-surface-border">
                                    <td className="px-4 py-3">{formatDate(p.payment_date)}</td>
                                    <td className="px-4 py-3 text-right"><MoneyDisplay amount={p.amount} /></td>
                                    <td className="px-4 py-3">{labelFromMap(crm.payment_methods, p.payment_method)}</td>
                                    <td className="px-4 py-3 text-ink-muted">{p.transaction_reference || '—'}</td>
                                </tr>
                            ))}
                            {!influencer.payments?.length && (
                                <tr><td colSpan={4} className="px-4 py-8 text-center text-ink-muted">No payments recorded</td></tr>
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
                                <th className="px-4 py-3 text-left">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(influencer.follow_ups || []).map((f) => (
                                <tr key={f.id} className="border-t border-surface-border">
                                    <td className="px-4 py-3">{formatDate(f.follow_up_date)}{f.follow_up_time ? ` ${String(f.follow_up_time).slice(0, 5)}` : ''}</td>
                                    <td className="px-4 py-3"><StatusBadge status={f.status} /></td>
                                    <td className="px-4 py-3 text-ink-soft">{f.note || '—'}</td>
                                </tr>
                            ))}
                            {!influencer.follow_ups?.length && (
                                <tr><td colSpan={3} className="px-4 py-8 text-center text-ink-muted">No follow-ups</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            {modal === 'activity' && (
                <AddActivity open influencerId={influencer.id} onClose={() => setModal(null)} />
            )}
            {modal === 'followup' && (
                <AddFollowUp open influencerId={influencer.id} onClose={() => setModal(null)} />
            )}
            {modal === 'note' && (
                <AddNote open notableType="influencer" notableId={influencer.id} onClose={() => setModal(null)} />
            )}
            {(modal === 'create-login' || modal === 'reset-password') && (
                <ManageInfluencerLogin
                    open
                    influencer={influencer}
                    mode={modal === 'create-login' ? 'create' : 'reset'}
                    onClose={() => setModal(null)}
                />
            )}
        </AuthenticatedLayout>
    );
}
