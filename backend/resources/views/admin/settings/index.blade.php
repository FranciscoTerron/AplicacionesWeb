@extends('layouts.admin')

@section('title', 'Configuración - MA Piscinas')
@section('page-title', 'Configuración')

@section('content')
<div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
    <h3 class="text-lg font-semibold text-dark mb-4 pb-3 border-b">Información de la Tienda</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="mb-4">
            <label class="block text-sm font-medium text-dark mb-2">Nombre de la Tienda</label>
            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none" value="MA Piscinas">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-dark mb-2">Email de Contacto</label>
            <input type="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none" value="contacto@mapiscinas.com">
        </div>
    </div>
    <div class="mb-4">
        <label class="block text-sm font-medium text-dark mb-2">Descripción</label>
        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none" value="E-commerce de piscinas e insumos de mantenimiento">
    </div>
</div>

<div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
    <h3 class="text-lg font-semibold text-dark mb-4 pb-3 border-b">Configuración de Pedidos</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="mb-4">
            <label class="block text-sm font-medium text-dark mb-2">Estado por Defecto</label>
            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none bg-white">
                <option>Pendiente</option>
                <option selected>Procesando</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-dark mb-2">Moneda</label>
            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none bg-white">
                <option selected>ARS (Peso Argentino)</option>
                <option>USD (Dólar)</option>
            </select>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
    <h3 class="text-lg font-semibold text-dark mb-4 pb-3 border-b">Integraciones</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="mb-4">
            <label class="block text-sm font-medium text-dark mb-2">Mercado Pago - Client ID</label>
            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none" placeholder="Ingrese el Client ID">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-dark mb-2">Mercado Pago - Client Secret</label>
            <input type="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none" placeholder="••••••••">
        </div>
    </div>
</div>
@endsection