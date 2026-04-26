@extends('layouts.admin')

@section('title', 'Detalle Pedido - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Detalle Pedido</h1>
    <div>
        <a href="{{ route('admin.orders.edit', $id) }}" class="btn btn-primary">Editar</a>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>

<div class="card">
    <div class="p-4">
        <dl class="row">
            <dt class="col-sm-3">Cliente:</dt>
            <dd class="col-sm-9">{{ $item['clientId'] ?? '-' }}</dd>

            <dt class="col-sm-3">Estado:</dt>
            <dd class="col-sm-9">
                @php $statuses = ['pending' => 'warning', 'confirmed' => 'info', 'in_process' => 'primary', 'completed' => 'success', 'cancelled' => 'danger']; @endphp
                @php $statusLabels = ['pending' => 'Pendiente', 'confirmed' => 'Confirmado', 'in_process' => 'En Proceso', 'completed' => 'Completado', 'cancelled' => 'Cancelado']; @endphp
                <span class="badge bg-{{ $statuses[$item['status'] ?? 'secondary'] }}">
                    {{ $statusLabels[$item['status']] ?? $item['status'] ?? '-' }}
                </span>
            </dd>

            <dt class="col-sm-3">Estado de Pago:</dt>
            <dd class="col-sm-9">
                @php $paymentStatuses = ['pending' => 'warning', 'paid' => 'success', 'overdue' => 'danger']; @endphp
                @php $paymentLabels = ['pending' => 'Pendiente', 'paid' => 'Pagado', 'overdue' => 'Vencido']; @endphp
                <span class="badge bg-{{ $paymentStatuses[$item['paymentStatus']] ?? 'secondary' }}">
                    {{ $paymentLabels[$item['paymentStatus']] ?? $item['paymentStatus'] ?? '-' }}
                </span>
            </dd>

            <dt class="col-sm-3">Método de Pago:</dt>
            <dd class="col-sm-9">{{ $item['paymentMethod'] ?? '-' }}</dd>

            <dt class="col-sm-3">Notas:</dt>
            <dd class="col-sm-9">{{ $item['notes'] ?? '-' }}</dd>
        </dl>

        @if(isset($item['items']) && is_array($item['items']))
        <h5 class="mt-4">Productos</h5>
        <table class="table table-sm mt-2">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($item['items'] as $orderItem)
                <tr>
                    <td>{{ $orderItem['productId'] ?? '-' }}</td>
                    <td>{{ $orderItem['quantity'] ?? 0 }}</td>
                    <td>${{ number_format($orderItem['unitPrice'] ?? 0, 2) }}</td>
                    <td>${{ number_format(($orderItem['quantity'] ?? 0) * ($orderItem['unitPrice'] ?? 0), 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection