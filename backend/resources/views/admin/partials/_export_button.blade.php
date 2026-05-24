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
<div class="dropdown">
    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Opciones de exportación">
        <i class="bi bi-download"></i> Exportar
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a href="{{ route('admin.export.csv', $entity) }}" class="dropdown-item" aria-label="Exportar a CSV">
                <i class="bi bi-file-text"></i> Exportar CSV
            </a>
        </li>
        <li>
            <a href="{{ route('admin.export.excel', $entity) }}" class="dropdown-item" aria-label="Exportar a Excel">
                <i class="bi bi-file-earmark-excel"></i> Exportar Excel
            </a>
        </li>
    </ul>
</div>
@endif