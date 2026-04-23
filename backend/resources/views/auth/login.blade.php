@extends('layouts.app')

@section('title', 'Login - MA Piscinas Admin')

@section('styles')
.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--navy) 0%, var(--deep-blue) 100%);
}

.login-box {
    background: var(--white);
    padding: 2.5rem;
    border-radius: .5rem;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25);
}

.login-logo {
    text-align: center;
    margin-bottom: 2rem;
}

.login-logo h1 {
    font-size: 1.75rem;
    color: var(--dark);
    font-weight: 600;
}

.login-logo span {
    color: var(--primary);
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group label {
    display: block;
    font-size: .85rem;
    color: var(--text);
    margin-bottom: .5rem;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: .75rem 1rem;
    border: 1px solid #dee2e6;
    border-radius: .375rem;
    font-size: 1rem;
    font-family: inherit;
    transition: border-color .15s;
}

.form-group input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(0, 119, 182, .1);
}

.btn-primary {
    width: 100%;
    padding: .75rem;
    background: var(--primary);
    color: var(--white);
    border: none;
    border-radius: .375rem;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background .15s;
}

.btn-primary:hover {
    background: var(--dark);
}

.error-message {
    color: var(--danger);
    font-size: .85rem;
    margin-top: .5rem;
}

.back-link {
    text-align: center;
    margin-top: 1.5rem;
}

.back-link a {
    color: var(--primary);
    text-decoration: none;
    font-size: .9rem;
}

.back-link a:hover {
    text-decoration: underline;
}
@endsection

@section('content')
<div class="login-container">
    <div class="login-box">
        <div class="login-logo">
            <h1>MA <span>Piscinas</span></h1>
            <p style="color:#6c757d;font-size:.9rem;margin-top:.5rem;">Panel Administrativo</p>
        </div>

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

            <button type="submit" class="btn-primary">Iniciar Sesión</button>
        </form>

        <div class="back-link">
            <a href="{{ url('/') }}">← Volver al sitio</a>
        </div>
    </div>
</div>
@endsection