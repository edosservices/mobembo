<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users', [
            'users' => User::query()->with('currentSubscription.plan')->withCount(['resumes', 'coverLetters'])->latest()->paginate(20),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['required', 'boolean'],
            'bonus_credits' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        if ($request->user()->id === $user->id && $data['role'] !== UserRole::Admin->value) {
            return back()->withErrors(['role' => 'Vous ne pouvez pas retirer votre propre accès administrateur.']);
        }

        if ($request->user()->id === $user->id && ! $request->boolean('is_active')) {
            return back()->withErrors(['is_active' => 'Vous ne pouvez pas désactiver votre propre compte.']);
        }

        $user->forceFill([
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ])->save();

        $subscription = $user->currentSubscription;

        if ($subscription !== null && $request->filled('bonus_credits')) {
            $subscription->update(['bonus_credits' => (int) $data['bonus_credits']]);
        }

        return back()->with('status', 'Compte mis à jour.');
    }
}
