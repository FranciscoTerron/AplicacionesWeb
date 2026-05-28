@php
$currentRoute = Route::currentRouteName();
$breadcrumbs = [];

$routeLabels = [
    'admin.dashboard' => 'Dashboard',
    'admin.products.index' => 'Productos',
    'admin.products.create' => 'Nuevo Producto',
    'admin.products.show' => 'Ver Producto',
    'admin.products.edit' => 'Editar Producto',
    'admin.categories.index' => 'Categorías',
    'admin.categories.create' => 'Nueva Categoría',
    'admin.categories.show' => 'Ver Categoría',
    'admin.categories.edit' => 'Editar Categoría',
    'admin.subcategories.index' => 'Subcategorías',
    'admin.subcategories.create' => 'Nueva Subcategoría',
    'admin.subcategories.show' => 'Ver Subcategoría',
    'admin.subcategories.edit' => 'Editar Subcategoría',
    'admin.discounts.index' => 'Descuentos',
    'admin.discounts.create' => 'Nuevo Descuento',
    'admin.discounts.show' => 'Ver Descuento',
    'admin.discounts.edit' => 'Editar Descuento',
    'admin.orders.index' => 'Pedidos',
    'admin.orders.show' => 'Ver Pedido',
    'admin.shipments.index' => 'Envíos',
    'admin.shipments.show' => 'Ver Envío',
    'admin.clients.index' => 'Clientes',
    'admin.clients.show' => 'Ver Cliente',
    'admin.users.index' => 'Usuarios',
    'admin.users.create' => 'Crear Usuario',
    'admin.users.show' => 'Ver Usuario',
    'admin.users.edit' => 'Editar Usuario',
    'admin.settings' => 'Configuración',
];

if ($currentRoute !== 'admin.dashboard' && str_starts_with($currentRoute, 'admin.')) {
    $parts = explode('.', $currentRoute);
    
    if (count($parts) >= 2) {
        $breadcrumbs[] = [
            'label' => 'Dashboard',
            'url' => route('admin.dashboard'),
        ];
        
        $currentPath = 'admin';
        for ($i = 1; $i < count($parts); $i++) {
            $currentPath .= '.' . $parts[$i];
            if (isset($routeLabels[$currentPath])) {
                if ($i === count($parts) - 1) {
                    $breadcrumbs[] = [
                        'label' => $routeLabels[$currentPath],
                        'url' => null,
                    ];
                } else {
                    try {
                        $breadcrumbs[] = [
                            'label' => $routeLabels[$currentPath],
                            'url' => route($currentPath),
                        ];
                    } catch (\Exception $e) {
                        $breadcrumbs[] = [
                            'label' => $routeLabels[$currentPath],
                            'url' => null,
                        ];
                    }
                }
            }
        }
    }
}
@endphp

@if(count($breadcrumbs) > 0)
<nav aria-label="Breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size: 0.875rem;">
        @foreach($breadcrumbs as $index => $crumb)
            @if($loop->last)
                <li class="breadcrumb-item active" aria-current="page" style="color: #64748b;">
                    {{ $crumb['label'] }}
                </li>
            @else
                <li class="breadcrumb-item">
                    @if($crumb['url'])
                        <a href="{{ $crumb['url'] }}" style="color: #0284c7; text-decoration: none;">{{ $crumb['label'] }}</a>
                    @else
                        <span style="color: #64748b;">{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endif
        @endforeach
    </ol>
</nav>
@endif