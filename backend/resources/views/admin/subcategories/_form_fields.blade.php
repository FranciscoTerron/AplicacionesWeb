@php
$categories = $categories ?? collect([]);
@endphp

@include('admin.components.form-field', [
    'name' => 'name',
    'label' => 'Nombre',
    'type' => 'text',
    'required' => true,
    'value' => old('name')
])

@include('admin.components.form-field', [
    'name' => 'description',
    'label' => 'Descripción',
    'type' => 'textarea',
    'value' => old('description')
])

@include('admin.components.form-field', [
    'name' => 'categoryId',
    'label' => 'Categoría',
    'type' => 'select',
    'required' => true,
    'options' => $categories->pluck('name', 'name')->toArray(),
    'selected' => old('categoryId', $item['categoryId'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'active',
    'label' => 'Estado',
    'type' => 'select',
    'options' => [1 => 'Activo', 0 => 'Inactivo'],
    'selected' => old('active', $item['active'] ?? 1)
])