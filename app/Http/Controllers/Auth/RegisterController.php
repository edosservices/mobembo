<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $phone = PhoneNumber::normalize($request->input('phone'));

        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => 'Indiquez un numéro mobile RDC valide, par exemple 0812345678.',
            ]);
        }

        $request->merge([
            'phone' => $phone,
            'referral_code' => strtoupper(trim((string) $request->input('referral_code'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:/^\+243[0-9]{9}$/', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'referral_code' => ['nullable', 'string', 'max:16'],
        ]);

        $referrer = null;

        if ($data['referral_code'] !== '') {
            $referrer = User::query()->where('referral_code', $data['referral_code'])->first();

            if (! $referrer) {
                throw ValidationException::withMessages([
                    'referral_code' => 'Ce code de parrainage est inconnu.',
                ]);
            }
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'referred_by_id' => $referrer?->id,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Compte créé. Votre code de parrainage est prêt.');
    }
}
