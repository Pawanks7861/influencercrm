<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShortlistResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status?->value ?? $this->status,
            'message' => $this->message,
            'shared_at' => optional($this->shared_at)->toIso8601String(),
            'expires_at' => optional($this->expires_at)->toDateString(),
            'requirement' => $this->whenLoaded('requirement', fn () => [
                'id' => $this->requirement->id,
                'requirement_number' => $this->requirement->requirement_number,
                'title' => $this->requirement->title,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(
                fn ($item) => (new ShortlistItemResource($item))->resolve()
            )->values()->all()),
            'items_count' => $this->when(isset($this->items_count), $this->items_count),
        ];
    }
}
