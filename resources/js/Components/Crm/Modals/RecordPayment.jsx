import { useForm, usePage } from '@inertiajs/react';
import Drawer from '@/Components/Crm/Drawer';
import InputError from '@/Components/InputError';

export default function RecordPayment({ open, onClose, campaignInfluencerId, defaultAmount = '' }) {
    const { crm } = usePage().props;
    const { data, setData, post, processing, errors, reset } = useForm({
        campaign_influencer_id: campaignInfluencerId,
        amount: defaultAmount,
        payment_date: new Date().toISOString().slice(0, 10),
        payment_method: 'upi',
        payment_type: '',
        transaction_reference: '',
        remarks: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('payments.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <Drawer
            open={open}
            onClose={onClose}
            title="Record payment"
            footer={
                <div className="flex justify-end gap-2">
                    <button type="button" className="crm-btn-secondary" onClick={onClose}>
                        Cancel
                    </button>
                    <button type="submit" form="record-payment-form" className="crm-btn-primary" disabled={processing}>
                        Save payment
                    </button>
                </div>
            }
        >
            <form id="record-payment-form" onSubmit={submit} className="space-y-4">
                <div>
                    <label className="crm-label">Amount</label>
                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        className="crm-input"
                        value={data.amount}
                        onChange={(e) => setData('amount', e.target.value)}
                        required
                    />
                    <InputError message={errors.amount} className="mt-1" />
                </div>
                <div>
                    <label className="crm-label">Payment date</label>
                    <input
                        type="date"
                        className="crm-input"
                        value={data.payment_date}
                        onChange={(e) => setData('payment_date', e.target.value)}
                        required
                    />
                    <InputError message={errors.payment_date} className="mt-1" />
                </div>
                <div>
                    <label className="crm-label">Method</label>
                    <select className="crm-input" value={data.payment_method} onChange={(e) => setData('payment_method', e.target.value)}>
                        {Object.entries(crm?.payment_methods || {}).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                </div>
                <div>
                    <label className="crm-label">Reference</label>
                    <input
                        type="text"
                        className="crm-input"
                        value={data.transaction_reference}
                        onChange={(e) => setData('transaction_reference', e.target.value)}
                    />
                </div>
                <div>
                    <label className="crm-label">Remarks</label>
                    <textarea className="crm-input" value={data.remarks} onChange={(e) => setData('remarks', e.target.value)} />
                </div>
            </form>
        </Drawer>
    );
}
