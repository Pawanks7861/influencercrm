import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Bell,
    Building2,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    CreditCard,
    LayoutDashboard,
    LogOut,
    Menu,
    Search,
    Settings,
    Users,
    Megaphone,
    BarChart3,
    X,
} from 'lucide-react';
import Toast from '@/Components/Crm/Toast';
import { cn } from '@/lib/format';

const NAV = [
    { label: 'Dashboard', href: 'dashboard', icon: LayoutDashboard, match: 'dashboard' },
    { label: 'Influencers', href: 'influencers.index', icon: Users, match: 'influencers.*' },
    { label: 'Clients', href: 'clients.index', icon: Building2, match: 'clients.*' },
    { label: 'Requirements', href: 'requirements.index', icon: ClipboardList, match: 'requirements.*' },
    { label: 'Campaigns', href: 'campaigns.index', icon: Megaphone, match: 'campaigns.*' },
    { label: 'Follow-ups', href: 'follow-ups.index', icon: ClipboardList, match: 'follow-ups.*' },
    { label: 'Payments', href: 'payments.index', icon: CreditCard, match: 'payments.*' },
    { label: 'Reports', href: 'reports.index', icon: BarChart3, match: 'reports.*' },
    { label: 'Settings', href: 'settings.index', icon: Settings, match: 'settings.*' },
];
export default function AuthenticatedLayout({ children, title }) {
    const { auth, followUps, settings } = usePage().props;
    const [collapsed, setCollapsed] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);
    const [search, setSearch] = useState('');

    useEffect(() => {
        const stored = localStorage.getItem('crm_sidebar_collapsed');
        if (stored === '1') setCollapsed(true);
    }, []);

    const toggleCollapse = () => {
        setCollapsed((prev) => {
            localStorage.setItem('crm_sidebar_collapsed', !prev ? '1' : '0');
            return !prev;
        });
    };

    const submitSearch = (e) => {
        e.preventDefault();
        router.get(route('influencers.index'), { search }, { preserveState: true });
    };

    const brand = settings?.studio_name || 'Grovera Studio';
    const todayCount = followUps?.today ?? 0;
    const overdueCount = followUps?.overdue ?? 0;

    const SidebarContent = ({ mobile = false }) => (
        <div className="flex h-full flex-col">
            <div className={cn('flex items-center gap-3 border-b border-white/10 px-4 py-5', collapsed && !mobile && 'justify-center px-2')}>
                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-accent font-display text-sm font-bold text-white">
                    G
                </div>
                {(!collapsed || mobile) && (
                    <div className="min-w-0">
                        <p className="truncate font-display text-sm font-semibold text-white">{brand}</p>
                        <p className="text-[11px] text-white/50">Influencer CRM</p>
                    </div>
                )}
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
                                active ? 'bg-white/10 text-white' : 'text-white/65 hover:bg-white/5 hover:text-white',
                                collapsed && !mobile && 'justify-center px-2'
                            )}
                            title={item.label}
                        >
                            <Icon className="h-4 w-4 shrink-0" strokeWidth={1.75} />
                            {(!collapsed || mobile) && <span>{item.label}</span>}
                        </Link>
                    );
                })}
            </nav>

            {!mobile && (
                <button
                    type="button"
                    onClick={toggleCollapse}
                    className="m-3 flex items-center justify-center gap-2 rounded-lg border border-white/10 px-3 py-2 text-xs text-white/60 hover:bg-white/5 hover:text-white"
                >
                    {collapsed ? <ChevronRight className="h-4 w-4" /> : <ChevronLeft className="h-4 w-4" />}
                    {!collapsed && 'Collapse'}
                </button>
            )}
        </div>
    );

    return (
        <div className="min-h-screen bg-surface">
            <aside
                className={cn(
                    'fixed inset-y-0 left-0 z-40 hidden bg-ink-soft transition-all duration-200 lg:block',
                    collapsed ? 'w-[72px]' : 'w-60'
                )}
            >
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
                        <SidebarContent mobile />
                    </aside>
                </div>
            )}

            <div className={cn('transition-all duration-200', collapsed ? 'lg:pl-[72px]' : 'lg:pl-60')}>
                <header className="sticky top-0 z-30 border-b border-surface-border bg-white/90 backdrop-blur">
                    <div className="flex h-14 items-center gap-3 px-4 sm:px-6">
                        <button
                            type="button"
                            className="rounded-lg p-2 text-ink-muted hover:bg-surface lg:hidden"
                            onClick={() => setMobileOpen(true)}
                        >
                            <Menu className="h-5 w-5" />
                        </button>

                        <form onSubmit={submitSearch} className="relative max-w-md flex-1">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" />
                            <input
                                type="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search influencers…"
                                className="w-full rounded-lg border-surface-border bg-surface py-2 pl-9 pr-3 text-sm focus:border-accent focus:ring-accent/30"
                            />
                        </form>

                        <div className="ml-auto flex items-center gap-2 sm:gap-3">
                            <Link
                                href={route('follow-ups.index', { filter: 'overdue' })}
                                className="relative hidden items-center gap-2 rounded-lg border border-surface-border px-2.5 py-1.5 text-xs font-medium text-ink-soft hover:bg-surface sm:inline-flex"
                                title="Follow-ups"
                            >
                                <Bell className="h-3.5 w-3.5" />
                                <span className="text-ink-muted">Today {todayCount}</span>
                                {overdueCount > 0 && (
                                    <span className="rounded-md bg-red-50 px-1.5 py-0.5 text-red-700">Overdue {overdueCount}</span>
                                )}
                            </Link>

                            <div className="hidden text-right sm:block">
                                <p className="text-sm font-medium text-ink">{auth.user?.name}</p>
                                <p className="text-[11px] text-ink-muted">{auth.user?.roles?.[0] || 'Team'}</p>
                            </div>

                            <Link href={route('profile.edit')} className="crm-btn-secondary !px-2.5 !py-1.5 text-xs">
                                Profile
                            </Link>
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="rounded-lg p-2 text-ink-muted hover:bg-surface hover:text-ink"
                                title="Log out"
                            >
                                <LogOut className="h-4 w-4" />
                            </Link>
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
