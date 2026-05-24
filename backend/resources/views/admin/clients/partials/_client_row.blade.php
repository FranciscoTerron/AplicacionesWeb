<tr class="{{ !$isActive ? 'table-light' : '' }}">
    <td>{{ $client['name'] ?? 'N/A' }}</td>
    <td>{{ $client['email'] ?? 'N/A' }}</td>
    <td>{{ $client['phone'] ?? '—' }}</td>
    <td>
        @if($isActive)
            <span class="badge bg-success">Activo</span>
        @else
            <span class="badge bg-danger">Bloqueado</span>
        @endif
    </td>
    <td>
        <!-- Ver -->
        <button type="button" class="btn btn-sm btn-outline-primary"
            onclick="openModal('show', {{ json_encode($client) }}, this)"
            aria-label="Ver detalles de {{ $client['name'] ?? '' }}">
            <i class="bi bi-eye"></i> Ver
        </button>

        <!-- Editar -->
        @if($isAdmin)
            <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="openModal('edit', {{ json_encode($client) }}, this)"
                aria-label="Editar {{ $client['name'] ?? '' }}">
                <i class="bi bi-pencil"></i> Editar
            </button>
        @endif

        <!-- Bloquear/Desbloquear -->
        @if($isAdmin)
            @if($isActive)
                <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="openModal('deactivate', {{ json_encode($client) }}, this)"
                    aria-label="Bloquear {{ $client['name'] ?? '' }}">
                    <i class="bi bi-lock"></i> Bloquear
                </button>
            @else
                <button type="button" class="btn btn-sm btn-outline-success"
                    onclick="openModal('activate', {{ json_encode($client) }}, this)"
                    aria-label="Desbloquear {{ $client['name'] ?? '' }}">
                    <i class="bi bi-unlock"></i> Desbloquear
                </button>
            @endif
        @endif
    </td>
</tr>