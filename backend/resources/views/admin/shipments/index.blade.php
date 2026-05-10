@extends('layouts.admin')

@section('title', 'Envíos - MA Piscinas')
@section('page-title', 'Envíos')
@section('page-subtitle', 'Gestión de envíos de pedidos')

@section('content')
@php
    $currentUser = Auth::user();
    $currentUserRole = $currentUser?->role ?? 'editor';
    $isAdmin = $currentUserRole === 'admin';
@endphp

<div class="flex justify-between items-center mb-4">
    <div>
        <h1>Envíos</h1>
    </div>
        @if($isAdmin)
            <a href="{{ route('admin.shipments.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nuevo Envío
            </a>
        @endif
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.shipments.index') }}" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="search" value="{{ $search ?? '' }}"
                       class="form-control" placeholder="Buscar por orden, tracking, transportista o dirección">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">Todos los estados</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ ($statusFilter ?? '') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
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
                <th>Orden</th>
                <th>Tracking</th>
                <th>Transportista</th>
                <th>Estado</th>
                <th>Dirección</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shipments as $shipment)
                @php
                    $shipmentId = $shipment['id'] ?? '';
                    $statusKey = $shipment['status'] ?? 'preparing';
                    $statusLabel = $statuses[$statusKey] ?? $statusKey;
                    $isActive = $shipment['active'] ?? true;
                    $badgeClass = match($statusKey) {
                        'preparing' => 'bg-secondary',
                        'in_transit' => 'bg-warning text-dark',
                        'delivered' => 'bg-success',
                        'cancelled' => 'bg-danger',
                        default => 'bg-light text-dark',
                    };
                @endphp
                <tr>
                    <td><code>{{ $shipment['order_id'] ?? '—' }}</code></td>
                    <td><code>{{ $shipment['tracking_code'] ?? '—' }}</code></td>
                    <td>{{ $shipment['carrier'] ?? '—' }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                    <td>{{ \Illuminate\Support\Str::limit($shipment['address'] ?? '', 40) }}</td>
                    <td class="text-end">
                        @if($shipmentId)
                            <a href="{{ route('admin.shipments.show', $shipmentId) }}" class="btn btn-sm btn-outline-secondary" title="Ver">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('admin.shipments.edit', $shipmentId) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if($isAdmin)
                                @if($isActive)
                                    <form action="{{ route('admin.shipments.destroy', $shipmentId) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Desactivar este envío?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Desactivar">
                                            <i class="bi bi-lock"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.shipments.activate', $shipmentId) }}" method="POST" class="d-inline">
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
                        <i class="bi bi-truck display-6"></i>
                        <p class="lead mt-2">No hay envíos registrados</p>
                        <a href="{{ route('admin.shipments.create') }}" class="btn btn-primary mt-2">
                            <i class="bi bi-plus-circle"></i> Crear primer envío
                        </a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
