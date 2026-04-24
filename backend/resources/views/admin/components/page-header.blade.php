@php
$currentRoute = Route::currentRouteName();
$pageTitles = [
    'admin.dashboard' => ['title' => 'Dashboard', 'subtitle' => 'Resumen general del sistema'],
    'admin.products.index' => ['title' => 'Productos', 'subtitle' => 'Gestión de productos'],
    'admin.products.create' => ['title' => 'Nuevo Producto', 'subtitle' => 'Crear producto'],
    'admin.categories.index' => ['title' => 'Categorías', 'subtitle' => 'Gestión de categorías'],
    'admin.categories.create' => ['title' => 'Nueva Categoría', 'subtitle' => 'Crear categoría'],
    'admin.subcategories.index' => ['title' => 'Subcategorías', 'subtitle' => 'Gestión de subcategorías'],
    'admin.users.index' => ['title' => 'Usuarios', 'subtitle' => 'Gestión de usuarios'],
    'admin.users.create' => ['title' => 'Crear Usuario', 'subtitle' => 'Agregar un nuevo usuario al sistema'],
    'admin.users.show' => ['title' => 'Ver Usuario', 'subtitle' => 'Información completa del usuario'],
    'admin.users.edit' => ['title' => 'Editar Usuario', 'subtitle' => 'Modificar información del usuario'],
    'admin.discounts.index' => ['title' => 'Descuentos', 'subtitle' => 'Gestión de promociones'],
    'admin.orders' => ['title' => 'Órdenes', 'subtitle' => 'Gestión de pedidos'],
    'admin.customers' => ['title' => 'Clientes', 'subtitle' => 'Gestión de clientes'],
    'admin.settings' => ['title' => 'Configuración', 'subtitle' => 'Ajustes del sistema'],
];

$page = $pageTitles[$currentRoute] ?? ['title' => 'Panel', 'subtitle' => ''];
@endphp

<div>
    <h1 class="page-title">{{ $title ?? $page['title'] }}</h1>
    @if(!empty($subtitle) || !empty($page['subtitle']))
        <p class="page-subtitle">{{ $subtitle ?? $page['subtitle'] }}</p>
    @endif
</div>