<?php

namespace App\Services\Payment;

use App\Models\CampaignInfluencer;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function record(array $data): Payment
    {
        $amount = Money::of($data['amount'] ?? 0);

        if (! Money::isPositive($amount)) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        $campaignInfluencer = CampaignInfluencer::with('campaign')->findOrFail($data['campaign_influencer_id']);

        $payment = Payment::create([
            'campaign_id' => $campaignInfluencer->campaign_id,
            'campaign_influencer_id' => $campaignInfluencer->id,
            'influencer_id' => $campaignInfluencer->influencer_id,
            'amount' => $amount,
            'payment_type' => $data['payment_type'] ?? null,
            'payment_status' => 'paid',
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return $payment->load(['campaign', 'influencer', 'campaignInfluencer']);
    }

    public function void(Payment $payment): void
    {
        $payment->delete();
    }

    public function deriveStatus(CampaignInfluencer $campaignInfluencer): array
    {
        $campaignInfluencer->unsetRelation('payments');

        return [
            'amount_paid' => $campaignInfluencer->amount_paid,
            'amount_pending' => $campaignInfluencer->amount_pending,
            'payment_status' => $campaignInfluencer->derived_payment_status,
            'final_amount' => Money::of($campaignInfluencer->final_amount),
        ];
    }
}
