        <form method="GET" action="{{ route('admin.clients.index') }}" class="row g-3">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o email..." value="{{ $search ?? '' }}" aria-label="Buscar por nombre o email">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select" aria-label="Filtrar por estado">
                    <option value="">Todos los estados</option>
                    <option value="active" {{ ($statusFilter ?? '') == 'active' ? 'selected' : '' }}>Activo</option>
                    <option value="inactive" {{ ($statusFilter ?? '') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-sm btn-outline-primary" aria-label="Aplicar filtros de clientes">Filtrar</button>
                @include('admin.partials._filters', ['routeName' => 'admin.clients.index'])
            </div>
        </form>