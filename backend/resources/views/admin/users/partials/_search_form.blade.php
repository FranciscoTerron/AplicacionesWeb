        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o email..." value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-2">
                <select name="role" class="form-select">
                    <option value="">Todos los roles</option>
                    <option value="admin" {{ ($roleFilter ?? '') == 'admin' ? 'selected' : '' }}>Administrador</option>
                    <option value="editor" {{ ($roleFilter ?? '') == 'editor' ? 'selected' : '' }}>Editor</option>
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
                <button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button>
                @if($search || $roleFilter || $statusFilter)
                    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary w-100 mt-1">Limpiar</a>
                @endif
            </div>
        </form>