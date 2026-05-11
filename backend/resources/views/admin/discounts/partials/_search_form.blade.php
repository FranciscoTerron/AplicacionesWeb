        <form method="GET" action="{{ route('admin.discounts.index') }}" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Buscar por código o nombre..." value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">Todos los tipos</option>
                    <option value="percentage" {{ ($typeFilter ?? '') == 'percentage' ? 'selected' : '' }}>Porcentaje (%)</option>
                    <option value="fixed" {{ ($typeFilter ?? '') == 'fixed' ? 'selected' : '' }}>Importe Fijo ($)</option>
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
                @if($search || $typeFilter || $statusFilter)
                    <a href="{{ route('admin.discounts.index') }}" class="btn btn-sm btn-outline-secondary w-100 mt-1">Limpiar</a>
                @endif
            </div>
        </form>