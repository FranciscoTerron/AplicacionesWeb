<tr class="{{ !$isActive ? 'table-light' : '' }}">
    <td>{{ $user['name'] ?? 'N/A' }}</td>
    <td>{{ $user['email'] ?? 'N/A' }}</td>
    <td>
        @if($user['role'] == 'admin')
            <span class="badge bg-primary">Administrador</span>
        @else
            <span class="badge bg-secondary">Editor</span>
        @endif
    </td>
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
            onclick="openModal('show', {{ json_encode($user) }})"
            title="Ver detalles">
            <i class="bi bi-eye">Ver</i>
        </button>

        <!-- Editar -->
        @if($isAdmin || $isOwnProfile)
            <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="openModal('edit', {{ json_encode($user) }})"
                title="Editar">
                <i class="bi bi-pencil">Editar</i>
            </button>
        @else
            <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                title="No puedes editar este usuario">
                <i class="bi bi-pencil">Editar</i>
            </button>
        @endif

        <!-- Bloquear/Desbloquear -->
        @if($isAdmin || $isOwnProfile)
            @if($isActive)
                <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="openModal('delete', {{ json_encode($user) }})"
                    title="Bloquear usuario">
                    <i class="bi bi-lock">Bloquear</i>
                </button>
            @else
                <button type="button" class="btn btn-sm btn-outline-success"
                    onclick="openModal('activate', {{ json_encode($user) }})"
                    title="Desbloquear usuario">
                    <i class="bi bi-unlock">Desbloquear</i>
                </button>
            @endif
        @else
            <button type="button" class="btn btn-sm btn-outline-danger" disabled
                title="Solo los administradores pueden bloquear usuarios">
                <i class="bi bi-lock">Bloquear</i>
            </button>
        @endif
    </td>
</tr>