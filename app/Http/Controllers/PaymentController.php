<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentStoreRequest;
use App\Models\CampaignInfluencer;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
        $this->middleware('permission:payments.view')->only(['index', 'show']);
        $this->middleware('permission:payments.manage')->only(['store', 'destroy', 'status']);
    }

    public function index(Request $request): Response
    {
        $query = Payment::query()->with(['influencer', 'campaign', 'campaignInfluencer']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_reference', 'like', "%{$search}%")
                    ->orWhereHas('influencer', fn ($i) => $i->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('campaign', fn ($c) => $c->where('campaign_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('payment_date', '>=', $request->get('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('payment_date', '<=', $request->get('to'));
        }

        $payments = $query->latest('payment_date')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'from', 'to', 'per_page']),
        ]);
    }

    public function store(PaymentStoreRequest $request): RedirectResponse
    {
        $this->paymentService->record($request->validated());

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->paymentService->void($payment);

        return back()->with('success', 'Payment voided.');
    }

    public function status(CampaignInfluencer $campaignInfluencer)
    {
        return response()->json($this->paymentService->deriveStatus($campaignInfluencer));
    }
}
