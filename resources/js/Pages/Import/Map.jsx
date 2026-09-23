import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/Crm/PageHeader';
import InputError from '@/Components/InputError';

export default function ImportMap({ job, mappableFields = {}, headers = [], preview = [] }) {
    const initial = Object.keys(mappableFields).reduce((acc, key) => {
        const existing = job.mapping?.field_map?.[key];
        const auto = headers.find((h) => String(h).toLowerCase().includes(key.replace('_', ' ')) || String(h).toLowerCase() === key);
        acc[key] = existing || auto || '';
        return acc;
    }, {});

    const { data, setData, post, processing, errors } = useForm({
        mapping: initial,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('import.save-mapping', job.id));
    };

    return (
        <AuthenticatedLayout title="Map columns">
            <Head title="Map import columns" />
            <PageHeader
                title="Map columns"
                description={`File: ${job.filename}`}
                actions={
                    <Link href={route('import.index')} className="crm-btn-secondary">
                        Cancel
                    </Link>
                }
            />

            <form onSubmit={submit} className="space-y-6">
                <div className="crm-card p-5">
                    <div className="grid gap-4 sm:grid-cols-2">
                        {Object.entries(mappableFields).map(([field, label]) => (
                            <div key={field}>
                                <label className="crm-label">
                                    {label}
                                    {field === 'name' ? ' *' : ''}
                                </label>
                                <select
                                    className="crm-input"
                                    value={data.mapping[field] || ''}
                                    onChange={(e) =>
                                        setData('mapping', { ...data.mapping, [field]: e.target.value })
                                    }
                                    required={field === 'name'}
                                >
                                    <option value="">— skip —</option>
                                    {headers.map((header) => (
                                        <option key={header} value={header}>
                                            {header}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        ))}
                    </div>
                    <InputError message={errors['mapping.name']} className="mt-2" />
                </div>

                <div className="crm-card overflow-hidden">
                    <div className="border-b border-surface-border px-4 py-3">
                        <h3 className="font-display text-sm font-semibold text-ink">Sample rows</h3>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="bg-surface text-xs uppercase text-ink-muted">
                                <tr>
                                    {headers.map((h) => (
                                        <th key={h} className="px-3 py-2 text-left whitespace-nowrap">{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {preview.map((row, idx) => (
                                    <tr key={idx} className="border-t border-surface-border">
                                        {(Array.isArray(row) ? row : headers.map((_, i) => row[i])).map((cell, i) => (
                                            <td key={i} className="px-3 py-2 whitespace-nowrap text-ink-soft">{cell ?? ''}</td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="flex justify-end">
                    <button type="submit" className="crm-btn-primary" disabled={processing}>
                        Save mapping & preview
                    </button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
