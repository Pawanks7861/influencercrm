<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShortlistItemResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'status' => $this->status?->value ?? $this->status,
            'client_remark' => $this->client_remark,
            'responded_at' => optional($this->responded_at)->toIso8601String(),
            'display_order' => $this->display_order,
            'show_name' => (bool) $this->show_name,
            'show_instagram' => (bool) $this->show_instagram,
            'show_price' => (bool) $this->show_price,
            'show_location' => (bool) $this->show_location,
            'show_type' => (bool) $this->show_type,
            'show_note' => (bool) $this->show_note,
        ];

        if ($this->show_name) {
            $data['display_name'] = $this->display_name;
        }

        if ($this->show_instagram) {
            $data['instagram_url'] = $this->instagram_url;
            $data['instagram_username'] = $this->instagram_username;
        }

        if ($this->show_price) {
            $data['client_price'] = $this->client_price;
        }

        if ($this->show_location) {
            $data['location'] = $this->location;
        }

        if ($this->show_type) {
            $data['influencer_type'] = $this->influencer_type;
        }

        if ($this->show_note) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
