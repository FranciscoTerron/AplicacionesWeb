<form method="GET" action="{{ route('admin.shipments.index') }}" class="row g-3">
    <div class="col-md-6">
        <input type="text" name="search" value="{{ $search ?? '' }}"
               class="form-control" placeholder="Buscar por orden, tracking, transportista o dirección..." aria-label="Buscar por orden, tracking, transportista o dirección">
    </div>
    <div class="col-md-4">
        <select name="status" class="form-select" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}" {{ ($statusFilter ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button>
        @include('admin.partials._filters', ['routeName' => 'admin.shipments.index'])
    </div>
</form>