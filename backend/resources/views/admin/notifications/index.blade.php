@extends('layouts.admin')

@section('title', 'Notificaciones push - MA Piscinas')
@section('page-title', 'Notificaciones push')

@section('content')
<div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
    <h3 class="text-lg font-semibold text-dark mb-1 pb-3 border-b">Enviar notificación a la tienda</h3>
    <p class="text-sm text-muted mb-4 mt-3">
        Se envía a todos los dispositivos que activaron las notificaciones en la tienda
        (promociones, productos nuevos, ofertas).
        <strong>Dispositivos suscriptos: {{ $subscriberCount }}</strong>
    </p>

    @if(!$configured)
        <div class="alert alert-warning">
            Las claves VAPID no están configuradas (<code>VAPID_PUBLIC_KEY</code> /
            <code>VAPID_PRIVATE_KEY</code> en el .env). El envío está deshabilitado.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.notifications.send') }}">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-medium text-dark mb-2">Título</label>
            <input type="text" name="title" value="{{ old('title') }}" maxlength="100" required
                placeholder="Ej: ¡Nueva promo de verano!"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
            @error('title')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-dark mb-2">Mensaje</label>
            <textarea name="body" rows="3" maxlength="255" required
                placeholder="Ej: 20% de descuento en cloro y químicos hasta el domingo."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">{{ old('body') }}</textarea>
            @error('body')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-dark mb-2">Ruta de destino (opcional)</label>
            <input type="text" name="url" value="{{ old('url') }}" maxlength="500"
                placeholder="/productos/abc123 (al tocar la notificación se abre esta página; por defecto, la home)"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
            @error('url')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-6">
            <button type="submit" class="btn btn-sm btn-outline-primary" @if(!$configured) disabled @endif>
                Enviar notificación
            </button>
        </div>
    </form>
</div>
@endsection
