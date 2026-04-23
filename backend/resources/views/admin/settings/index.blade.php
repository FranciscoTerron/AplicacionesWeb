@extends('layouts.admin')

@section('title', 'Configuración - MA Piscinas')
@section('page-title', 'Configuración')

@section('styles')
.settings-section {
    background: var(--white);
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.settings-section h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--border);
}

.form-group {
    margin-bottom: 1rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text);
    margin-bottom: 0.5rem;
}

.form-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.9rem;
    border: 1px solid var(--border);
    border-radius: 0.375rem;
    background: var(--white);
    color: var(--text);
    transition: border-color 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}
@endsection

@section('content')
<div class="settings-section">
    <h3>Información de la Tienda</h3>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Nombre de la Tienda</label>
            <input type="text" class="form-input" value="MA Piscinas">
        </div>
        <div class="form-group">
            <label class="form-label">Email de Contacto</label>
            <input type="email" class="form-input" value="contacto@mapiscinas.com">
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Descripción</label>
        <input type="text" class="form-input" value="E-commerce de piscinas e insumos de mantenimiento">
    </div>
</div>

<div class="settings-section">
    <h3>Configuración de Pedidos</h3>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Estado por Defecto</label>
            <select class="form-input">
                <option>Pendiente</option>
                <option selected>Procesando</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Moneda</label>
            <select class="form-input">
                <option selected>ARS (Peso Argentino)</option>
                <option>USD (Dólar)</option>
            </select>
        </div>
    </div>
</div>

<div class="settings-section">
    <h3>Integraciones</h3>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Mercado Pago - Client ID</label>
            <input type="text" class="form-input" placeholder="Ingrese el Client ID">
        </div>
        <div class="form-group">
            <label class="form-label">Mercado Pago - Client Secret</label>
            <input type="password" class="form-input" placeholder="••••••••">
        </div>
    </div>
</div>
@endsection