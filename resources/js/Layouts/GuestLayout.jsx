import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col justify-center bg-surface px-4 py-10">
            <div className="mx-auto w-full max-w-[420px]">
                <div className="mb-8 text-center">
                    <Link href="/" className="inline-flex items-center gap-3">
                        <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-ink-soft font-display text-lg font-bold text-white">
                            G
                        </span>
                        <span className="text-left">
                            <span className="block font-display text-xl font-semibold text-ink">Grovera Studio</span>
                            <span className="block text-sm text-ink-muted">Influencer CRM</span>
                        </span>
                    </Link>
                </div>
                <div className="crm-card p-6 sm:p-8">{children}</div>
            </div>
        </div>
    );
}
