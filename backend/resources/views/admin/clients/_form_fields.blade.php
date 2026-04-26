@include('admin.components.form-field', [
    'name' => 'name',
    'label' => 'Nombre',
    'type' => 'text',
    'required' => true,
    'value' => old('name', $item['name'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'email',
    'label' => 'Correo Electrónico',
    'type' => 'email',
    'required' => true,
    'value' => old('email', $item['email'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'phone',
    'label' => 'Teléfono',
    'type' => 'tel',
    'value' => old('phone', $item['phone'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'address',
    'label' => 'Dirección',
    'type' => 'textarea',
    'value' => old('address', $item['address'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'city',
    'label' => 'Ciudad',
    'type' => 'text',
    'value' => old('city', $item['city'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'notes',
    'label' => 'Notas',
    'type' => 'textarea',
    'value' => old('notes', $item['notes'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'active',
    'label' => 'Estado',
    'type' => 'select',
    'options' => [1 => 'Activo', 0 => 'Inactivo'],
    'selected' => old('active', $item['active'] ?? 1)
])