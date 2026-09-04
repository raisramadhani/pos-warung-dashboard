<?php

namespace App\Http\Middleware;

use App\Models\Session;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleSession
{
    /**
     * Ensure the authenticated user's session still exists in the database.
     *
     * When another device logs in and deletes old session rows, the current
     * session cookie becomes stale. This middleware detects that and forces
     * a logout with a Filament notification.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            $sessionExists = Session::query()
                ->where('id', session()->getId())
                ->where('user_id', $user->id)
                ->exists();

            if (! $sessionExists) {
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();

                session()->flash('session_notification', [
                    'title' => 'Sesi Berakhir',
                    'body' => 'Sesi Anda telah berakhir karena login di perangkat lain.',
                    'status' => 'warning',
                ]);

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Sesi Anda telah berakhir.'], 401);
                }

                return redirect()->route('filament.merchant.auth.login');
            }
        }

        return $next($request);
    }
}
