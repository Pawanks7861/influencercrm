import { useEffect, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import Drawer from '@/Components/Crm/Drawer';
import SearchInput from '@/Components/Crm/SearchInput';
import MoneyDisplay from '@/Components/Crm/MoneyDisplay';

export default function AddToCampaign({ open, onClose, influencer }) {
    const [search, setSearch] = useState('');
    const [campaigns, setCampaigns] = useState([]);
    const [loading, setLoading] = useState(false);
    const [campaignId, setCampaignId] = useState('');
    const [pricing, setPricing] = useState({
        influencer_cost: Number(influencer?.default_price || 0),
        grovera_fee: 0,
        final_amount: Number(influencer?.default_price || 0),
        final_amount_overridden: false,
        status: 'new_lead',
    });

    useEffect(() => {
        if (!open) return;
        setLoading(true);
        fetch(`${route('campaigns.index')}?select=1`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((r) => r.json())
            .then((data) => setCampaigns(Array.isArray(data) ? data : []))
            .catch(() => setCampaigns([]))
            .finally(() => setLoading(false));
    }, [open]);

    const filtered = useMemo(() => {
        const q = search.toLowerCase();
        if (!q) return campaigns;
        return campaigns.filter(
            (c) => c.campaign_name?.toLowerCase().includes(q) || c.brand_name?.toLowerCase().includes(q)
        );
    }, [campaigns, search]);

    const updatePricing = (field, value) => {
        setPricing((prev) => {
            const next = { ...prev, [field]: value };
            if (!next.final_amount_overridden && (field === 'influencer_cost' || field === 'grovera_fee')) {
                next.final_amount = Number(next.influencer_cost || 0) + Number(next.grovera_fee || 0);
            }
            return next;
        });
    };

    const continueToEditor = () => {
        if (!campaignId) return;
        sessionStorage.setItem(
            'crm_add_influencer',
            JSON.stringify({
                influencer_id: influencer.id,
                ...pricing,
            })
        );
        router.visit(route('campaigns.edit', campaignId));
    };

    return (
        <Drawer
            open={open}
            onClose={onClose}
            title={`Add ${influencer?.name || 'influencer'} to campaign`}
            width="max-w-lg"
            footer={
                <div className="flex justify-end gap-2">
                    <button type="button" className="crm-btn-secondary" onClick={onClose}>
                        Cancel
                    </button>
                    <button type="button" className="crm-btn-primary" disabled={!campaignId} onClick={continueToEditor}>
                        Continue in campaign editor
                    </button>
                </div>
            }
        >
            <div className="space-y-4">
                <SearchInput value={search} onChange={setSearch} placeholder="Search campaigns…" />
                <div className="max-h-48 space-y-1 overflow-y-auto rounded-lg border border-surface-border">
                    {loading && <p className="p-3 text-sm text-ink-muted">Loading campaigns…</p>}
                    {!loading &&
                        filtered.map((campaign) => (
                            <button
                                key={campaign.id}
                                type="button"
                                onClick={() => setCampaignId(campaign.id)}
                                className={`flex w-full flex-col items-start px-3 py-2 text-left text-sm hover:bg-surface ${
                                    String(campaignId) === String(campaign.id) ? 'bg-accent-soft/50' : ''
                                }`}
                            >
                                <span className="font-medium text-ink">{campaign.campaign_name}</span>
                                <span className="text-xs text-ink-muted">{campaign.brand_name || '—'}</span>
                            </button>
                        ))}
                    {!loading && !filtered.length && <p className="p-3 text-sm text-ink-muted">No campaigns found.</p>}
                </div>

                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <label className="crm-label">Influencer cost</label>
                        <input
                            type="number"
                            className="crm-input"
                            value={pricing.influencer_cost}
                            onChange={(e) => updatePricing('influencer_cost', e.target.value)}
                        />
                    </div>
                    <div>
                        <label className="crm-label">Grovera fee</label>
                        <input
                            type="number"
                            className="crm-input"
                            value={pricing.grovera_fee}
                            onChange={(e) => updatePricing('grovera_fee', e.target.value)}
                        />
                    </div>
                </div>
                <div>
                    <label className="crm-label">Final amount</label>
                    <input
                        type="number"
                        className="crm-input"
                        value={pricing.final_amount}
                        onChange={(e) =>
                            setPricing((prev) => ({
                                ...prev,
                                final_amount: e.target.value,
                                final_amount_overridden: true,
                            }))
                        }
                    />
                    <p className="mt-1 text-xs text-ink-muted">
                        Calculated:{' '}
                        <MoneyDisplay amount={Number(pricing.influencer_cost || 0) + Number(pricing.grovera_fee || 0)} />
                    </p>
                </div>
            </div>
        </Drawer>
    );
}
