<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockImpersonatedMutations
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('impersonator_id')) {
            return $next($request);
        }

        if ($request->isMethodSafe()) {
            return $next($request);
        }

        if ($request->routeIs('support.leave', 'logout')) {
            return $next($request);
        }

        return back()->with('error', 'Pendant un dépannage, les opérations du compte restent réservées au client. Revenez à l’administration pour un bonus, un ajustement ou une validation.');
    }
}
