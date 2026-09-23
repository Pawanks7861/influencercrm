<?php

namespace App\Exports;

use App\Models\Influencer;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InfluencersExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(protected Request $request) {}

    public function query()
    {
        $query = Influencer::query();

        if ($search = $this->request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('instagram_username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($type = $this->request->get('influencer_type')) {
            $query->where('influencer_type', $type);
        }

        if ($location = $this->request->get('location')) {
            $query->where('location', $location);
        }

        return $query->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Name',
            'Instagram Username',
            'Instagram URL',
            'Mobile',
            'Email',
            'Location',
            'Type',
            'Default Price',
            'Status',
        ];
    }

    public function map($influencer): array
    {
        return [
            $influencer->name,
            $influencer->instagram_username,
            $influencer->instagram_url,
            $influencer->mobile,
            $influencer->email,
            $influencer->location,
            $influencer->influencer_type?->value ?? $influencer->influencer_type,
            $influencer->default_price,
            $influencer->status,
        ];
    }
}
