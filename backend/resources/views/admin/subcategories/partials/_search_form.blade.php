        <form method="GET" action="{{ route('admin.subcategories.index') }}" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o slug..." value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $category)
                        <option value="{{ $category['id'] }}" {{ ($categoryFilter ?? '') == $category['id'] ? 'selected' : '' }}>
                            {{ $category['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="active" {{ ($statusFilter ?? '') == 'active' ? 'selected' : '' }}>Activo</option>
                    <option value="inactive" {{ ($statusFilter ?? '') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button>
                @if($search || $categoryFilter || $statusFilter)
                    <a href="{{ route('admin.subcategories.index') }}" class="btn btn-sm btn-outline-secondary w-100 mt-1">Limpiar</a>
                @endif
            </div>
        </form>