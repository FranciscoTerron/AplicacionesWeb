@php
    $orderId = $order['id'] ?? '';
    $statusKey = $order['status'] ?? 'pending';
    $statusLabel = $statuses[$statusKey] ?? $statusKey;
    $paymentKey = $order['paymentStatus'] ?? $order['payment_status'] ?? 'pending';
    $paymentLabel = $paymentStatuses[$paymentKey] ?? $paymentKey;
    $total = $order['total_amount'] ?? $order['total'] ?? 0;
    if (! $total && isset($order['items']) && is_array($order['items'])) {
        $total = array_sum(array_map(
            fn ($it) => ((float) ($it['quantity'] ?? 0)) * ((float) ($it['unitPrice'] ?? $it['unit_price'] ?? 0)),
            $order['items']
        ));
    }
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
            <button type="button" class="btn btn-sm btn-outline-primary"
                onclick="openModal('show', {{ json_encode($order) }})"
                title="Ver detalles">
                <i class="bi bi-eye"></i> Ver
            </button>

            @if($isAdmin || $isEditor)
                <button type="button" class="btn btn-sm btn-outline-info"
                    onclick="openModal('status', {{ json_encode($order) }})"
                    title="Cambiar estado">
                    <i class="bi bi-arrow-repeat"></i> Estado
                </button>
            @endif

            @if($isAdmin)
                <button type="button" class="btn btn-sm btn-outline-secondary"
                    onclick="openModal('edit', {{ json_encode($order) }})"
                    title="Editar">
                    <i class="bi bi-pencil"></i> Editar
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="openModal('cancel', {{ json_encode($order) }})"
                    title="Cancelar pedido">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
            @endif
        @endif
    </td>
</tr>
