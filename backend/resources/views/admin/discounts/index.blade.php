@extends('layouts.admin')

@section('title', 'Descuentos - MA Piscinas')
@section('page-title', 'Descuentos')
@section('page-subtitle', 'Gestión de descuentos y promociones del sistema')

@section('styles')
<style>
    .modal-backdrop.show { opacity: 0.5; }
    .empty-state { text-align: center; padding: 2rem; color: #6c757d; }

    .discount-details h6 {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .discount-details .row {
        margin-bottom: 1rem;
    }

    .discount-details .progress {
        background-color: #e9ecef;
    }

    .discount-details code {
        background-color: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.85em;
    }

    .discount-details .text-muted {
        font-size: 0.85em;
    }
</style>
@endsection

@section('content')
@php
    $currentUser = Auth::user();
    $currentUserRole = $currentUser?->role ?? 'editor';
@endphp

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1>Descuentos</h1>
    @if($currentUser && $currentUserRole == 'admin')
        <button type="button" class="btn btn-primary" id="btnNewDiscount">
            <i class="bi bi-plus-circle"></i> Nuevo Descuento
        </button>
    @else
        <button type="button" class="btn btn-outline-danger btn-sm" disabled title="Solo los administradores pueden crear descuentos">
            Nuevo Descuento
        </button>
    @endif
</div>

<!-- Search and Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.discounts.index') }}" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Buscar por código o nombre..." value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">Todos los tipos</option>
                    <option value="percentage" {{ ($typeFilter ?? '') == 'percentage' ? 'selected' : '' }}>Porcentaje (%)</option>
                    <option value="fixed" {{ ($typeFilter ?? '') == 'fixed' ? 'selected' : '' }}>Importe Fijo ($)</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="active" {{ ($statusFilter ?? '') == 'active' ? 'selected' : '' }}>Activo</option>
                    <option value="inactive" {{ ($statusFilter ?? '') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                @if($search || $typeFilter || $statusFilter)
                    <a href="{{ route('admin.discounts.index') }}" class="btn btn-outline-secondary w-100 mt-1">Limpiar</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Válido Desde</th>
                <th>Válido Hasta</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $discount)
                @php
                    $isAdmin = $currentUserRole == 'admin';
                    $isActive = $discount['active'] ?? true;
                @endphp
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
                    <td>{{ isset($discount['valid_from']) ? \Carbon\Carbon::parse($discount['valid_from'])->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ isset($discount['valid_to']) ? \Carbon\Carbon::parse($discount['valid_to'])->format('d/m/Y H:i') : '-' }}</td>
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
                            onclick="openModal('show', {{ json_encode($discount) }})"
                            title="Ver detalles">
                            <i class="bi bi-eye"></i> Ver
                        </button>

                        <!-- Editar -->
                        @if($isAdmin)
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="openModal('edit', {{ json_encode($discount) }})"
                                title="Editar descuento">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                        @else
                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                title="Solo los administradores pueden editar descuentos">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                        @endif

                        <!-- Desactivar/Reactivar -->
                        @if($isAdmin)
                            @if($isActive)
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="openModal('delete', {{ json_encode($discount) }})"
                                    title="Desactivar descuento">
                                    <i class="bi bi-lock"></i> Desactivar
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-outline-success"
                                    onclick="openModal('activate', {{ json_encode($discount) }})"
                                    title="Reactivar descuento">
                                    <i class="bi bi-unlock"></i> Reactivar
                                </button>
                            @endif
                        @else
                            <button type="button" class="btn btn-sm btn-outline-danger" disabled
                                title="Solo los administradores pueden gestionar descuentos">
                                <i class="bi bi-lock"></i> Gestionar
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="bi bi-percent display-6"></i>
                        <p class="lead mt-2">No hay descuentos registrados</p>
                        @if($currentUserRole == 'admin')
                            <button type="button" class="btn btn-primary mt-2" id="btnNewDiscountEmpty">
                                <i class="bi bi-plus-circle"></i> Crear primer descuento
                            </button>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if(isset($hasMore) && ($hasMore || ($page ?? 1) > 1))
<div class="d-flex justify-content-between align-items-center mt-3">
    <div>
        @if(($page ?? 1) > 1)
            <a href="{{ route('admin.discounts.index', array_merge(['page' => ($page ?? 1) - 1, 'after' => request('after_prev')], array_filter(['search' => $search ?? '', 'type' => $typeFilter ?? '', 'status' => $statusFilter ?? '']))) }}"
               class="btn btn-outline-primary btn-sm">
               <i class="bi bi-chevron-left"></i> Anterior
            </a>
        @endif
    </div>
    <div>
        <span class="text-muted">Página {{ $page ?? 1 }}</span>
    </div>
    <div>
        @if($hasMore ?? false)
            <a href="{{ route('admin.discounts.index', array_merge(['page' => ($page ?? 1) + 1, 'after' => $lastDocumentId ?? ''], array_filter(['search' => $search ?? '', 'type' => $typeFilter ?? '', 'status' => $statusFilter ?? '']))) }}"
               class="btn btn-outline-primary btn-sm">
               Siguiente <i class="bi bi-chevron-right"></i>
            </a>
        @endif
    </div>
</div>
@endif

<!-- Modal Único Dinámico -->
<div class="modal fade" id="discountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">-</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="modalForm" action="#" method="POST">
                @csrf
                @method('POST')
                <div class="modal-body" id="modalBody">
                    <!-- Contenido dinámico -->
                </div>
                <div class="modal-footer" id="modalFooter">
                    <!-- Botones dinámicos -->
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const modal = new bootstrap.Modal(document.getElementById('discountModal'));
    let currentAction = '';
    let currentDiscount = null;

    function openModal(action, discount) {
        currentAction = action;
        currentDiscount = discount;
        const titleEl = document.getElementById('modalTitle');
        const bodyEl = document.getElementById('modalBody');
        const footerEl = document.getElementById('modalFooter');
        const formEl = document.getElementById('modalForm');

        if (action === 'show') {
            titleEl.textContent = `Detalles del Descuento: ${escapeHtml(discount.code)}`;

            // Información básica
            const basicInfo = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-2"><i class="bi bi-tag"></i> Información Básica</h6>
                        <p class="mb-1"><strong>Código:</strong> <code>${escapeHtml(discount.code)}</code></p>
                        <p class="mb-1"><strong>Nombre:</strong> ${escapeHtml(discount.name || '-')}</p>
                        <p class="mb-1"><strong>Descripción:</strong> ${discount.description ? escapeHtml(discount.description) : '<em class="text-muted">Sin descripción</em>'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-2"><i class="bi bi-graph-up"></i> Configuración del Descuento</h6>
                        <p class="mb-1"><strong>Tipo:</strong> ${discount.discount_type === 'percentage' ?
                            '<span class="badge bg-info">Porcentaje (%)</span>' :
                            discount.discount_type === 'fixed' ?
                            '<span class="badge bg-warning">Importe Fijo ($)</span>' :
                            '<span class="badge bg-secondary">Desconocido</span>'}</p>
                        <p class="mb-1"><strong>Valor:</strong> <span class="h5 text-success">${
                            discount.discount_type === 'percentage' ?
                                (discount.value || 0) + '%' :
                                discount.discount_type === 'fixed' ?
                                '$' + parseFloat(discount.value || 0).toFixed(2) :
                                (discount.value || '-')
                        }</span></p>
                        <p class="mb-1"><strong>Estado:</strong> ${discount.active ?
                            '<span class="badge bg-success">Activo</span>' :
                            '<span class="badge bg-danger">Inactivo</span>'}</p>
                    </div>
                </div>
            `;

            // Información de aplicación
            const applicationInfo = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-2"><i class="bi bi-calendar-check"></i> Vigencia</h6>
                        <p class="mb-1"><strong>Válido Desde:</strong> ${formatDateTime(discount.valid_from).replace('No disponible', '<em class="text-muted">No especificado</em>')}</p>
                        <p class="mb-1"><strong>Válido Hasta:</strong> ${formatDateTime(discount.valid_to).replace('No disponible', '<em class="text-muted">Sin límite</em>')}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-2"><i class="bi bi-shop"></i> Aplicación</h6>
                        <p class="mb-1"><strong>Aplica a:</strong> ${getAppliesToText(discount.applies_to)}</p>
                        ${discount.applies_to !== 'all' && discount.applicable_ids && discount.applicable_ids.length ?
                            `<p class="mb-1"><strong>IDs Aplicables:</strong> <small class="text-muted">${discount.applicable_ids.join(', ')}</small></p>` :
                            discount.applies_to !== 'all' ?
                            `<p class="mb-1"><strong>IDs Aplicables:</strong> <em class="text-warning">Ninguno especificado</em></p>` :
                            ''
                        }
                    </div>
                </div>
            `;

            // Información de uso
            const usageInfo = discount.max_uses || discount.used_count ? `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-2"><i class="bi bi-bar-chart"></i> Estadísticas de Uso</h6>
                        ${discount.max_uses ? `<p class="mb-1"><strong>Usos Máximos:</strong> ${discount.max_uses}</p>` : ''}
                        ${discount.used_count !== undefined ? `<p class="mb-1"><strong>Usos Realizados:</strong> ${discount.used_count}</p>` : ''}
                        ${discount.max_uses && discount.used_count !== undefined ? `
                            <div class="mb-1">
                                <strong>Progreso:</strong>
                                <div class="progress mt-1" style="height: 8px;">
                                    <div class="progress-bar ${discount.used_count >= discount.max_uses ? 'bg-danger' : 'bg-success'}"
                                         style="width: ${Math.min((discount.used_count / discount.max_uses) * 100, 100)}%">
                                    </div>
                                </div>
                                <small class="text-muted">${discount.used_count}/${discount.max_uses} usos</small>
                            </div>
                        ` : ''}
                    </div>
                    <div class="col-12">
                        <hr>
                    </div>
                </div>
            ` : '';

            // Información de auditoría
            const auditInfo = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-2"><i class="bi bi-clock-history"></i> Auditoría</h6>
                        <p class="mb-1"><strong>Creado:</strong> ${formatDateTime(discount.created_at).replace('No disponible', '<em class="text-muted">No disponible</em>')}</p>
                        ${discount.created_by ? `<p class="mb-1"><strong>Por:</strong> <small class="text-muted">${escapeHtml(discount.created_by)}</small></p>` : ''}
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-2"><i class="bi bi-pencil-square"></i> Última Modificación</h6>
                        <p class="mb-1"><strong>Actualizado:</strong> ${formatDateTime(discount.updated_at).replace('No disponible', '<em class="text-muted">No disponible</em>')}</p>
                        ${discount.updated_by ? `<p class="mb-1"><strong>Por:</strong> <small class="text-muted">${escapeHtml(discount.updated_by)}</small></p>` : ''}
                    </div>
                </div>
            `;

            bodyEl.innerHTML = `
                <div class="discount-details">
                    ${basicInfo}
                    ${applicationInfo}
                    ${usageInfo}
                    ${auditInfo}
                </div>
            `;

            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cerrar
                </button>
            `;
            formEl.setAttribute('method', 'GET');

        } else if (action === 'edit') {
            // Edit modal will be implemented in Phase 6
            alert('Funcionalidad de edición próximamente disponible.');

        } else if (action === 'new') {
            // New modal will be implemented in Phase 5
            alert('Funcionalidad de creación próximamente disponible.');

        } else if (action === 'delete') {
            titleEl.textContent = 'Desactivar Descuento';
            bodyEl.innerHTML = `
                <div class="text-center">
                    <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width: 60px; height: 60px;">
                        <i class="bi bi-lock display-6 text-danger"></i>
                    </div>
                    <p class="mt-3">¿Estás seguro de desactivar el descuento <strong>${escapeHtml(discount.name || discount.code)}</strong>?</p>
                    <p class="text-muted mb-0">Los clientes ya no podrán utilizar este descuento.</p>
                </div>
                <input type="hidden" name="_method" value="DELETE">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Desactivar Descuento</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/discounts/' + discount.id);

        } else if (action === 'activate') {
            titleEl.textContent = 'Reactivar Descuento';
            bodyEl.innerHTML = `
                <div class="text-center">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width: 60px; height: 60px;">
                        <i class="bi bi-unlock display-6 text-success"></i>
                    </div>
                    <p class="mt-3">¿Estás seguro de reactivar el descuento <strong>${escapeHtml(discount.name || discount.code)}</strong>?</p>
                    <p class="text-muted mb-0">El descuento volverá a estar disponible para los clientes.</p>
                </div>
                <input type="hidden" name="_method" value="POST">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Reactivar Descuento</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/discounts/' + discount.id + '/activate');
        }

        modal.show();
    }

    function getAppliesToText(appliesTo) {
        switch(appliesTo) {
            case 'all': return 'Todos los productos';
            case 'categories': return 'Categorías específicas';
            case 'products': return 'Productos específicos';
            default: return appliesTo || 'Todos los productos';
        }
    }

    function formatDateTime(dateString) {
        if (!dateString) return 'No disponible';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES') + ' ' +
               date.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Manejar formulario con AJAX para mejor UX - muestra errores inline sin cerrar el modal
    document.getElementById('modalForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const method = form.querySelector('input[name="_method"]')?.value || 'POST';
        const url = form.action;

        // Limpiar errores previos
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        // Enviar request
        fetch(url, {
            method: method,
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(async (r) => {
            const data = await r.json();

            if (r.ok || r.status === 302) {
                // Éxito: redirigir
                window.location.href = data.redirect || window.location.href;
            } else if (r.status === 422) {
                // Errores de validación: mostrar inline en el modal
                Object.keys(data.errors || {}).forEach(field => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'invalid-feedback';
                        errorDiv.textContent = Array.isArray(data.errors[field])
                            ? data.errors[field][0]
                            : data.errors[field];
                        input.parentNode.appendChild(errorDiv);
                    }
                });
            } else {
                // Otros errores: mostrar alerta en el modal
                const footer = document.getElementById('modalFooter');
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger mt-3 mb-0';
                alertDiv.textContent = data.message || 'Ocurrió un error. Por favor, inténtalo nuevamente.';
                footer.insertBefore(alertDiv, footer.firstChild);
                setTimeout(() => alertDiv.remove(), 5000);
            }
        }).catch(err => {
            const footer = document.getElementById('modalFooter');
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger mt-3 mb-0';
            alertDiv.textContent = 'Error de conexión. Por favor, inténtalo nuevamente.';
            footer.insertBefore(alertDiv, footer.firstChild);
            setTimeout(() => alertDiv.remove(), 5000);
        });
    });
    // Botones de nuevo descuento
    document.getElementById('btnNewDiscount')?.addEventListener('click', () => openModal('new', null));
    document.getElementById('btnNewDiscountEmpty')?.addEventListener('click', () => openModal('new', null));
</script>
@endsection