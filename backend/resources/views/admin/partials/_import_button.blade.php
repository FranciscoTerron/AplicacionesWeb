@php
    $importableEntities = [
        'categories' => 'categories',
        'subcategories' => 'subcategories',
        'products' => 'products',
    ];
    $entity = $entityName ?? null;
    $userRole = Auth::user()?->role ?? null;
@endphp

@if(isset($importableEntities[$entity]) && $userRole === 'admin')
<div class="dropdown">
    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Opciones de importación">
        <i class="bi bi-upload"></i> Importar
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a href="{{ route('admin.import.create', $entity) }}" class="dropdown-item" aria-label="Importar desde CSV">
                <i class="bi bi-file-text"></i> Importar CSV
            </a>
        </li>
    </ul>
</div>
@endif