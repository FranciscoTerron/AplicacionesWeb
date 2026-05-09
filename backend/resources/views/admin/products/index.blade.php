@extends('layouts.admin')

@section('title', 'Productos - MA Piscinas')
@section('page-title', 'Productos')
@section('page-subtitle', 'Gestión de productos del catálogo')

@section('content')
@php
    $currentUser = Auth::user();
    $currentUserRole = $currentUser?->role ?? 'editor';
    $isAdmin = $currentUserRole === 'admin';
@endphp

<div class="flex justify-between items-center mb-4">
    <div>
        <h1>Productos</h1>
    </div>
    @if($isAdmin)
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Producto
        </a>
    @endif
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.products.index') }}" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" value="{{ $search ?? '' }}"
                       class="form-control" placeholder="Buscar por nombre, SKU o descripción">
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat['id'] ?? '' }}" {{ ($categoryFilter ?? '') === ($cat['id'] ?? '') ? 'selected' : '' }}>
                            {{ $cat['name'] ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="active" {{ ($statusFilter ?? '') === 'active' ? 'selected' : '' }}>Activo</option>
                    <option value="inactive" {{ ($statusFilter ?? '') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Producto</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                @php
                    $isActive = $product['active'] ?? true;
                    $productId = $product['id'] ?? '';
                @endphp
                <tr>
                    <td><code>{{ $product['sku'] ?? '—' }}</code></td>
                    <td>
                        <strong>{{ $product['name'] ?? '' }}</strong>
                        @if(($product['featured'] ?? false))
                            <span class="badge bg-warning text-dark ms-1">Destacado</span>
                        @endif
                    </td>
                    <td>${{ number_format((float)($product['price'] ?? 0), 2, ',', '.') }}</td>
                    <td>
                        {{ $product['stock'] ?? 0 }}
                        @if(($product['stock'] ?? 0) <= ($product['min_stock'] ?? 0))
                            <span class="badge bg-danger ms-1">Bajo</span>
                        @endif
                    </td>
                    <td>
                        @if($isActive)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-secondary">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if($productId)
                            <a href="{{ route('admin.products.show', $productId) }}" class="btn btn-sm btn-outline-secondary" title="Ver">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($isAdmin)
                                <a href="{{ route('admin.products.edit', $productId) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if($isActive)
                                    <form action="{{ route('admin.products.destroy', $productId) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Desactivar este producto?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Desactivar">
                                            <i class="bi bi-lock"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.products.activate', $productId) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Activar">
                                            <i class="bi bi-unlock"></i>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-box-seam display-6"></i>
                        <p class="lead mt-2">No hay productos registrados</p>
                        @if($isAdmin)
                            <a href="{{ route('admin.products.create') }}" class="btn btn-primary mt-2">
                                <i class="bi bi-plus-circle"></i> Crear primer producto
                            </a>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
