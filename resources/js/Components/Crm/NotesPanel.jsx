import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { formatDateTime } from '@/lib/format';

export default function NotesPanel({ notes = [], onAdd, canDelete = true }) {
    return (
        <div className="space-y-4">
            {onAdd && (
                <div className="flex justify-end">
                    <button type="button" className="crm-btn-secondary" onClick={onAdd}>
                        Add note
                    </button>
                </div>
            )}
            {!notes.length ? (
                <p className="text-sm text-ink-muted">No notes yet.</p>
            ) : (
                <ul className="space-y-3">
                    {notes.map((note) => (
                        <li key={note.id} className="rounded-lg border border-surface-border bg-white px-3 py-2.5">
                            <div className="flex items-start justify-between gap-3">
                                <p className="whitespace-pre-wrap text-sm text-ink-soft">{note.note}</p>
                                {canDelete && (
                                    <button
                                        type="button"
                                        className="shrink-0 rounded p-1 text-ink-muted hover:bg-red-50 hover:text-red-600"
                                        onClick={() => {
                                            if (confirm('Delete this note?')) {
                                                router.delete(route('notes.destroy', note.id));
                                            }
                                        }}
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </button>
                                )}
                            </div>
                            <p className="mt-2 text-xs text-ink-muted">
                                {note.creator?.name ? `${note.creator.name} · ` : ''}
                                {formatDateTime(note.created_at)}
                            </p>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
