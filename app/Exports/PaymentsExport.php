<?php

namespace App\Exports;

use App\Models\Payment;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(protected Request $request) {}

    public function query()
    {
        $query = Payment::query()->with(['influencer', 'campaign']);

        if ($this->request->filled('from')) {
            $query->whereDate('payment_date', '>=', $this->request->get('from'));
        }

        if ($this->request->filled('to')) {
            $query->whereDate('payment_date', '<=', $this->request->get('to'));
        }

        return $query->latest('payment_date');
    }

    public function headings(): array
    {
        return [
            'Payment Date',
            'Influencer',
            'Campaign',
            'Amount',
            'Method',
            'Reference',
            'Remarks',
        ];
    }

    public function map($payment): array
    {
        return [
            optional($payment->payment_date)->format('Y-m-d'),
            $payment->influencer?->name,
            $payment->campaign?->campaign_name,
            $payment->amount,
            $payment->payment_method?->value ?? $payment->payment_method,
            $payment->transaction_reference,
            $payment->remarks,
        ];
    }
}
