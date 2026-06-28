<tr class="{{ !$isActive ? 'table-light' : '' }}">
    <td><strong>{{ $discount['code'] ?? '-' }}</strong></td>
    <td>{{ $discount['name'] ?? '-' }}</td>
    <td>
        @if(($discount['discount_type'] ?? '') == 'percentage')
            <span class="badge bg-info">Porcentaje</span>
        @elseif(($discount['discount_type'] ?? '') == 'fixed')
            <span class="badge bg-warning">Importe Fijo</span>
        @else
            <span class="badge bg-secondary">Desconocido</span>
        @endif
    </td>
    <td>
        @if(($discount['discount_type'] ?? '') == 'percentage')
            {{ $discount['value'] ?? 0 }}%
        @elseif(($discount['discount_type'] ?? '') == 'fixed')
            ${{ number_format($discount['value'] ?? 0, 2) }}
        @else
            {{ $discount['value'] ?? '-' }}
        @endif
    </td>
    <td>
        @if($isActive)
            <span class="badge bg-success">Activo</span>
        @else
            <span class="badge bg-danger">Inactivo</span>
        @endif
    </td>
    <td class="text-end" style="white-space: nowrap">
        <!-- Ver -->
        <button type="button" class="btn btn-sm btn-outline-primary"
            onclick="openModal('show', {{ json_encode($discount) }}, this)"
            aria-label="Ver descuento {{ $discount['name'] ?? $discount['code'] ?? '' }}">
            <i class="bi bi-eye"></i> Ver
        </button>

        <!-- Editar -->
        @if($isAdmin)
            <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="openModal('edit', {{ json_encode($discount) }}, this)"
                aria-label="Editar {{ $discount['name'] ?? $discount['code'] ?? '' }}">
                <i class="bi bi-pencil"></i> Editar
            </button>
        @endif

        <!-- Desactivar/Reactivar -->
        @if($isAdmin)
            @if($isActive)
                <button type="button" class="btn btn-sm btn-outline-danger"
                onclick="openModal('delete', {{ json_encode($discount) }}, this)"
                    aria-label="Desactivar {{ $discount['name'] ?? $discount['code'] ?? '' }}">
                    <i class="bi bi-lock"></i> Desactivar
                </button>
            @else
                <button type="button" class="btn btn-sm btn-outline-success"
                    onclick="openModal('activate', {{ json_encode($discount) }}, this)"
                    aria-label="Activar {{ $discount['name'] ?? $discount['code'] ?? '' }}">
                    <i class="bi bi-unlock"></i> Activar
                </button>
            @endif
        @endif
    </td>
</tr>