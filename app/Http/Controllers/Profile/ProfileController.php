<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // If email changed, mark as unverified
        if ($user->email !== $data['email']) {
            $data['email_verified_at'] = null;
        }

        $user->update($data);

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        // Invalida otras sesiones activas por seguridad
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Contraseña actualizada. Inicia sesión de nuevo.');
    }

    public function sessions(Request $request): array
    {
        $currentSessionId = $request->session()->getId();

        return DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn($s) => [
                'id'            => $s->id,
                'is_current'    => $s->id === $currentSessionId,
                'ip_address'    => $s->ip_address,
                'device'        => $this->parseDevice($s->user_agent ?? ''),
                'browser'       => $this->parseBrowser($s->user_agent ?? ''),
                'last_active'   => now()->diffForHumans(\Carbon\Carbon::createFromTimestamp($s->last_activity)),
                'last_activity' => $s->last_activity,
            ])
            ->toArray();
    }

    public function destroySession(Request $request, string $sessionId): RedirectResponse
    {
        // Solo puede eliminar sus propias sesiones
        $exists = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $exists) {
            abort(403);
        }

        DB::table('sessions')->where('id', $sessionId)->delete();

        return back()->with('success', 'Sesión cerrada.');
    }

    public function destroyOtherSessions(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string', 'current_password']]);

        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('success', 'Todas las otras sesiones han sido cerradas.');
    }

    private function parseDevice(string $ua): string
    {
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad'))  return 'iOS';
        if (str_contains($ua, 'Android'))  return 'Android';
        if (str_contains($ua, 'Windows'))  return 'Windows';
        if (str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS')) return 'macOS';
        if (str_contains($ua, 'Linux'))    return 'Linux';
        return 'Desconocido';
    }

    private function parseBrowser(string $ua): string
    {
        if (str_contains($ua, 'Edg/'))     return 'Edge';
        if (str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')) return 'Opera';
        if (str_contains($ua, 'Chrome'))   return 'Chrome';
        if (str_contains($ua, 'Firefox'))  return 'Firefox';
        if (str_contains($ua, 'Safari'))   return 'Safari';
        return 'Navegador';
    }
}
