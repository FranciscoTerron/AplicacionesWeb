@include('admin.components.form-field', [
    'name' => 'code',
    'label' => 'Código',
    'type' => 'text',
    'required' => true,
    'value' => old('code', $item['code'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'description',
    'label' => 'Descripción',
    'type' => 'textarea',
    'value' => old('description', $item['description'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'discountType',
    'label' => 'Tipo',
    'type' => 'select',
    'required' => true,
    'options' => ['percentage' => 'Porcentaje', 'fixed' => 'Fijo'],
    'selected' => old('discountType', $item['discountType'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'discountValue',
    'label' => 'Valor',
    'type' => 'number',
    'required' => true,
    'value' => old('discountValue', $item['discountValue'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'minPurchase',
    'label' => 'Compra Mínima',
    'type' => 'number',
    'value' => old('minPurchase', $item['minPurchase'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'validFrom',
    'label' => 'Válido Desde',
    'type' => 'date',
    'value' => old('validFrom', $item['validFrom'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'validUntil',
    'label' => 'Válido Hasta',
    'type' => 'date',
    'value' => old('validUntil', $item['validUntil'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'active',
    'label' => 'Estado',
    'type' => 'select',
    'options' => [1 => 'Activo', 0 => 'Inactivo'],
    'selected' => old('active', $item['active'] ?? 1)
])