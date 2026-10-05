<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== AccountStatus::Active) {
            $user->currentAccessToken()?->delete();

            if ($request->hasSession()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Ce compte est bloqué.'], 403);
            }

            return redirect()->route('login')->with('error', 'Ce compte est bloqué. Contactez le support ZELVORA.');
        }

        return $next($request);
    }
}
