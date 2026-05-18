@extends('layouts.app')

@section('title', 'Login — MA Piscinas Admin')

@section('styles')
.login-wrap {
    min-height: calc(100vh - 68px);
    display: flex; align-items: center; justify-content: center;
    background: var(--surface);
    padding: 2rem 1rem;
}
.login-card {
    background: #fff;
    padding: 2.5rem;
    border-radius: 16px;
    width: 100%;
    max-width: 420px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-lg);
}
.login-head { text-align: center; margin-bottom: 2rem; }
.login-head h1 {
    font-size: 1.4rem; font-weight: 700; color: var(--text);
    letter-spacing: -.01em;
}
.login-head p { color: var(--muted); font-size: .9rem; margin-top: .35rem; }

.form-group { margin-bottom: 1rem; }
.form-group label {
    display: block; font-size: .82rem; color: var(--text-soft);
    margin-bottom: .4rem; font-weight: 500;
}
.form-group input {
    width: 100%; padding: .75rem .95rem;
    border: 1px solid var(--border); border-radius: 8px;
    font-size: .95rem; font-family: inherit;
    color: var(--text); background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.form-group input:focus {
    outline: none; border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(2,132,199,.15);
}

.btn-submit {
    width: 100%; padding: .8rem;
    background: var(--primary); color: #fff;
    border: 0; border-radius: 8px;
    font-size: .95rem; font-weight: 500; cursor: pointer;
    transition: background .15s;
    margin-top: .5rem;
}
.btn-submit:hover { background: var(--primary-dark); }

.error-message { color: var(--danger); font-size: .82rem; margin-top: .4rem; }

.divider {
    display: flex; align-items: center; gap: .8rem;
    margin: 1.5rem 0;
    color: var(--muted); font-size: .78rem;
}
.divider::before, .divider::after {
    content: ''; flex: 1; height: 1px; background: var(--border);
}

.btn-google {
    width: 100%; padding: .75rem;
    background: #fff; color: var(--text);
    border: 1px solid var(--border); border-radius: 8px;
    font-size: .95rem; font-weight: 500; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: .6rem;
    text-decoration: none;
    transition: background .15s, border-color .15s;
}
.btn-google:hover { background: var(--surface); border-color: var(--text); }
.btn-google svg { width: 18px; height: 18px; flex-shrink: 0; }

.back-link {
    text-align: center; margin-top: 1.5rem;
    font-size: .88rem;
}
.back-link a { color: var(--primary); }
.back-link a:hover { text-decoration: underline; }

.notice {
    background: #fffbeb; border: 1px solid #fde68a;
    color: #92400e; padding: .7rem .9rem; border-radius: 8px;
    font-size: .82rem; margin-bottom: 1rem;
}
@endsection

@section('content')
<div class="login-wrap">
    <div class="login-card">
        <div class="login-head">
            <h1>Panel Administrativo</h1>
            <p>Ingresá con tu cuenta para gestionar el catálogo</p>
        </div>

        @if(($googleEnabled ?? false))
            <a href="{{ route('auth.google') }}" class="btn-google">
                <svg viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Continuar con Google
            </a>

            <div class="divider">o con email</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" required>
                @error('password')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-submit">Iniciar sesión</button>
        </form>

        <div class="back-link">
            <a href="{{ url('/') }}">← Volver al sitio</a>
        </div>
    </div>
</div>
@endsection
