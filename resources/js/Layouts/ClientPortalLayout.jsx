import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Bell,
    CheckCircle2,
    ClipboardList,
    LayoutDashboard,
    LogOut,
    Megaphone,
    Menu,
    UserRound,
    Users,
    X,
} from 'lucide-react';
import Toast from '@/Components/Crm/Toast';
import { cn, formatDateTime } from '@/lib/format';

const NAV = [
    { label: 'Dashboard', href: 'client.portal.dashboard', icon: LayoutDashboard, match: 'client.portal.dashboard' },
    { label: 'My Requirements', href: 'client.requirements.index', icon: ClipboardList, match: 'client.requirements.*' },
    { label: 'Influencer Shortlists', href: 'client.shortlists.index', icon: Users, match: 'client.shortlists.*' },
    { label: 'My Campaigns', href: 'client.campaigns.index', icon: Megaphone, match: 'client.campaigns.*' },
    { label: 'Approvals', href: 'client.approvals.index', icon: CheckCircle2, match: 'client.approvals.*' },
    { label: 'My Profile', href: 'client.profile.edit', icon: UserRound, match: 'client.profile.*' },
];

export default function ClientPortalLayout({ children, title }) {
    const { auth, settings, portalNotifications = [], unreadNotificationCount = 0 } = usePage().props;
    const [mobileOpen, setMobileOpen] = useState(false);
    const [notifOpen, setNotifOpen] = useState(false);
    const brand = settings?.studio_name || 'Grovera Studio';

    const SidebarContent = () => (
        <div className="flex h-full flex-col">
            <div className="flex items-center gap-3 border-b border-white/10 px-4 py-5">
                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-accent font-display text-sm font-bold text-white">
                    G
                </div>
                <div className="min-w-0">
                    <p className="truncate font-display text-sm font-semibold text-white">{brand}</p>
                    <p className="text-[11px] text-white/50">Client Portal</p>
                </div>
            </div>

            <nav className="flex-1 space-y-1 overflow-y-auto px-2 py-4">
                {NAV.map((item) => {
                    const active = route().current(item.match);
                    const Icon = item.icon;
                    return (
                        <Link
                            key={item.href}
                            href={route(item.href)}
                            onClick={() => setMobileOpen(false)}
                            className={cn(
                                'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                                active ? 'bg-white/10 text-white' : 'text-white/65 hover:bg-white/5 hover:text-white'
                            )}
                        >
                            <Icon className="h-4 w-4 shrink-0" strokeWidth={1.75} />
                            <span>{item.label}</span>
                        </Link>
                    );
                })}
            </nav>

            <div className="border-t border-white/10 p-4">
                <p className="truncate text-sm font-medium text-white">{auth.user?.name}</p>
                <p className="truncate text-[11px] text-white/50">{auth.user?.email}</p>
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-white/10 px-3 py-2 text-xs text-white/70 hover:bg-white/5 hover:text-white"
                >
                    <LogOut className="h-3.5 w-3.5" />
                    Log out
                </Link>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-surface">
            <aside className="fixed inset-y-0 left-0 z-40 hidden w-60 bg-ink-soft lg:block">
                <SidebarContent />
            </aside>

            {mobileOpen && (
                <div className="fixed inset-0 z-50 lg:hidden">
                    <button type="button" className="absolute inset-0 bg-ink/50" onClick={() => setMobileOpen(false)} />
                    <aside className="absolute inset-y-0 left-0 w-64 bg-ink-soft shadow-xl">
                        <div className="flex justify-end p-3">
                            <button type="button" onClick={() => setMobileOpen(false)} className="rounded-lg p-1.5 text-white/70 hover:bg-white/10">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <SidebarContent />
                    </aside>
                </div>
            )}

            <div className="lg:pl-60">
                <header className="sticky top-0 z-30 border-b border-surface-border bg-white/90 backdrop-blur">
                    <div className="flex h-14 items-center gap-3 px-4 sm:px-6">
                        <button
                            type="button"
                            className="rounded-lg p-2 text-ink-muted hover:bg-surface lg:hidden"
                            onClick={() => setMobileOpen(true)}
                        >
                            <Menu className="h-5 w-5" />
                        </button>
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-medium text-ink">{title || 'Portal'}</p>
                            <p className="text-[11px] text-ink-muted">{brand}</p>
                        </div>
                        <div className="relative">
                            <button
                                type="button"
                                className="relative rounded-lg p-2 text-ink-muted hover:bg-surface"
                                onClick={() => setNotifOpen((v) => !v)}
                            >
                                <Bell className="h-5 w-5" />
                                {unreadNotificationCount > 0 && (
                                    <span className="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent px-1 text-[10px] font-semibold text-white">
                                        {unreadNotificationCount > 9 ? '9+' : unreadNotificationCount}
                                    </span>
                                )}
                            </button>
                            {notifOpen && (
                                <div className="absolute right-0 mt-2 w-80 rounded-xl border border-surface-border bg-white shadow-lg">
                                    <div className="flex items-center justify-between border-b border-surface-border px-3 py-2">
                                        <p className="text-sm font-medium">Notifications</p>
                                        <button
                                            type="button"
                                            className="text-xs text-accent"
                                            onClick={() => router.post(route('client.notifications.read-all'), {}, { preserveScroll: true })}
                                        >
                                            Mark all read
                                        </button>
                                    </div>
                                    <ul className="max-h-80 overflow-y-auto">
                                        {portalNotifications.map((n) => (
                                            <li key={n.id}>
                                                <button
                                                    type="button"
                                                    className={cn(
                                                        'block w-full px-3 py-2 text-left text-sm hover:bg-surface',
                                                        !n.read_at && 'bg-accent/5'
                                                    )}
                                                    onClick={() => {
                                                        setNotifOpen(false);
                                                        router.post(route('client.notifications.read', n.id));
                                                    }}
                                                >
                                                    <p className="font-medium text-ink">{n.title}</p>
                                                    {n.body && <p className="text-xs text-ink-soft line-clamp-2">{n.body}</p>}
                                                    <p className="mt-0.5 text-[10px] text-ink-muted">{formatDateTime(n.created_at)}</p>
                                                </button>
                                            </li>
                                        ))}
                                        {!portalNotifications.length && (
                                            <li className="px-3 py-6 text-center text-xs text-ink-muted">No notifications</li>
                                        )}
                                    </ul>
                                </div>
                            )}
                        </div>
                    </div>
                </header>

                <main className="px-4 py-6 sm:px-6 lg:px-8">
                    {title && <h1 className="sr-only">{title}</h1>}
                    {children}
                </main>
            </div>

            <Toast />
        </div>
    );
}
