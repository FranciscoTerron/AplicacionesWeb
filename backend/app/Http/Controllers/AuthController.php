<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    public function showLoginForm()
    {
        $googleEnabled = (bool) config('services.google.client_id');

        return view('auth.login', ['googleEnabled' => $googleEnabled]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            if (Auth::user()->role === 'cliente') {
                return redirect()->intended('/');
            }

            return redirect()->intended('/admin');
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function redirectToGoogle()
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('login')->withErrors([
                'email' => 'El login con Google aún no está configurado. Avisa al administrador.',
            ]);
        }

        // Calculamos el redirect_uri según el host actual del request, así el flow
        // funciona tanto en producción como en cualquier preview de Vercel sin tener
        // que mantener una env var por entorno.
        config(['services.google.redirect' => route('auth.google.callback')]);

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        config(['services.google.redirect' => route('auth.google.callback')]);

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'No pudimos verificar tu cuenta de Google. Probá de nuevo.',
            ]);
        }

        $email = strtolower((string) $googleUser->getEmail());
        if (! $email) {
            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta de Google no tiene un email accesible.',
            ]);
        }

        $allowedDomains = config('services.google.allowed_domains', []);
        if (empty($allowedDomains) && app()->isProduction()) {
            Log::warning('Google login: services.google.allowed_domains está vacío en producción; el acceso queda restringido solo a usuarios existentes en `users`.');
        }
        if (! empty($allowedDomains)) {
            $domain = substr(strrchr($email, '@') ?: '', 1);
            if (! in_array($domain, $allowedDomains, true)) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Tu cuenta no pertenece a un dominio autorizado.',
                ]);
            }
        }

        $existing = $this->firestore->query('users', ['email' => $email], 1);
        if (count($existing) > 0) {
            $userData = $existing[0];

            if (! ($userData['active'] ?? true)) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Tu usuario está inactivo. Contactá al administrador.',
                ]);
            }

            $this->firestore->updateDocument('users', $userData['id'], [
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'updated_at' => now()->toISOString(),
            ]);
        } else {
            // Email desconocido: NO se crean usuarios internos desde el login con
            // Google. La única excepción es services.google.admin_emails, que
            // bootstrapea admins; el resto de las altas se hace desde /admin/users.
            $adminEmails = array_map('strtolower', config('services.google.admin_emails', []));
            if (! in_array($email, $adminEmails, true)) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Tu cuenta de Google no tiene acceso al panel. Contactá a un administrador para que te dé de alta.',
                ]);
            }

            $userData = $this->firestore->createDocument('users', [
                'name' => $googleUser->getName() ?: $email,
                'email' => $email,
                'role' => 'admin',
                'active' => true,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => '',
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ]);
        }

        $user = new User;
        $user->forceFill([
            'id' => (string) ($userData['id'] ?? ''),
            'name' => $userData['name'] ?? '',
            'email' => $userData['email'] ?? $email,
            'role' => $userData['role'] ?? 'cliente',
            'active' => $userData['active'] ?? true,
        ]);
        $user->exists = true;

        Auth::login($user, true);
        $request->session()->regenerate();

        // Cliente no entra al panel admin: lo mandamos al catálogo público.
        if ($user->role === 'cliente') {
            return redirect()->intended('/');
        }

        return redirect()->intended('/admin');
    }
}
