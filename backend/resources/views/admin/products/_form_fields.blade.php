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
    'name' => 'price',
    'label' => 'Precio',
    'type' => 'number',
    'required' => true,
    'value' => old('price', $item['price'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'stock',
    'label' => 'Stock',
    'type' => 'number',
    'required' => true,
    'value' => old('stock', $item['stock'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'active',
    'label' => 'Estado',
    'type' => 'select',
    'options' => [1 => 'Activo', 0 => 'Inactivo'],
    'selected' => old('active', $item['active'] ?? 1)
])