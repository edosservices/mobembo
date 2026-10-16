<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans', [
            'plans' => Plan::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'currency' => ['required', 'in:CDF,USD'],
            'is_active' => ['required', 'boolean'],
            'is_public' => ['required', 'boolean'],
            'limits' => ['required', 'json'],
        ], [
            'limits.json' => 'Les limites doivent être un objet JSON valide.',
        ]);

        $limits = json_decode($data['limits'], true);

        if (! is_array($limits)) {
            return back()->withErrors(['limits' => 'Les limites doivent être un objet JSON.']);
        }

        $plan->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'price_cents' => $request->filled('price_cents') ? (int) $data['price_cents'] : null,
            'currency' => $data['currency'],
            'is_active' => $request->boolean('is_active'),
            'is_public' => $request->boolean('is_public'),
            'limits' => $limits,
        ]);

        return back()->with('status', 'Offre mise à jour. Les tarifs ne sont pas codés dans l’application.');
    }
}
