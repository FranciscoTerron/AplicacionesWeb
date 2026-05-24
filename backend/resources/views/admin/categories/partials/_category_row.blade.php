<tr class="{{ !$isActive ? 'table-light' : '' }}">
    <td>{{ $category['name'] ?? 'N/A' }}</td>
    <td><code>{{ $category['slug'] ?? 'N/A' }}</code></td>
    <td>{{ Str::limit($category['description'] ?? '', 50) }}</td>
    <td>
        @if($isActive)
            <span class="badge bg-success">Activo</span>
        @else
            <span class="badge bg-danger">Inactivo</span>
        @endif
    </td>
    <td>
        <!-- Ver -->
        <button type="button" class="btn btn-sm btn-outline-primary"
            onclick="openModal('show', {{ json_encode($category) }}, this)"
            title="Ver detalles"
            aria-label="Ver detalles de {{ $category['name'] ?? '' }}">
            <i class="bi bi-eye">Ver</i>
        </button>

        <!-- Editar -->
        @if($canManage ?? $isAdmin)
            <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="openModal('edit', {{ json_encode($category) }}, this)"
                title="Editar"
                aria-label="Editar {{ $category['name'] ?? '' }}">
                <i class="bi bi-pencil">Editar</i>
            </button>
        @else
            <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                title="Solo los administradores pueden editar categorías">
                <i class="bi bi-pencil">Editar</i>
            </button>
        @endif

        <!-- Desactivar/Activar -->
        @if($canManage ?? $isAdmin)
            @if($isActive)
                <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="openModal('deactivate', {{ json_encode($category) }}, this)"
                    title="Desactivar categoría"
                    aria-label="Desactivar {{ $category['name'] ?? '' }}">
                    <i class="bi bi-lock">Desactivar</i>
                </button>
            @else
                <button type="button" class="btn btn-sm btn-outline-success"
                    onclick="openModal('activate', {{ json_encode($category) }}, this)"
                    title="Activar categoría"
                    aria-label="Activar {{ $category['name'] ?? '' }}">
                    <i class="bi bi-unlock">Activar</i>
                </button>
            @endif
        @else
            <button type="button" class="btn btn-sm btn-outline-danger" disabled
                title="Solo los administradores pueden cambiar el estado">
                <i class="bi bi-lock">Desactivar</i>
            </button>
        @endif
    </td>
</tr>