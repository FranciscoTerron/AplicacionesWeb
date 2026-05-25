@php
$navItems = [
    'main' => [
        'title' => 'Principal',
        'items' => [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'roles' => ['admin', 'editor']],
        ]
    ],
    'commerce' => [
        'title' => 'Comercio',
        'items' => [
            ['route' => 'admin.products.index', 'label' => 'Productos', 'icon' => 'products', 'roles' => ['admin', 'editor']],
            ['route' => 'admin.categories.index', 'label' => 'Categorías', 'icon' => 'categories', 'roles' => ['admin', 'editor']],
            ['route' => 'admin.subcategories.index', 'label' => 'Subcategorías', 'icon' => 'categories', 'roles' => ['admin', 'editor']],
            ['route' => 'admin.discounts.index', 'label' => 'Descuentos', 'icon' => 'discounts', 'roles' => ['admin', 'editor']],
        ]
    ],
    'management' => [
        'title' => 'Gestión',
        'items' => [
            ['route' => 'admin.orders.index', 'label' => 'Pedidos', 'icon' => 'orders', 'roles' => ['admin', 'editor']],
            ['route' => 'admin.shipments.index', 'label' => 'Envíos', 'icon' => 'orders', 'roles' => ['admin', 'editor']],
            ['route' => 'admin.clients.index', 'label' => 'Clientes', 'icon' => 'customers', 'roles' => ['admin', 'editor']],
            ['route' => 'admin.users.index', 'label' => 'Usuarios', 'icon' => 'users', 'roles' => ['admin']],
        ]
    ],
    'system' => [
        'title' => 'Sistema',
        'items' => [
            ['route' => 'admin.settings', 'label' => 'Configuración', 'icon' => 'settings', 'roles' => ['admin', 'editor']],
        ]
    ]
];

$currentRole = Auth::user()->role ?? 'editor';
$currentRoute = Route::currentRouteName();

$iconPaths = [
    'dashboard' => '<path d="M3 3v18h18V3H3zm16 16H5V5h14v14z"/><path d="M7 12h2v5H7zm4-3h2v8h-2zm4-3h2v11h-2z"/>',
    'products' => '<path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V6h16v12z"/><path d="M6 10h2v2H6zm0 4h2v2H6zm4-4h8v2h-8zm0 4h8v2h-8z"/>',
    'categories' => '<path d="M12 2l-5.5 9h11L12 2zm0 3.84L13.93 9h-3.87L12 5.84zM17.5 13c-2.49 0-4.5 2.01-4.5 4.5s2.01 4.5 4.5 4.5 4.5-2.01 4.5-4.5-2.01-4.5-4.5-4.5zm0 7c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5zM3 21.5h8v-8H3v8zm2-6h4v4H5v-4z"/>',
    'discounts' => '<path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/>',
    'users' => '<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
    'orders' => '<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>',
    'customers' => '<path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>',
    'settings' => '<path d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>',
];
@endphp

<aside class="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('admin.dashboard') }}" class="sidebar-logo">
            {{ $settings['store_name'] ?? 'MA Piscinas' }} <span>Admin</span>
        </a>
    </div>

    <nav class="sidebar-nav">
        @foreach($navItems as $section)
            @php
                $filteredItems = array_filter($section['items'], function ($item) use ($currentRole) {
                    return in_array($currentRole, $item['roles']);
                });
            @endphp
            @if(count($filteredItems) > 0)
                <div class="nav-section">
                    <div class="nav-section-title">{{ $section['title'] }}</div>
                    @foreach($filteredItems as $item)
                        @php
                            $isActive = $currentRoute === $item['route'] || (str_starts_with($currentRoute, $item['route']) && $item['route'] !== 'admin.dashboard');
                        @endphp
                        <a href="{{ route($item['route']) }}" class="nav-link {{ $isActive ? 'active' : '' }}">
                            <span class="nav-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    {!! $iconPaths[$item['icon']] ?? '' !!}
                                </svg>
                            </span>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 2)) }}
            </div>
            <div class="user-details">
                <div class="user-name">{{ Auth::user()->name ?? 'Admin' }}</div>
                <div class="user-role">{{ Auth::user()->role ?? 'admin' }}</div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout-btn" title="Cerrar sesión">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>