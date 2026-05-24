@php
    $entityMap = [
        'categories' => 'categories',
        'subcategories' => 'subcategories',
        'products' => 'products',
        'discounts' => 'discounts',
        'clients' => 'clients',
        'orders' => 'orders',
        'shipments' => 'shipments',
    ];
    $entity = $entityName ?? null;
@endphp

@if(in_array($entity, $entityMap))
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
    </ul>
</div>
@endif