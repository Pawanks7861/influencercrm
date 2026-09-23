import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { CheckCircle2, X, XCircle } from 'lucide-react';

export default function Toast() {
    const { flash } = usePage().props;
    const [message, setMessage] = useState(null);
    const [type, setType] = useState('success');

    useEffect(() => {
        if (flash?.success) {
            setType('success');
            setMessage(flash.success);
        } else if (flash?.error) {
            setType('error');
            setMessage(flash.error);
        }
    }, [flash?.success, flash?.error]);

    useEffect(() => {
        if (!message) return undefined;
        const timer = setTimeout(() => setMessage(null), 4000);
        return () => clearTimeout(timer);
    }, [message]);

    if (!message) return null;

    const Icon = type === 'success' ? CheckCircle2 : XCircle;

    return (
        <div className="pointer-events-none fixed inset-x-0 bottom-6 z-[60] flex justify-center px-4">
            <div
                className={`pointer-events-auto flex max-w-md items-start gap-3 rounded-xl border px-4 py-3 shadow-soft ${
                    type === 'success'
                        ? 'border-emerald-200 bg-white text-emerald-800'
                        : 'border-red-200 bg-white text-red-800'
                }`}
            >
                <Icon className="mt-0.5 h-4 w-4 shrink-0" />
                <p className="text-sm font-medium">{message}</p>
                <button type="button" className="ml-2 text-ink-muted hover:text-ink" onClick={() => setMessage(null)}>
                    <X className="h-4 w-4" />
                </button>
            </div>
        </div>
    );
}
