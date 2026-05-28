@extends('layouts.admin')

@section('title', 'Configuración - MA Piscinas')
@section('page-title', 'Configuración')

@section('content')
<div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
    <h3 class="text-lg font-semibold text-dark mb-4 pb-3 border-b">Información de la Tienda</h3>
    
    <form method="POST" action="{{ route('admin.settings.update') }}" id="settings-form">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium text-dark mb-2">Nombre de la Tienda</label>
                <input type="text" name="store_name" value="{{ old('store_name', $settings['store_name'] ?? 'MA Piscinas') }}" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                    @if(!auth()->user()->isAdmin()) disabled @endif>
                @error('store_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-dark mb-2">Email de Contacto</label>
                <input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email'] ?? 'contacto@mapiscinas.com') }}" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                    @if(!auth()->user()->isAdmin()) disabled @endif>
                @error('contact_email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-dark mb-2">Descripción</label>
            <input type="text" name="description" value="{{ old('description', $settings['description'] ?? 'E-commerce de piscinas e insumos de mantenimiento') }}" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                @if(!auth()->user()->isAdmin()) disabled @endif>
            @error('description')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        @if(auth()->user()->isAdmin())
            <div class="mt-6">
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    Guardar Cambios
                </button>
            </div>
        @endif
    </form>
</div>

<div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
    <h3 class="text-lg font-semibold text-dark mb-4 pb-3 border-b">Configuración de Pedidos</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="mb-4">
            <label class="block text-sm font-medium text-dark mb-2">Estado por Defecto</label>
            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none bg-white" disabled>
                <option>Pendiente</option>
                <option selected>Procesando</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-dark mb-2">Moneda</label>
            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none bg-white" disabled>
                <option selected>ARS (Peso Argentino)</option>
                <option>USD (Dólar)</option>
            </select>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
    <h3 class="text-lg font-semibold text-dark mb-4 pb-3 border-b">Integraciones</h3>
    <p class="text-sm text-gray-500 mb-4">Configuración de Mercado Pago (próximamente)</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="mb-4">
            <label class="block text-sm font-medium text-dark mb-2">Mercado Pago - Client ID</label>
            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none" placeholder="Ingrese el Client ID" disabled>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-dark mb-2">Mercado Pago - Client Secret</label>
            <input type="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none" placeholder="••••••••" disabled>
        </div>
    </div>
</div>
@endsection