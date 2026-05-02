@extends('layouts.admin')

@section('title', 'Dashboard - MA Piscinas')
@section('page-title', 'Dashboard')
@section('page-subtitle')
<p class="page-subtitle">Resumen general del sistema</p>
@endsection

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    @component('admin.components.stat-card', [
        'label' => 'Ventas Totales',
        'value' => '$245,680',
        'variant' => 'success',
        'trend' => '+12.5%',
        'trendUp' => true
    ])
    @endcomponent

    @component('admin.components.stat-card', [
        'label' => 'Órdenes',
        'value' => '156',
        'variant' => 'info',
        'trend' => '+8.2%',
        'trendUp' => true
    ])
    @endcomponent

    @component('admin.components.stat-card', [
        'label' => 'Clientes',
        'value' => '89',
        'variant' => 'default',
        'trend' => '+15.3%',
        'trendUp' => true
    ])
    @endcomponent

    @component('admin.components.stat-card', [
        'label' => 'Productos',
        'value' => '30',
        'variant' => 'default',
        'trend' => '+28.3%',
        'trendUp' => true
    ])
    @endcomponent
</div>

<h2 class="text-lg font-semibold text-dark mb-4">Órdenes Recientes</h2>
@component('admin.components.data-table', [
    'columns' => [
        ['key' => 'id', 'label' => 'ID'],
        ['key' => 'cliente', 'label' => 'Cliente'],
        ['key' => 'total', 'label' => 'Total'],
        ['key' => 'estado', 'label' => 'Estado'],
        ['key' => 'fecha', 'label' => 'Fecha']
    ],
    'data' => [
        ['id' => '#ORD-001', 'cliente' => 'Juan Pérez', 'total' => '$1,250', 'estado' => 'Completado', 'fecha' => '22/04/2026'],
        ['id' => '#ORD-002', 'cliente' => 'María González', 'total' => '$890', 'estado' => 'Procesando', 'fecha' => '22/04/2026'],
        ['id' => '#ORD-003', 'cliente' => 'Carlos López', 'total' => '$2,100', 'estado' => 'Pendiente', 'fecha' => '21/04/2026'],
        ['id' => '#ORD-004', 'cliente' => 'Ana Martínez', 'total' => '$560', 'estado' => 'Completado', 'fecha' => '21/04/2026'],
        ['id' => '#ORD-005', 'cliente' => 'Pedro Ruiz', 'total' => '$1,780', 'estado' => 'Cancelado', 'fecha' => '20/04/2026']
    ]
])
@endcomponent

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <div>
        <h2 class="text-lg font-semibold text-dark mb-4">Top 5 Productos</h2>
        @component('admin.components.data-table', [
            'columns' => [
                ['key' => 'producto', 'label' => 'Producto'],
                ['key' => 'ventas', 'label' => 'Ventas'],
                ['key' => 'ingresos', 'label' => 'Ingresos']
            ],
            'data' => [
                ['producto' => 'Cloro 5L', 'ventas' => '45', 'ingresos' => '$22,500'],
                ['producto' => 'Filtro Arena', 'ventas' => '28', 'ingresos' => '$56,000'],
                ['producto' => 'Bomba Auto', 'ventas' => '19', 'ingresos' => '$38,000'],
                ['producto' => 'Kit Limpieza', 'ventas' => '52', 'ingresos' => '$15,600'],
                ['producto' => 'Clorador Salino', 'ventas' => '12', 'ingresos' => '$36,000']
            ]
        ])
        @endcomponent
    </div>

    <div>
        <h2 class="text-lg font-semibold text-dark mb-4">Top 5 Clientes</h2>
        @component('admin.components.data-table', [
            'columns' => [
                ['key' => 'cliente', 'label' => 'Cliente'],
                ['key' => 'pedidos', 'label' => 'Pedidos'],
                ['key' => 'total', 'label' => 'Total gastado']
            ],
            'data' => [
                ['cliente' => 'Juan Pérez', 'pedidos' => '12', 'total' => '$45,200'],
                ['cliente' => 'María González', 'pedidos' => '8', 'total' => '$32,500'],
                ['cliente' => 'Carlos López', 'pedidos' => '6', 'total' => '$28,900'],
                ['cliente' => 'Ana Martínez', 'pedidos' => '5', 'total' => '$21,000'],
                ['cliente' => 'Pedro Ruiz', 'pedidos' => '4', 'total' => '$18,500']
            ]
        ])
        @endcomponent
    </div>
</div>
@endsection