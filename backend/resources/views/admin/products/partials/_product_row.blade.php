<tr class="{{ !$isActive ? 'table-light' : '' }}">
    <td>
        @php
            $thumbUrl = null;
            $fullUrl = null;
            $images = $product['images'] ?? [];
            if (is_array($images)) {
                foreach ($images as $img) {
                    if (is_array($img) && ! empty($img['url'])) {
                        $fullUrl = $img['url'];
                        $thumbUrl = str_replace('/upload/', '/upload/c_thumb,w_40,h_40,g_auto/', $fullUrl);
                        break;
                    }
                }
            }
        @endphp
        <div class="d-flex align-items-center gap-2">
            @if($thumbUrl)
                <img src="{{ $thumbUrl }}" alt="Imagen de {{ $product['name'] ?? '' }}"
                     onclick="showImageLightbox('{{ e($fullUrl) }}', '{{ e($product['name'] ?? '') }}')"
                     style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid var(--border);flex:0 0 auto;cursor:zoom-in;"
                     title="Click para ampliar">
            @endif
            <div>
                <strong>{{ $product['name'] ?? 'N/A' }}</strong>
                @if(!empty($product['description']))
                    <br><small class="text-muted">{{ Str::limit($product['description'], 50) }}</small>
                @endif
            </div>
        </div>
    </td>
    <td>{{ $categoryName ?? 'N/A' }}</td>
    <td>
        @php
            $hasDisc = ! empty($product['has_discount']);
            $basePrice = $product['price'] ?? 0;
            $finalPrice = $product['final_price'] ?? $basePrice;
            $disc = $product['discount'] ?? null;
        @endphp
        @if($hasDisc)
            <span class="text-muted text-decoration-line-through small">${{ number_format($basePrice, 2, ',', '.') }}</span>
            <br>
            <strong class="text-success">${{ number_format($finalPrice, 2, ',', '.') }}</strong>
            <span class="badge bg-success ms-1"
                  title="{{ $disc['name'] ?? 'Descuento' }}{{ ! empty($disc['code']) ? ' ('.$disc['code'].')' : '' }}">
                -{{ $disc['percent_off'] ?? 0 }}%
            </span>
        @else
            ${{ number_format($basePrice, 2, ',', '.') }}
        @endif
    </td>
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