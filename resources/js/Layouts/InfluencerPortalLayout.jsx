import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    ClipboardList,
    CreditCard,
    LayoutDashboard,
    LogOut,
    Megaphone,
    Menu,
    UserRound,
    X,
} from 'lucide-react';
import Toast from '@/Components/Crm/Toast';
import { cn } from '@/lib/format';

const NAV = [
    { label: 'Dashboard', href: 'influencer.portal.dashboard', icon: LayoutDashboard, match: 'influencer.portal.dashboard' },
    { label: 'My Campaigns', href: 'influencer.campaigns.index', icon: Megaphone, match: 'influencer.campaigns.*' },
    { label: 'My Deliverables', href: 'influencer.deliverables.index', icon: ClipboardList, match: 'influencer.deliverables.*' },
    { label: 'My Payments', href: 'influencer.payments.index', icon: CreditCard, match: 'influencer.payments.*' },
    { label: 'My Profile', href: 'influencer.profile.edit', icon: UserRound, match: 'influencer.profile.*' },
];

export default function InfluencerPortalLayout({ children, title }) {
    const { auth, settings } = usePage().props;
    const [mobileOpen, setMobileOpen] = useState(false);
    const brand = settings?.studio_name || 'Grovera Studio';

    const SidebarContent = () => (
        <div className="flex h-full flex-col">
            <div className="flex items-center gap-3 border-b border-white/10 px-4 py-5">
                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-accent font-display text-sm font-bold text-white">
                    G
                </div>
                <div className="min-w-0">
                    <p className="truncate font-display text-sm font-semibold text-white">{brand}</p>
                    <p className="text-[11px] text-white/50">Influencer Portal</p>
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
                        <div>
                            <p className="text-sm font-medium text-ink">{title || 'Portal'}</p>
                            <p className="text-[11px] text-ink-muted">{brand}</p>
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
