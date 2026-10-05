<?php

namespace App\Http\Controllers;

use App\Services\BadgeService;
use App\Services\ReferralProgressService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request, ReferralProgressService $progress, BadgeService $badges)
    {
        $team = $progress->snapshot($request->user());

        return view('profile.edit', [
            'user' => $request->user(),
            'team' => $team,
            'badges' => $badges->evaluate($team),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $request->user()->forceFill(['name' => $data['name']])->save();

        return back()->with('success', 'Profil mis à jour. Le numéro de téléphone reste l’identifiant du compte.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
        ])->save();

        return back()->with('success', 'Mot de passe modifié.');
    }
}
