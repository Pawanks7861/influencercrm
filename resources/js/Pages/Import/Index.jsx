import { Head, Link, router, useForm } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import StatusBadge from '@/Components/Crm/StatusBadge';
import { formatDateTime } from '@/lib/format';
import InputError from '@/Components/InputError';

export default function ImportIndex({ jobs = [], mappableFields = {} }) {
    const { data, setData, post, processing, errors, progress } = useForm({
        file: null,
        type: 'influencers',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('import.upload'), { forceFormData: true });
    };

    return (
        <AuthenticatedLayout title="Import influencers">
            <Head title="Import influencers" />
            <PageHeader
                title="Import influencers"
                description="Upload a spreadsheet, map columns, preview, then import"
                actions={
                    <Link href={route('influencers.index')} className="crm-btn-secondary">
                        Back to influencers
                    </Link>
                }
            />

            <div className="grid gap-6 lg:grid-cols-3">
                <form onSubmit={submit} className="crm-card space-y-4 p-5 lg:col-span-1">
                    <h3 className="font-display text-sm font-semibold text-ink">Upload file</h3>
                    <p className="text-xs text-ink-muted">Accepted: .xlsx, .xls, .csv (max 10MB)</p>
                    <div>
                        <label className="crm-label">File</label>
                        <input
                            type="file"
                            accept=".xlsx,.xls,.csv,.txt"
                            className="block w-full text-sm text-ink-muted file:mr-3 file:rounded-lg file:border-0 file:bg-accent file:px-3 file:py-2 file:text-sm file:font-medium file:text-white"
                            onChange={(e) => setData('file', e.target.files[0])}
                            required
                        />
                        <InputError message={errors.file} className="mt-1" />
                    </div>
                    {progress && (
                        <div className="h-1.5 overflow-hidden rounded-full bg-surface">
                            <div className="h-full bg-accent transition-all" style={{ width: `${progress.percentage}%` }} />
                        </div>
                    )}
                    <button type="submit" className="crm-btn-primary w-full" disabled={processing || !data.file}>
                        <Upload className="h-4 w-4" /> Upload & continue
                    </button>
                    <div className="rounded-lg bg-surface p-3 text-xs text-ink-muted">
                        <p className="font-medium text-ink-soft">Mappable fields</p>
                        <ul className="mt-2 space-y-1">
                            {Object.values(mappableFields).map((label) => (
                                <li key={label}>· {label}</li>
                            ))}
                        </ul>
                    </div>
                </form>

                <div className="crm-card overflow-hidden lg:col-span-2">
                    <div className="border-b border-surface-border px-4 py-3">
                        <h3 className="font-display text-sm font-semibold text-ink">Recent imports</h3>
                    </div>
                    <table className="min-w-full text-sm">
                        <thead className="bg-surface text-xs uppercase text-ink-muted">
                            <tr>
                                <th className="px-4 py-3 text-left">File</th>
                                <th className="px-4 py-3 text-left">Status</th>
                                <th className="px-4 py-3 text-left">Rows</th>
                                <th className="px-4 py-3 text-left">Result</th>
                                <th className="px-4 py-3 text-left">When</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {jobs.map((job) => (
                                <tr key={job.id} className="border-t border-surface-border">
                                    <td className="px-4 py-3 font-medium text-ink">{job.filename}</td>
                                    <td className="px-4 py-3"><StatusBadge status={job.status} /></td>
                                    <td className="px-4 py-3 text-ink-soft">{job.total_rows}</td>
                                    <td className="px-4 py-3 text-ink-soft">
                                        {job.status === 'completed'
                                            ? `${job.success_count || 0} ok · ${job.error_count || 0} failed`
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-3 text-ink-muted">{formatDateTime(job.created_at)}</td>
                                    <td className="px-4 py-3 text-right">
                                        {job.status === 'uploaded' && (
                                            <Link href={route('import.map', job.id)} className="text-sm font-medium text-accent">Map</Link>
                                        )}
                                        {job.status === 'mapped' && (
                                            <Link href={route('import.preview', job.id)} className="text-sm font-medium text-accent">Preview</Link>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {!jobs.length && (
                                <tr><td colSpan={6} className="px-4 py-10 text-center text-ink-muted">No import jobs yet</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
