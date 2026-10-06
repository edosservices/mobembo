<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function entry(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'dashboard');
    }

    public function create(Request $request)
    {
        $next = (string) $request->query('next', '');

        if ($next !== '' && str_starts_with($next, '/projets/') && ! str_contains($next, '://') && ! str_contains($next, '\\')) {
            $request->session()->put('url.intended', url($next));
        }

        return view('auth.login');
    }

    public function store(Request $request)
    {
        $phone = PhoneNumber::normalize($request->input('phone'));

        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => 'Indiquez un numéro mobile RDC valide, par exemple 0812345678.',
            ]);
        }

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('phone', $phone)->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'phone' => 'Numéro ou mot de passe incorrect.',
            ]);
        }

        if ($user->status !== AccountStatus::Active) {
            throw ValidationException::withMessages([
                'phone' => 'Ce compte est bloqué. Contactez le support ZELVORA.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->isAdmin()) {
            $intended = (string) $request->session()->pull('url.intended', '');
            $path = parse_url($intended, PHP_URL_PATH) ?: '';

            if ($intended !== '' && str_starts_with($path, '/admin')) {
                return redirect()->to($intended);
            }

            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
