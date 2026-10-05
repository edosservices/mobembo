<?php

namespace App\Http\Controllers\Api;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ApiPresenter;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
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
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $referrer = null;
        if ($data['referral_code'] !== '') {
            $referrer = User::query()->where('referral_code', $data['referral_code'])->first();
            if (! $referrer) {
                throw ValidationException::withMessages(['referral_code' => 'Ce code de parrainage est inconnu.']);
            }
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'referred_by_id' => $referrer?->id,
        ]);

        $token = $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => ApiPresenter::user($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $phone = PhoneNumber::normalize($request->input('phone'));
        $request->validate([
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $user = $phone ? User::query()->where('phone', $phone)->first() : null;

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['phone' => 'Numéro ou mot de passe incorrect.']);
        }

        if ($user->status !== AccountStatus::Active) {
            return response()->json(['message' => 'Ce compte est bloqué.'], 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token' => $user->createToken($request->input('device_name', 'mobile'))->plainTextToken,
            'user' => ApiPresenter::user($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Session terminée.']);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => ApiPresenter::user($request->user())]);
    }
}
