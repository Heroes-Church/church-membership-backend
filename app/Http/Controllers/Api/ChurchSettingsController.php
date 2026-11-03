<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Church\UpdateChurchSettingsRequest;
use App\Http\Resources\ChurchSettingsResource;
use App\Support\CurrentChurch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChurchSettingsController extends Controller
{
    public function show(CurrentChurch $currentChurch): ChurchSettingsResource
    {
        return new ChurchSettingsResource($currentChurch->church());
    }

    public function update(
        UpdateChurchSettingsRequest $request,
        CurrentChurch $currentChurch
    ): ChurchSettingsResource {
        $church = $currentChurch->church();
        $settings = $request->safe()->except(['logo', 'remove_logo']);
        $oldLogoPath = $church->logo_path;
        $removeLogo = $request->boolean('remove_logo');

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('church-logos', 'public');

            if ($logoPath === false) {
                throw new \RuntimeException('The church logo could not be saved.');
            }

            $settings['logo_path'] = $logoPath;
        } elseif ($removeLogo) {
            $settings['logo_path'] = null;
        }

        $church->update($settings);

        if ($oldLogoPath && ($request->hasFile('logo') || $removeLogo)) {
            if (! Storage::disk('public')->delete($oldLogoPath)) {
                Log::warning('A replaced church logo could not be deleted.', [
                    'church_id' => $church->id,
                    'logo_path' => $oldLogoPath,
                ]);
            }
        }

        return new ChurchSettingsResource($church->refresh());
    }
}
