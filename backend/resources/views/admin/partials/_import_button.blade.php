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
<a href="{{ route('admin.import.create', $entity) }}" class="btn btn-outline-secondary" aria-label="Importar desde CSV">
    <i class="bi bi-upload"></i> Importar CSV
</a>
@endif