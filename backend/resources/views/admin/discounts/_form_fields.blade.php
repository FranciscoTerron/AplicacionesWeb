@include('admin.components.form-field', [
    'name' => 'code',
    'label' => 'Código',
    'type' => 'text',
    'required' => true,
    'value' => old('code', $item['code'] ?? ''),
    'help' => 'Código alfanumérico, se guardará en mayúsculas.'
])

@include('admin.components.form-field', [
    'name' => 'name',
    'label' => 'Nombre',
    'type' => 'text',
    'required' => true,
    'value' => old('name', $item['name'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'description',
    'label' => 'Descripción',
    'type' => 'textarea',
    'value' => old('description', $item['description'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'discount_type',
    'label' => 'Tipo de Descuento',
    'type' => 'select',
    'required' => true,
    'options' => [
        'percentage' => 'Porcentaje (%)',
        'fixed' => 'Importe Fijo ($)',
    ],
    'selected' => old('discount_type', $item['discount_type'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'value',
    'label' => 'Valor',
    'type' => 'number',
    'step' => '0.01',
    'required' => true,
    'value' => old('value', $item['value'] ?? ''),
    'help' => 'Para porcentaje: 0-100. Para fijo: monto en pesos.'
])

@include('admin.components.form-field', [
    'name' => 'max_uses',
    'label' => 'Usos Máximos (opcional)',
    'type' => 'number',
    'required' => false,
    'value' => old('max_uses', $item['max_uses'] ?? ''),
    'help' => 'Dejar vacío para usos ilimitados.'
])

@include('admin.components.form-field', [
    'name' => 'valid_from',
    'label' => 'Válido Desde',
    'type' => 'datetime-local',
    'required' => true,
    'value' => old('valid_from', isset($item['valid_from']) ? substr($item['valid_from'], 0, 16) : '')
])

@include('admin.components.form-field', [
    'name' => 'valid_to',
    'label' => 'Válido Hasta',
    'type' => 'datetime-local',
    'required' => true,
    'value' => old('valid_to', isset($item['valid_to']) ? substr($item['valid_to'], 0, 16) : '')
])

@include('admin.components.form-field', [
    'name' => 'applies_to',
    'label' => 'Aplica a',
    'type' => 'select',
    'required' => true,
    'options' => [
        'all' => 'Todos los productos',
        'categories' => 'Categorías específicas',
        'products' => 'Productos específicos',
    ],
    'selected' => old('applies_to', $item['applies_to'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'applicable_ids',
    'label' => 'IDs Aplicables (si applies_to no es "all")',
    'type' => 'textarea',
    'placeholder' => "Ingrese IDs separados por coma (ej: cat1, cat2, prod1)",
    'value' => old('applicable_ids', isset($item['applicable_ids']) ? implode(', ', $item['applicable_ids']) : ''),
    'help' => 'Solo requerido si "Aplica a" es Categorías o Productos.'
])

@include('admin.components.form-field', [
    'name' => 'active',
    'label' => 'Estado',
    'type' => 'select',
    'options' => [1 => 'Activo', 0 => 'Inactivo'],
    'selected' => old('active', $item['active'] ?? 1)
])
