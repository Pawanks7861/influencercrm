<?php

namespace App\Http\Controllers\InfluencerPortal;

use App\Http\Controllers\Controller;
use App\Models\CampaignInfluencer;
use App\Models\Influencer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $influencer = $this->influencer();

        $rows = CampaignInfluencer::query()
            ->where('influencer_id', $influencer->id)
            ->with([
                'campaign:id,campaign_name,brand_name',
                'payments' => fn ($q) => $q->latest('payment_date'),
            ])
            ->withSum('payments as payments_sum_amount', 'amount')
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString()
            ->through(function (CampaignInfluencer $row) {
                $paid = (float) ($row->payments_sum_amount ?? 0);
                $cost = (float) $row->influencer_cost;

                return [
                    'id' => $row->id,
                    'campaign_name' => $row->campaign?->campaign_name,
                    'brand_name' => $row->campaign?->brand_name,
                    'influencer_cost' => $row->influencer_cost,
                    'amount_paid' => round($paid, 2),
                    'amount_pending' => round(max($cost - $paid, 0), 2),
                    'payment_status' => $paid <= 0
                        ? 'not_paid'
                        : ($paid >= $cost ? 'paid' : 'partially_paid'),
                    'payments' => $row->payments->map(fn ($p) => [
                        'id' => $p->id,
                        'amount' => $p->amount,
                        'payment_date' => optional($p->payment_date)->toDateString(),
                        'payment_method' => $p->payment_method?->value ?? $p->payment_method,
                        'transaction_reference' => $p->transaction_reference,
                    ]),
                    'latest_payment_date' => optional($row->payments->first()?->payment_date)->toDateString(),
                ];
            });

        return Inertia::render('InfluencerPortal/Payments/Index', [
            'payments' => $rows,
        ]);
    }

    protected function influencer(): Influencer
    {
        return Auth::user()->influencer;
    }
}
