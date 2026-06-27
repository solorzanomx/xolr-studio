<?php

declare(strict_types=1);

namespace App\Http\Controllers\Audio;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\VoiceProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VoiceProfileController extends Controller
{
    public function store(Request $request, Character $character): RedirectResponse
    {
        $data = $request->validate([
            'name'                    => 'required|string|max:150',
            'elevenlabs_voice_id'     => 'required|string|max:100',
            'language'                => 'required|string|max:10',
            'default_stability'       => 'required|numeric|min:0|max:1',
            'default_similarity_boost'=> 'required|numeric|min:0|max:1',
            'default_style'           => 'required|numeric|min:0|max:1',
            'is_cloned'               => 'boolean',
            'is_default'              => 'boolean',
            'notes'                   => 'nullable|string',
        ]);

        if ($data['is_default'] ?? false) {
            $character->voiceProfiles()->update(['is_default' => false]);
        }

        $character->voiceProfiles()->create($data);

        return back()->with('success', 'Voice profile añadido.');
    }

    public function update(Request $request, VoiceProfile $voiceProfile): RedirectResponse
    {
        $data = $request->validate([
            'name'                    => 'required|string|max:150',
            'elevenlabs_voice_id'     => 'required|string|max:100',
            'language'                => 'required|string|max:10',
            'default_stability'       => 'required|numeric|min:0|max:1',
            'default_similarity_boost'=> 'required|numeric|min:0|max:1',
            'default_style'           => 'required|numeric|min:0|max:1',
            'is_cloned'               => 'boolean',
            'is_default'              => 'boolean',
            'notes'                   => 'nullable|string',
        ]);

        if ($data['is_default'] ?? false) {
            VoiceProfile::where('character_id', $voiceProfile->character_id)
                ->where('id', '!=', $voiceProfile->id)
                ->update(['is_default' => false]);
        }

        $voiceProfile->update($data);

        return back()->with('success', 'Voice profile actualizado.');
    }

    public function destroy(VoiceProfile $voiceProfile): RedirectResponse
    {
        $voiceProfile->delete();
        return back()->with('success', 'Voice profile eliminado.');
    }
}
