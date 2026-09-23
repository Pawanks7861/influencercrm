import { Head, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import InputError from '@/Components/InputError';

export default function SettingsIndex({ settings }) {
    const { auth, crm } = usePage().props;
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        studio_name: settings.studio_name || 'Grovera Studio',
        currency: settings.currency || 'INR',
        currency_symbol: settings.currency_symbol || '₹',
        date_format: settings.date_format || 'd M Y',
        timezone: settings.timezone || 'Asia/Kolkata',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('settings.update'));
    };

    return (
        <AuthenticatedLayout title="Settings">
            <Head title="Settings" />
            <PageHeader title="Settings" description="Studio preferences and team" />

            <div className="grid gap-6 lg:grid-cols-3">
                <form onSubmit={submit} className="crm-card space-y-4 p-5 lg:col-span-2">
                    <h3 className="font-display text-sm font-semibold text-ink">General</h3>
                    <div>
                        <label className="crm-label">Studio name</label>
                        <input className="crm-input" value={data.studio_name} onChange={(e) => setData('studio_name', e.target.value)} />
                        <InputError message={errors.studio_name} className="mt-1" />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label className="crm-label">Currency</label>
                            <input className="crm-input" value={data.currency} onChange={(e) => setData('currency', e.target.value)} />
                        </div>
                        <div>
                            <label className="crm-label">Currency symbol</label>
                            <input className="crm-input" value={data.currency_symbol} onChange={(e) => setData('currency_symbol', e.target.value)} />
                        </div>
                        <div>
                            <label className="crm-label">Date format</label>
                            <input className="crm-input" value={data.date_format} onChange={(e) => setData('date_format', e.target.value)} />
                        </div>
                        <div>
                            <label className="crm-label">Timezone</label>
                            <input className="crm-input" value={data.timezone} onChange={(e) => setData('timezone', e.target.value)} />
                        </div>
                    </div>
                    <div className="flex items-center gap-3 border-t border-surface-border pt-4">
                        <button type="submit" className="crm-btn-primary" disabled={processing}>Save settings</button>
                        {recentlySuccessful && <span className="text-sm text-accent">Saved</span>}
                    </div>
                </form>

                <div className="space-y-6">
                    <div className="crm-card p-5">
                        <h3 className="font-display text-sm font-semibold text-ink">Team</h3>
                        <div className="mt-3 space-y-2 text-sm">
                            <div className="rounded-lg border border-surface-border px-3 py-2">
                                <p className="font-medium text-ink">{auth.user?.name}</p>
                                <p className="text-xs text-ink-muted">{auth.user?.email}</p>
                                <p className="mt-1 text-xs text-ink-muted">
                                    Roles: {(auth.user?.roles || []).join(', ') || '—'}
                                </p>
                            </div>
                            <p className="text-xs text-ink-muted">User management is handled via seeders / admin provisioning.</p>
                        </div>
                    </div>

                    <div className="crm-card p-5">
                        <h3 className="font-display text-sm font-semibold text-ink">Influencer settings</h3>
                        <p className="mt-2 text-sm text-ink-muted">Configured types:</p>
                        <ul className="mt-2 space-y-1 text-sm text-ink-soft">
                            {Object.entries(crm.influencer_types || {}).map(([value, label]) => (
                                <li key={value} className="flex justify-between border-b border-surface-border py-1.5 last:border-0">
                                    <span>{label}</span>
                                    <span className="text-ink-muted">{value}</span>
                                </li>
                            ))}
                        </ul>
                        <p className="mt-3 text-xs text-ink-muted">Type labels are managed in config/crm.php.</p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
