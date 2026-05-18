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
            onclick="openModal('show', {{ json_encode($client) }})"
            title="Ver detalles">
            <i class="bi bi-eye">Ver</i>
        </button>

        <!-- Editar -->
        @if($isAdmin)
            <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="openModal('edit', {{ json_encode($client) }})"
                title="Editar">
                <i class="bi bi-pencil">Editar</i>
            </button>
        @endif

        <!-- Bloquear/Desbloquear -->
        @if($isAdmin)
            @if($isActive)
                <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="openModal('deactivate', {{ json_encode($client) }})"
                    title="Bloquear cliente">
                    <i class="bi bi-lock">Bloquear</i>
                </button>
            @else
                <button type="button" class="btn btn-sm btn-outline-success"
                    onclick="openModal('activate', {{ json_encode($client) }})"
                    title="Desbloquear cliente">
                    <i class="bi bi-unlock">Desbloquear</i>
                </button>
            @endif
        @endif
    </td>
</tr>