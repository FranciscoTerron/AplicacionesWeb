<form method="GET" action="{{ route('admin.products.index') }}" class="row g-3">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o descripción..." value="{{ $search ?? '' }}">
    </div>
    <div class="col-md-2">
        <select name="category" class="form-select">
            <option value="">Todas las categorías</option>
            @foreach($categories as $category)
                <option value="{{ $category['id'] ?? '' }}" {{ ($categoryFilter ?? '') == ($category['id'] ?? '') ? 'selected' : '' }}>
                    {{ $category['name'] ?? 'N/A' }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="subcategory" class="form-select">
            <option value="">Todas las subcategorías</option>
            @foreach($subcategories as $subcategory)
                <option value="{{ $subcategory['id'] ?? '' }}" {{ ($subcategoryFilter ?? '') == ($subcategory['id'] ?? '') ? 'selected' : '' }}>
                    {{ $subcategory['name'] ?? 'N/A' }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">Todos los estados</option>
            <option value="active" {{ ($statusFilter ?? '') == 'active' ? 'selected' : '' }}>Activo</option>
            <option value="inactive" {{ ($statusFilter ?? '') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
        </select>
    </div>
    <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        @if($search || $categoryFilter || $subcategoryFilter || $statusFilter)
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary w-100 mt-1">Limpiar</a>
        @endif
    </div>
</form>
