<?php

namespace App\Http\Resources;

use App\Models\Church;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Church */
class ChurchSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'address' => $this->address,
            'logo_url' => $this->logo_path
                ? Storage::disk('public')->url($this->logo_path)
                : null,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
        ];
    }
}
