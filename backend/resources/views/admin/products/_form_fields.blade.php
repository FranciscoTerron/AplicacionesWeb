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
    'name' => 'category_id',
    'label' => 'Categoría',
    'type' => 'select',
    'required' => true,
    'options' => $categories->pluck('name', 'id')->toArray(),
    'selected' => old('category_id', $item['category_id'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'subcategory_id',
    'label' => 'Subcategoría (opcional)',
    'type' => 'select',
    'options' => ['' => 'Sin subcategoría'] + $subcategories->pluck('name', 'id')->toArray(),
    'selected' => old('subcategory_id', $item['subcategory_id'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'sku', // Unidad de Mantenimiento de Stock
    'label' => 'SKU',
    'type' => 'text',
    'required' => true,
    'value' => old('sku', $item['sku'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'price',
    'label' => 'Precio',
    'type' => 'number',
    'step' => '0.01',
    'required' => true,
    'value' => old('price', $item['price'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'cost',
    'label' => 'Costo (opcional)',
    'type' => 'number',
    'step' => '0.01',
    'value' => old('cost', $item['cost'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'stock',
    'label' => 'Stock',
    'type' => 'number',
    'required' => true,
    'value' => old('stock', $item['stock'] ?? 0)
])

@include('admin.components.form-field', [
    'name' => 'min_stock',
    'label' => 'Stock Mínimo',
    'type' => 'number',
    'required' => true,
    'value' => old('min_stock', $item['min_stock'] ?? 0)
])

@include('admin.components.form-field', [
    'name' => 'main_image',
    'label' => 'URL Imagen Principal',
    'type' => 'text',
    'placeholder' => 'https://...',
    'value' => old('main_image', $item['main_image'] ?? '')
])

@include('admin.components.form-field', [
    'name' => 'images[]',
    'label' => 'Galería de Imágenes (URLs, una por línea)',
    'type' => 'textarea',
    'placeholder' => "https://...\nhttps://...",
    'value' => old('images', implode("\n", $item['images'] ?? []))
])

@include('admin.components.form-field', [
    'name' => 'featured',
    'label' => 'Destacado',
    'type' => 'checkbox',
    'checked' => old('featured', $item['featured'] ?? false)
])

@include('admin.components.form-field', [
    'name' => 'active',
    'label' => 'Estado',
    'type' => 'select',
    'options' => [1 => 'Activo', 0 => 'Inactivo'],
    'selected' => old('active', $item['active'] ?? 1)
])