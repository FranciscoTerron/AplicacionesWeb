@include('admin.components.form-field', [
    'name' => 'order_id',
    'label' => 'Orden asociada',
    'type' => 'select',
    'required' => true,
    'options' => $orders->mapWithKeys(function ($o) {
        $label = ($o['id'] ?? '') . ' — ' . ($o['client_name'] ?? $o['client_id'] ?? 'Cliente');
        return [($o['id'] ?? '') => $label];
    })->toArray(),
    'selected' => old('order_id', $item['order_id'] ?? ''),
    'help' => 'Selecciona la orden a la que corresponde este envío'
])

@include('admin.components.form-field', [
    'name' => 'address',
    'label' => 'Dirección de envío',
    'type' => 'textarea',
    'required' => true,
    'value' => old('address', $item['address'] ?? ''),
    'help' => 'Dirección completa incluyendo código postal'
])

@include('admin.components.form-field', [
    'name' => 'carrier',
    'label' => 'Transportista',
    'type' => 'text',
    'placeholder' => 'OCA, Andreani, Correo Argentino...',
    'value' => old('carrier', $item['carrier'] ?? ''),
    'help' => 'Nombre de la empresa de mensajería'
])

@include('admin.components.form-field', [
    'name' => 'tracking_code',
    'label' => 'Código de seguimiento',
    'type' => 'text',
    'value' => old('tracking_code', $item['tracking_code'] ?? ''),
    'help' => 'Número de seguimiento proporcionado por el transportista'
])

@include('admin.components.form-field', [
    'name' => 'status',
    'label' => 'Estado',
    'type' => 'select',
    'required' => true,
    'options' => $statuses,
    'selected' => old('status', $item['status'] ?? 'preparing'),
    'help' => 'Estado actual del envío'
])

@include('admin.components.form-field', [
    'name' => 'shipped_at',
    'label' => 'Fecha de despacho',
    'type' => 'date',
    'value' => old('shipped_at', isset($item['shipped_at']) ? \Illuminate\Support\Str::of($item['shipped_at'])->substr(0, 10) : ''),
    'help' => 'Fecha en que se entregó el paquete al transportista'
])

@include('admin.components.form-field', [
    'name' => 'delivered_at',
    'label' => 'Fecha de entrega',
    'type' => 'date',
    'value' => old('delivered_at', isset($item['delivered_at']) ? \Illuminate\Support\Str::of($item['delivered_at'])->substr(0, 10) : ''),
    'help' => 'Fecha en que se confirmó la entrega al cliente'
])

@include('admin.components.form-field', [
    'name' => 'notes',
    'label' => 'Notas',
    'type' => 'textarea',
    'value' => old('notes', $item['notes'] ?? ''),
    'help' => 'Observaciones adicionales sobre el envío'
])