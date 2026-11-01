<?php

namespace App\Http\Resources;

use App\Models\MemberProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MemberProfile */
class MemberProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'phone_number' => $this->phone_number,
            'gender' => $this->gender,
            'province' => $this->province,
            'city_municipality' => $this->city_municipality,
            'barangay' => $this->barangay,
            'street_address' => $this->street_address,
            'zip_code' => $this->zip_code,
        ];
    }
}
