import { formatDateTime, labelFromMap } from '@/lib/format';

export default function ActivityTimeline({ activities = [], activityTypes = {} }) {
    if (!activities.length) {
        return <p className="text-sm text-ink-muted">No activity yet.</p>;
    }

    return (
        <ol className="relative space-y-4 border-l border-surface-border pl-5">
            {activities.map((activity) => (
                <li key={activity.id} className="relative">
                    <span className="absolute -left-[1.4rem] top-1.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-accent" />
                    <div className="rounded-lg border border-surface-border bg-white px-3 py-2.5">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <p className="text-sm font-medium text-ink">
                                {labelFromMap(activityTypes, activity.activity_type)}
                            </p>
                            <p className="text-xs text-ink-muted">{formatDateTime(activity.activity_at)}</p>
                        </div>
                        {activity.note && <p className="mt-1 text-sm text-ink-soft">{activity.note}</p>}
                        <p className="mt-1 text-xs text-ink-muted">
                            {activity.influencer?.name}
                            {activity.creator?.name ? ` · ${activity.creator.name}` : ''}
                            {activity.campaign?.campaign_name ? ` · ${activity.campaign.campaign_name}` : ''}
                        </p>
                    </div>
                </li>
            ))}
        </ol>
    );
}
