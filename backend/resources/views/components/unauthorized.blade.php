@extends('layouts.admin')

@section('title', ($title ?? 'Acceso Denegado') . ' - MA Piscinas')

@section('page-title', $title ?? 'Acceso Denegado')
@section('page-subtitle', $subtitle ?? 'No tienes permisos para acceder a esta sección')

@section('content')
<div class="card text-center" style="max-width: 600px; margin: 50px auto;">
    <div class="card-body p-5">
        <div class="mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#dc3545" class="bi bi-shield-x" viewBox="0 0 16 16">
                <path d="M5.338 1.593A.5.5 0 0 1 6.173.5H13.5A1.5 1.5 0 0 1 15 2v13h-2v-1h-8A1.5 1.5 0 0 1-1.5-1.342L.338 1.593zM2.15 2.39 5.736 8h4.528L2.15 2.39zm3.586 8.61L8 10.5l2.264 1.5L13.848 3.52 11.736 2H6.173l-2.39 1.477 2.153 2.683zm.634 0L6.736 8h4.528l-1.5 2.5H5.37z"/>
            </svg>
        </div>

        <h3 class="text-danger mb-3">{{ $title ?? 'Acceso Denegado' }}</h3>

        <p class="text-muted mb-4">
            {{ $message ?? 'Tu cuenta no tiene los permisos necesarios para acceder a esta sección.' }}
        </p>

        <div class="alert alert-warning" role="alert">
            <strong>¿Necesitas acceso?</strong><br>
            {{ $contactMessage ?? 'Por favor, contacta a un administrador del sistema para solicitar permisos de acceso.' }}
        </div>

        <div class="mt-4">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-lg">
                ← Volver al Dashboard
            </a>
        </div>

        <div class="mt-3">
            <small class="text-muted">
                Si crees que esto es un error, contacta al administrador del sistema.
            </small>
        </div>
    </div>
</div>
@endsection
