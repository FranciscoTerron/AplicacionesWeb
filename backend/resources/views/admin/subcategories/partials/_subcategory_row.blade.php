<tr class="{{ !$isActive ? 'table-light' : '' }}">
    <td>{{ $subcategory['name'] ?? 'N/A' }}</td>
    <td><code>{{ $subcategory['slug'] ?? 'N/A' }}</code></td>
    <td>
        @php
            $categoryName = 'N/A';
            if (isset($subcategory['category_id'])) {
                $category = $categories->firstWhere('id', $subcategory['category_id']);
                $categoryName = $category['name'] ?? 'N/A';
            }
        @endphp
        {{ $categoryName }}
    </td>
    <td>{{ Str::limit($subcategory['description'] ?? '', 50) }}</td>
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
            onclick="openModal('show', {{ json_encode($subcategory) }})"
            title="Ver detalles">
            <i class="bi bi-eye">Ver</i>
        </button>

        <!-- Editar -->
        @if($canManage ?? $isAdmin)
            <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="openModal('edit', {{ json_encode($subcategory) }})"
                title="Editar">
                <i class="bi bi-pencil">Editar</i>
            </button>
        @else
            <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                title="Solo los administradores pueden editar subcategorías">
                <i class="bi bi-pencil">Editar</i>
            </button>
        @endif

        <!-- Desactivar/Activar -->
        @if($canManage ?? $isAdmin)
            @if($isActive)
                <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="openModal('deactivate', {{ json_encode($subcategory) }})"
                    title="Desactivar subcategoría">
                    <i class="bi bi-lock">Desactivar</i>
                </button>
            @else
                <button type="button" class="btn btn-sm btn-outline-success"
                    onclick="openModal('activate', {{ json_encode($subcategory) }})"
                    title="Activar subcategoría">
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