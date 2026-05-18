@php
$clients = $clients ?? collect([]);
$products = $products ?? collect([]);
@endphp

@include('admin.components.form-field', [
    'name' => 'clientId',
    'label' => 'Cliente',
    'type' => 'select',
    'required' => true,
    'options' => $clients->pluck('name', 'name')->toArray(),
    'selected' => old('clientId')
])

<div class="mb-3">
    <label class="form-label">Productos <span class="text-danger">*</span></label>
    <div id="order-items" class="mb-2">
        <div class="row g-2 mb-2 item-row">
            <div class="col-md-6">
                <select name="items[0][productId]" class="form-select">
                    <option value="">Seleccionar producto...</option>
                    @foreach($products as $product)
                        <option value="{{ $product['name'] }}">{{ $product['name'] }} - ${{ number_format($product['price'] ?? 0, 2) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" name="items[0][quantity]" class="form-control" placeholder="Cantidad" min="1" value="1">
            </div>
            <div class="col-md-3">
                <input type="number" name="items[0][unitPrice]" class="form-control" placeholder="Precio" step="0.01">
            </div>
        </div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addItem()">+ Agregar Producto</button>
</div>

@include('admin.components.form-field', [
    'name' => 'status',
    'label' => 'Estado',
    'type' => 'select',
    'options' => ['pending' => 'Pendiente', 'confirmed' => 'Confirmado', 'in_process' => 'En Proceso', 'completed' => 'Completado', 'cancelled' => 'Cancelado'],
    'selected' => old('status', 'pending')
])

@include('admin.components.form-field', [
    'name' => 'paymentStatus',
    'label' => 'Estado de Pago',
    'type' => 'select',
    'options' => ['pending' => 'Pendiente', 'paid' => 'Pagado', 'overdue' => 'Vencido'],
    'selected' => old('paymentStatus', 'pending')
])

@include('admin.components.form-field', [
    'name' => 'paymentMethod',
    'label' => 'Método de Pago',
    'type' => 'select',
    'options' => ['cash' => 'Efectivo', 'transfer' => 'Transferencia', 'card' => 'Tarjeta', 'mercado_pago' => 'Mercado Pago'],
    'selected' => old('paymentMethod')
])

@include('admin.components.form-field', [
    'name' => 'notes',
    'label' => 'Notas',
    'type' => 'textarea',
    'value' => old('notes')
])