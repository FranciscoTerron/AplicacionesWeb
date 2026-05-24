<form method="GET" action="{{ route('admin.orders.index') }}" class="row g-3">
    <div class="col-md-4">
        <input type="text" name="search" value="{{ $search ?? '' }}"
               class="form-control" placeholder="Buscar por cliente o ID..." aria-label="Buscar por cliente o ID">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}" {{ ($statusFilter ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="payment_status" class="form-select" aria-label="Filtrar por estado de pago">
            <option value="">Cualquier pago</option>
            @foreach($paymentStatuses as $key => $label)
                <option value="{{ $key }}" {{ ($paymentFilter ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button>
        @include('admin.partials._filters', ['routeName' => 'admin.orders.index'])
    </div>
</form>