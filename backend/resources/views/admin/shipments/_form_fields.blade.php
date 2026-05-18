@include('admin.components.form-field', [
    'name' => 'order_id',
    'label' => 'Orden asociada',
    'type' => 'select',
    'required' => true,
    'options' => $orders->mapWithKeys(function ($o) {
        $label = ($o['id'] ?? '') . ' — ' . ($o['client_name'] ?? $o['client_id'] ?? 'Cliente');
        return [($o['id'] ?? '') => $label];
    })->toArray(),
    'selected' => old('order_id', $item['order_id'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'address',
    'label' => 'Dirección de envío',
    'type' => 'textarea',
    'required' => true,
    'value' => old('address', $item['address'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'carrier',
    'label' => 'Transportista',
    'type' => 'text',
    'placeholder' => 'OCA, Andreani, Correo Argentino...',
    'value' => old('carrier', $item['carrier'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'tracking_code',
    'label' => 'Código de seguimiento',
    'type' => 'text',
    'value' => old('tracking_code', $item['tracking_code'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'status',
    'label' => 'Estado',
    'type' => 'select',
    'required' => true,
    'options' => $statuses,
    'selected' => old('status', $item['status'] ?? 'preparing')
])

@include('admin.components.form-field', [
    'name' => 'shipped_at',
    'label' => 'Fecha de despacho',
    'type' => 'date',
    'value' => old('shipped_at', isset($item['shipped_at']) ? \Illuminate\Support\Str::of($item['shipped_at'])->substr(0, 10) : '')
])

@include('admin.components.form-field', [
    'name' => 'delivered_at',
    'label' => 'Fecha de entrega',
    'type' => 'date',
    'value' => old('delivered_at', isset($item['delivered_at']) ? \Illuminate\Support\Str::of($item['delivered_at'])->substr(0, 10) : '')
])

@include('admin.components.form-field', [
    'name' => 'notes',
    'label' => 'Notas',
    'type' => 'textarea',
    'value' => old('notes', $item['notes'] ?? '')
])

<input type="hidden" name="active" value="1">
