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
    'name' => 'order',
    'label' => 'Orden',
    'type' => 'number',
    'required' => true,
    'value' => old('order', $item['order'] ?? 0)
])

@include('admin.components.form-field', [
    'name' => 'image',
    'label' => 'URL de Imagen (Cloudinary)',
    'type' => 'text',
    'placeholder' => 'https://res.cloudinary.com/...',
    'value' => old('image', $item['image'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'active',
    'label' => 'Estado',
    'type' => 'select',
    'options' => [1 => 'Activo', 0 => 'Inactivo'],
    'selected' => old('active', $item['active'] ?? 1)
])