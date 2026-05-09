<tr class="{{ !$isActive ? 'table-light' : '' }}">
    <td>
        <strong>{{ $product['name'] ?? 'N/A' }}</strong>
        @if(!empty($product['description']))
            <br><small class="text-muted">{{ Str::limit($product['description'], 50) }}</small>
        @endif
    </td>
    <td>{{ $categoryName ?? 'N/A' }}</td>
    <td>${{ number_format($product['price'] ?? 0, 2, ',', '.') }}</td>
    <td>{{ $product['stock'] ?? 0 }}</td>
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
            onclick="openModal('show', {{ json_encode($product) }})"
            title="Ver detalles">
            <i class="bi bi-eye">Ver</i>
        </button>

        <!-- Editar -->
        @if($canManage ?? $isAdmin)
            <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="openModal('edit', {{ json_encode($product) }})"
                title="Editar">
                <i class="bi bi-pencil">Editar</i>
            </button>
        @else
            <button type="button" class="btn btn-outline-secondary btn-sm" disabled
                title="No tienes permisos para editar productos">
                <i class="bi bi-pencil">Editar</i>
            </button>
        @endif

        <!-- Desactivar/Activar -->
        @if($canManage ?? $isAdmin)
            @if($isActive)
                <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="openModal('deactivate', {{ json_encode($product) }})"
                    title="Desactivar producto">
                    <i class="bi bi-lock">Desactivar</i>
                </button>
            @else
                <button type="button" class="btn btn-sm btn-outline-success"
                    onclick="openModal('activate', {{ json_encode($product) }})"
                    title="Activar producto">
                    <i class="bi bi-unlock">Activar</i>
                </button>
            @endif
        @else
            <button type="button" class="btn btn-sm btn-outline-danger" disabled
                title="No tienes permisos para cambiar el estado">
                <i class="bi bi-lock">Desactivar</i>
            </button>
        @endif
    </td>
</tr>
