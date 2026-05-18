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
<tr class="{{ !$isActive ? 'table-light' : '' }}">
    <td><code>{{ $shipment['order_id'] ?? '—' }}</code></td>
    <td><code>{{ $shipment['tracking_code'] ?? '—' }}</code></td>
    <td>{{ $shipment['carrier'] ?? '—' }}</td>
    <td><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>
    <td>{{ \Illuminate\Support\Str::limit($shipment['address'] ?? '', 40) }}</td>
    <td class="text-end">
        @if($shipmentId)
            <button type="button" class="btn btn-sm btn-outline-primary"
                onclick="openModal('show', {{ json_encode($shipment) }})"
                title="Ver detalles">
                <i class="bi bi-eye"></i> Ver
            </button>

            @if($isAdmin)
                <button type="button" class="btn btn-sm btn-outline-secondary"
                    onclick="openModal('edit', {{ json_encode($shipment) }})"
                    title="Editar">
                    <i class="bi bi-pencil"></i> Editar
                </button>

                @if($isActive)
                    <button type="button" class="btn btn-sm btn-outline-danger"
                        onclick="openModal('cancel', {{ json_encode($shipment) }})"
                        title="Cancelar envío">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                @else
                    <button type="button" class="btn btn-sm btn-outline-success"
                        onclick="openModal('reactivate', {{ json_encode($shipment) }})"
                        title="Reactivar envío">
                        <i class="bi bi-arrow-counterclockwise"></i> Reactivar
                    </button>
                @endif
            @endif
        @endif
    </td>
</tr>
