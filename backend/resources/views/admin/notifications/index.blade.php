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

    {{-- Plantillas rápidas: precargan el formulario y solo queda completar la
         parte entre [corchetes] (queda seleccionada para tipear encima). --}}
    <div class="mb-5">
        <p class="text-sm font-medium text-dark mb-2">Plantillas rápidas</p>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="js-plantilla px-3 py-1.5 text-sm border border-gray-300 rounded-full hover:border-primary hover:text-primary transition-colors"
                data-title="🔥 ¡Nueva promoción!"
                data-body="Aprovechá [DESCUENTO]% OFF en [PRODUCTO O CATEGORÍA]. Por tiempo limitado."
                data-url="/productos">
                🔥 Promoción
            </button>
            <button type="button" class="js-plantilla px-3 py-1.5 text-sm border border-gray-300 rounded-full hover:border-primary hover:text-primary transition-colors"
                data-title="✨ ¡Recién llegado!"
                data-body="Ya podés conseguir [PRODUCTO] en la tienda. Miralo antes de que se agote."
                data-url="/productos">
                ✨ Producto nuevo
            </button>
            <button type="button" class="js-plantilla px-3 py-1.5 text-sm border border-gray-300 rounded-full hover:border-primary hover:text-primary transition-colors"
                data-title="🏷️ Oferta imperdible"
                data-body="[PRODUCTO] ahora a $[PRECIO]. Hay stock limitado, ¡no te lo pierdas!"
                data-url="/productos/[ID-DEL-PRODUCTO]">
                🏷️ Oferta puntual
            </button>
            <button type="button" class="js-plantilla px-3 py-1.5 text-sm border border-gray-300 rounded-full hover:border-primary hover:text-primary transition-colors"
                data-title="🚚 Envío gratis"
                data-body="Envío gratis en compras desde $[MONTO]. Solo por esta semana."
                data-url="/productos">
                🚚 Envío gratis
            </button>
        </div>
    </div>

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

<script>
    // Al elegir una plantilla se llenan los campos y se selecciona el primer
    // [placeholder] del mensaje para escribir directamente encima.
    document.querySelectorAll('.js-plantilla').forEach((btn) => {
        btn.addEventListener('click', () => {
            const form = document.querySelector('form[action*="notifications"]');
            form.querySelector('[name="title"]').value = btn.dataset.title;
            form.querySelector('[name="url"]').value = btn.dataset.url;

            const body = form.querySelector('[name="body"]');
            body.value = btn.dataset.body;
            const start = body.value.indexOf('[');
            const end = body.value.indexOf(']');
            body.focus();
            if (start !== -1 && end !== -1) {
                body.setSelectionRange(start, end + 1);
            }
        });
    });
</script>
@endsection
