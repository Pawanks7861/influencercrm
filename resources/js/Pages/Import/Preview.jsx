import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';

export default function ImportPreview({ job, rows = [] }) {
    const [processing, setProcessing] = useState(false);
    const fields = Object.keys(rows[0] || {});

    return (
        <AuthenticatedLayout title="Preview import">
            <Head title="Preview import" />
            <PageHeader
                title="Preview import"
                description={`${job.filename} · ${job.total_rows} rows · showing first ${rows.length}`}
                actions={
                    <Link href={route('import.map', job.id)} className="crm-btn-secondary">
                        Back to mapping
                    </Link>
                }
            />

            <div className="crm-card mb-6 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full text-sm">
                        <thead className="bg-surface text-xs uppercase text-ink-muted">
                            <tr>
                                {fields.map((f) => (
                                    <th key={f} className="px-3 py-2 text-left whitespace-nowrap">{f}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row, idx) => (
                                <tr key={idx} className="border-t border-surface-border">
                                    {fields.map((f) => (
                                        <td key={f} className="px-3 py-2 whitespace-nowrap text-ink-soft">{row[f] ?? ''}</td>
                                    ))}
                                </tr>
                            ))}
                            {!rows.length && (
                                <tr><td className="px-4 py-8 text-center text-ink-muted">No preview rows</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="flex justify-end gap-2">
                <Link href={route('import.index')} className="crm-btn-secondary">Cancel</Link>
                <button
                    type="button"
                    className="crm-btn-primary"
                    disabled={processing}
                    onClick={() => {
                        setProcessing(true);
                        router.post(route('import.process', job.id), {}, {
                            onFinish: () => setProcessing(false),
                        });
                    }}
                >
                    {processing ? 'Importing…' : `Import ${job.total_rows} rows`}
                </button>
            </div>
        </AuthenticatedLayout>
    );
}
