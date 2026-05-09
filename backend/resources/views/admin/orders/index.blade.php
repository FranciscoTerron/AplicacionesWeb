@extends('layouts.admin')

@section('title', 'Pedidos - MA Piscinas')
@section('page-title', 'Pedidos')
@section('page-subtitle', 'Gestión de pedidos del e-commerce')

@section('content')
@php
    $currentUser = Auth::user();
    $isAdmin = ($currentUser?->role ?? '') === 'admin';
    $paymentStatuses = [
        'pending' => 'Pendiente',
        'paid' => 'Pagado',
        'overdue' => 'Vencido',
    ];
@endphp

<div class="flex justify-between items-center mb-4">
    <div>
        <h1>Pedidos</h1>
    </div>
    @if($isAdmin)
        <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Pedido
        </a>
    @endif
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" value="{{ $search ?? '' }}"
                       class="form-control" placeholder="Buscar por cliente o ID">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Todos los estados</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ ($statusFilter ?? '') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="payment_status" class="form-select">
                    <option value="">Cualquier pago</option>
                    @foreach($paymentStatuses as $key => $label)
                        <option value="{{ $key }}" {{ ($paymentFilter ?? '') === $key ? 'selected' : '' }}>
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
                <th>ID</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Pago</th>
                <th>Fecha</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                @php
                    $orderId = $order['id'] ?? '';
                    $statusKey = $order['status'] ?? 'pending';
                    $statusLabel = $statuses[$statusKey] ?? $statusKey;
                    $paymentKey = $order['paymentStatus'] ?? $order['payment_status'] ?? 'pending';
                    $paymentLabel = $paymentStatuses[$paymentKey] ?? $paymentKey;
                    $total = $order['total_amount'] ?? $order['total'] ?? 0;
                    $createdAt = $order['created_at'] ?? null;
                    $statusBadge = match($statusKey) {
                        'pending' => 'bg-warning text-dark',
                        'confirmed' => 'bg-info',
                        'in_process' => 'bg-primary',
                        'completed' => 'bg-success',
                        'cancelled' => 'bg-danger',
                        default => 'bg-secondary',
                    };
                    $paymentBadge = match($paymentKey) {
                        'paid' => 'bg-success',
                        'pending' => 'bg-warning text-dark',
                        'overdue' => 'bg-danger',
                        default => 'bg-secondary',
                    };
                @endphp
                <tr>
                    <td><code>{{ \Illuminate\Support\Str::limit($orderId, 10) }}</code></td>
                    <td>{{ $order['client_name'] ?? $order['clientId'] ?? $order['client_id'] ?? '—' }}</td>
                    <td>${{ number_format((float) $total, 2, ',', '.') }}</td>
                    <td><span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span></td>
                    <td><span class="badge {{ $paymentBadge }}">{{ $paymentLabel }}</span></td>
                    <td>{{ $createdAt ? \Illuminate\Support\Str::of($createdAt)->substr(0, 10) : '—' }}</td>
                    <td class="text-end">
                        @if($orderId)
                            <a href="{{ route('admin.orders.show', $orderId) }}" class="btn btn-sm btn-outline-secondary" title="Ver">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($isAdmin)
                                <a href="{{ route('admin.orders.edit', $orderId) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.orders.destroy', $orderId) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Cancelar este pedido?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancelar">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-receipt display-6"></i>
                        <p class="lead mt-2">No hay pedidos registrados</p>
                        @if($isAdmin)
                            <a href="{{ route('admin.orders.create') }}" class="btn btn-primary mt-2">
                                <i class="bi bi-plus-circle"></i> Crear primer pedido
                            </a>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
