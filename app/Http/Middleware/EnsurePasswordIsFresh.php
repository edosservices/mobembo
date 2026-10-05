<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsFresh
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password && ! $request->routeIs('profile.edit', 'profile.password', 'logout')) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Vous devez changer votre mot de passe.'], 423);
            }

            return redirect()->route('profile.edit')->with('error', 'Choisissez un nouveau mot de passe pour continuer.');
        }

        return $next($request);
    }
}
