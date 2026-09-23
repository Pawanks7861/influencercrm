import { useForm, usePage } from '@inertiajs/react';
import Drawer from '@/Components/Crm/Drawer';
import InputError from '@/Components/InputError';

export default function AddFollowUp({
    open,
    onClose,
    influencerId = '',
    clientId = '',
    campaignId = null,
    influencers = [],
    clients = [],
    users = [],
    defaultEntity = null,
}) {
    const { auth } = usePage().props;

    const initialEntity = defaultEntity
        || (clientId ? 'client' : null)
        || (influencerId ? 'influencer' : null)
        || (campaignId ? 'campaign' : 'influencer');

    const { data, setData, post, processing, errors, reset } = useForm({
        entity_type: initialEntity,
        influencer_id: influencerId || '',
        client_id: clientId || '',
        campaign_id: campaignId || '',
        assigned_to: auth?.user?.id || '',
        follow_up_date: new Date().toISOString().slice(0, 10),
        follow_up_time: '10:00',
        note: '',
    });

    const setEntity = (type) => {
        setData({
            ...data,
            entity_type: type,
            influencer_id: type === 'influencer' ? data.influencer_id : '',
            client_id: type === 'client' ? data.client_id : '',
            campaign_id: type === 'campaign' ? data.campaign_id : (type !== 'campaign' ? data.campaign_id : ''),
        });
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('follow-ups.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset('note');
                onClose();
            },
        });
    };

    const lockedEntity = Boolean(influencerId || clientId);

    return (
        <Drawer
            open={open}
            onClose={onClose}
            title="Schedule follow-up"
            footer={
                <div className="flex justify-end gap-2">
                    <button type="button" className="crm-btn-secondary" onClick={onClose}>
                        Cancel
                    </button>
                    <button type="submit" form="add-followup-form" className="crm-btn-primary" disabled={processing}>
                        Schedule
                    </button>
                </div>
            }
        >
            <form id="add-followup-form" onSubmit={submit} className="space-y-4">
                {!lockedEntity && (
                    <div>
                        <label className="crm-label">Related to</label>
                        <select
                            className="crm-input"
                            value={data.entity_type}
                            onChange={(e) => setEntity(e.target.value)}
                        >
                            <option value="influencer">Influencer</option>
                            <option value="client">Client</option>
                            <option value="campaign">Campaign</option>
                        </select>
                    </div>
                )}

                {data.entity_type === 'influencer' && !influencerId && (
                    <div>
                        <label className="crm-label">Influencer</label>
                        <select
                            className="crm-input"
                            value={data.influencer_id}
                            onChange={(e) => setData('influencer_id', e.target.value)}
                            required
                        >
                            <option value="">Select influencer</option>
                            {influencers.map((inf) => (
                                <option key={inf.id} value={inf.id}>
                                    {inf.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.influencer_id} className="mt-1" />
                    </div>
                )}

                {data.entity_type === 'client' && !clientId && (
                    <div>
                        <label className="crm-label">Client</label>
                        <select
                            className="crm-input"
                            value={data.client_id}
                            onChange={(e) => setData('client_id', e.target.value)}
                            required
                        >
                            <option value="">Select client</option>
                            {clients.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.company_name || c.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.client_id} className="mt-1" />
                    </div>
                )}

                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <label className="crm-label">Date</label>
                        <input
                            type="date"
                            className="crm-input"
                            value={data.follow_up_date}
                            onChange={(e) => setData('follow_up_date', e.target.value)}
                            required
                        />
                        <InputError message={errors.follow_up_date} className="mt-1" />
                    </div>
                    <div>
                        <label className="crm-label">Time</label>
                        <input
                            type="time"
                            className="crm-input"
                            value={data.follow_up_time}
                            onChange={(e) => setData('follow_up_time', e.target.value)}
                        />
                        <InputError message={errors.follow_up_time} className="mt-1" />
                    </div>
                </div>
                {users.length > 0 && (
                    <div>
                        <label className="crm-label">Assign to</label>
                        <select className="crm-input" value={data.assigned_to} onChange={(e) => setData('assigned_to', e.target.value)}>
                            {users.map((user) => (
                                <option key={user.id} value={user.id}>
                                    {user.name}
                                </option>
                            ))}
                        </select>
                    </div>
                )}
                <div>
                    <label className="crm-label">Note</label>
                    <textarea className="crm-input min-h-[100px]" value={data.note} onChange={(e) => setData('note', e.target.value)} />
                </div>
            </form>
        </Drawer>
    );
}
