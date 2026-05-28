@php
    $entityMap = [
        'categories' => ['roles' => ['admin', 'editor']],
        'subcategories' => ['roles' => ['admin', 'editor']],
        'products' => ['roles' => ['admin', 'editor']],
        'discounts' => ['roles' => ['admin', 'editor']],
        'clients' => ['roles' => ['admin']],
        'orders' => ['roles' => ['admin']],
        'shipments' => ['roles' => ['admin']],
    ];
    $entity = $entityName ?? null;
    $userRole = Auth::user()?->role ?? null;
@endphp

@if(isset($entityMap[$entity]) && $userRole && in_array($userRole, $entityMap[$entity]['roles']))
<a href="{{ route('admin.export.csv', $entity) }}" class="btn btn-outline-secondary" aria-label="Exportar a CSV">
    <i class="bi bi-download"></i> Exportar CSV
</a>
@endif