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
            onclick="openProductModal('show', {{ json_encode($product) }}, this)"
            title="Ver detalles"
            aria-label="Ver detalles de {{ $product['name'] ?? '' }}">
            <i class="bi bi-eye"></i> Ver
        </button>

        <!-- Editar -->
        @if($canManage ?? $isAdmin)
            <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="openProductModal('edit', {{ json_encode($product) }}, this)"
                title="Editar"
                aria-label="Editar {{ $product['name'] ?? '' }}">
                <i class="bi bi-pencil"></i> Editar
            </button>
        @else
            <button type="button" class="btn btn-outline-secondary btn-sm" disabled
                title="No tienes permisos para editar productos">
                <i class="bi bi-pencil"></i> Editar
            </button>
        @endif

        <!-- Desactivar/Activar -->
        @if($canManage ?? $isAdmin)
            @if($isActive)
                <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="openProductModal('deactivate', {{ json_encode($product) }}, this)"
                    title="Desactivar producto"
                    aria-label="Desactivar {{ $product['name'] ?? '' }}">
                    <i class="bi bi-lock"></i> Desactivar
                </button>
            @else
                <button type="button" class="btn btn-sm btn-outline-success"
                    onclick="openProductModal('activate', {{ json_encode($product) }}, this)"
                    title="Activar producto"
                    aria-label="Activar {{ $product['name'] ?? '' }}">
                    <i class="bi bi-unlock"></i> Activar
                </button>
            @endif
        @else
            <button type="button" class="btn btn-sm btn-outline-danger" disabled
                title="No tienes permisos para cambiar el estado">
                <i class="bi bi-lock"></i> Desactivar
            </button>
        @endif
    </td>
</tr>