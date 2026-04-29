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

    /* Multi-step form styles */
    .step-progress {
        margin-bottom: 1.5rem;
    }

    .step-progress .progress {
        height: 6px;
        background-color: #e9ecef;
        border-radius: 3px;
    }

    .step-progress .progress-bar {
        background: linear-gradient(90deg, #0d6efd, #6610f2);
        border-radius: 3px;
    }

    .step-indicator {
        flex: 1;
        text-align: center;
        position: relative;
    }

    .step-indicator:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 15px;
        left: 50%;
        width: calc(100% - 30px);
        height: 2px;
        background: #e9ecef;
        z-index: 1;
    }

    .step-indicator.completed:not(:last-child)::after {
        background: #198754;
    }

    .step-circle {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
        font-weight: 600;
        font-size: 0.85rem;
        position: relative;
        z-index: 2;
    }

    .step-indicator.active .step-circle {
        background: #0d6efd;
        color: white;
    }

    .step-indicator.completed .step-circle {
        background: #198754;
        color: white;
    }

    .step-label {
        font-size: 0.75rem;
        color: #6c757d;
        font-weight: 500;
    }

    .step-indicator.active .step-label {
        color: #0d6efd;
        font-weight: 600;
    }

    .step-indicator.completed .step-label {
        color: #198754;
    }

    .summary-card {
        background: #f8f9fa;
        border-color: #dee2e6 !important;
    }

    .summary-card h6 {
        color: #495057;
        font-size: 0.9rem;
        margin-bottom: 0.75rem;
    }

    .applies-to-details {
        transition: all 0.3s ease;
    }

    /* Delete/Activate modal styles */
    .deactivation-modal .discount-summary,
    .activation-modal .discount-summary {
        border-left: 4px solid #0d6efd !important;
    }

    .deactivation-modal .impact-alert {
        border-left: 4px solid #dc3545;
    }

    .activation-modal .impact-alert {
        border-left: 4px solid #198754;
    }

    .deactivation-modal .confirmation-section,
    .activation-modal .confirmation-section {
        border-left: 4px solid currentColor !important;
    }

    .deactivation-modal .confirmation-section {
        border-color: #dc3545 !important;
    }

    .activation-modal .confirmation-section {
        border-color: #198754 !important;
    }

    .discount-summary h6 {
        color: #495057;
        font-size: 0.9rem;
        margin-bottom: 0.75rem;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 0.25rem;
    }

    .impact-alert ul li {
        margin-bottom: 0.25rem;
    }

    .impact-alert ul li:last-child {
        margin-bottom: 0;
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

    // Multi-step form variables
    let currentStep = 1;
    let totalSteps = 4;
    let formData = {};

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
            // Multi-step edit modal
            currentStep = 1;
            totalSteps = 4;
            // Pre-load existing discount data
            formData = {
                id: discount.id,
                code: discount.code || '',
                name: discount.name || '',
                description: discount.description || '',
                discount_type: discount.discount_type || 'percentage',
                value: discount.value || '',
                max_uses: discount.max_uses || '',
                used_count: discount.used_count || 0,
                valid_from: discount.valid_from ? new Date(discount.valid_from).toISOString().slice(0, 16) : '',
                valid_to: discount.valid_to ? new Date(discount.valid_to).toISOString().slice(0, 16) : '',
                applies_to: discount.applies_to || 'all',
                applicable_ids: discount.applicable_ids || [],
                active: discount.active !== false, // Default to true if undefined
                created_at: discount.created_at,
                updated_at: discount.updated_at
            };

            renderEditStep(currentStep);
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/discounts/' + discount.id);

            // Add hidden _method input for PUT
            let methodInput = document.querySelector('input[name="_method"]');
            if (!methodInput) {
                methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'PUT';
                formEl.appendChild(methodInput);
            } else {
                methodInput.value = 'PUT';
            }

        } else if (action === 'new') {
            // Multi-step create modal
            currentStep = 1;
            totalSteps = 4;
            formData = {
                code: '',
                name: '',
                description: '',
                discount_type: 'percentage',
                value: '',
                max_uses: '',
                valid_from: '',
                valid_to: '',
                applies_to: 'all',
                applicable_ids: [],
                active: true
            };

            renderCreateStep(currentStep);
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/discounts');

        } else if (action === 'delete') {
            titleEl.innerHTML = `<i class="bi bi-lock"></i> Desactivar Descuento: ${escapeHtml(discount.code)}`;

            const isPercentage = discount.discount_type === 'percentage';
            const valueDisplay = isPercentage ?
                `${discount.value}%` :
                `$${parseFloat(discount.value || 0).toFixed(2)}`;

            const hasBeenUsed = (discount.used_count || 0) > 0;
            const usageText = hasBeenUsed ?
                `Ha sido utilizado ${discount.used_count} ${discount.used_count === 1 ? 'vez' : 'veces'}.` :
                'Aún no ha sido utilizado.';

            const appliesToText = getAppliesToText(discount.applies_to);

            bodyEl.innerHTML = `
                <div class="deactivation-modal">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>¿Estás seguro de desactivar este descuento?</strong>
                    </div>

                    <div class="discount-summary border rounded p-3 mb-3 bg-light">
                        <h6 class="text-primary mb-2"><i class="bi bi-tag"></i> Información del Descuento</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Código:</strong> <code>${escapeHtml(discount.code)}</code></p>
                                <p class="mb-1"><strong>Nombre:</strong> ${escapeHtml(discount.name || '-')}</p>
                                <p class="mb-1"><strong>Tipo:</strong> ${isPercentage ? 'Porcentaje' : 'Importe Fijo'}</p>
                                <p class="mb-1"><strong>Valor:</strong> <span class="text-success fw-bold">${valueDisplay}</span></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Aplica a:</strong> ${appliesToText}</p>
                                <p class="mb-1"><strong>Vigencia:</strong> ${discount.valid_to ?
                                    'Hasta ' + new Date(discount.valid_to).toLocaleDateString('es-ES') :
                                    'Sin límite'}</p>
                                <p class="mb-1"><strong>Estado de uso:</strong> ${usageText}</p>
                            </div>
                        </div>
                    </div>

                    <div class="impact-alert alert alert-warning">
                        <h6 class="alert-heading mb-2"><i class="bi bi-info-circle"></i> Impacto de la desactivación:</h6>
                        <ul class="mb-0">
                            <li>Los clientes <strong>ya no podrán utilizar</strong> este descuento</li>
                            <li>Los códigos de descuento existentes <strong>dejarán de funcionar</strong></li>
                            <li>Esta acción se puede <strong>revertir</strong> reactivando el descuento</li>
                            ${hasBeenUsed ? '<li><strong>No afecta</strong> los usos ya realizados</li>' : ''}
                            <li>El descuento aparecerá como <strong>"Inactivo"</strong> en la lista</li>
                        </ul>
                    </div>

                    <div class="confirmation-section text-center border rounded p-3 bg-danger bg-opacity-10">
                        <div class="mb-3">
                            <i class="bi bi-lock-fill text-danger display-4"></i>
                        </div>
                        <p class="mb-1 fw-bold">¿Confirmas que deseas desactivar este descuento?</p>
                        <p class="text-muted small mb-0">Esta acción se puede deshacer en cualquier momento.</p>
                    </div>
                </div>
                <input type="hidden" name="_method" value="DELETE">
            `;

            footerEl.innerHTML = `
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-lock"></i> Sí, Desactivar Descuento
                </button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/discounts/' + discount.id);

        } else if (action === 'activate') {
            titleEl.innerHTML = `<i class="bi bi-unlock"></i> Reactivar Descuento: ${escapeHtml(discount.code)}`;

            const isPercentage = discount.discount_type === 'percentage';
            const valueDisplay = isPercentage ?
                `${discount.value}%` :
                `$${parseFloat(discount.value || 0).toFixed(2)}`;

            const appliesToText = getAppliesToText(discount.applies_to);
            const usageText = discount.used_count ?
                `Ha sido utilizado ${discount.used_count} ${discount.used_count === 1 ? 'vez' : 'veces'}.` :
                'Aún no ha sido utilizado.';

            // Check if discount is expired
            const now = new Date();
            const validTo = discount.valid_to ? new Date(discount.valid_to) : null;
            const isExpired = validTo && validTo < now;

            bodyEl.innerHTML = `
                <div class="activation-modal">
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <strong>¿Estás seguro de reactivar este descuento?</strong>
                    </div>

                    <div class="discount-summary border rounded p-3 mb-3 bg-light">
                        <h6 class="text-primary mb-2"><i class="bi bi-tag"></i> Información del Descuento</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Código:</strong> <code>${escapeHtml(discount.code)}</code></p>
                                <p class="mb-1"><strong>Nombre:</strong> ${escapeHtml(discount.name || '-')}</p>
                                <p class="mb-1"><strong>Tipo:</strong> ${isPercentage ? 'Porcentaje' : 'Importe Fijo'}</p>
                                <p class="mb-1"><strong>Valor:</strong> <span class="text-success fw-bold">${valueDisplay}</span></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Aplica a:</strong> ${appliesToText}</p>
                                <p class="mb-1"><strong>Vigencia:</strong> ${discount.valid_to ?
                                    (isExpired ?
                                        '<span class="text-danger">Expirado el ' + validTo.toLocaleDateString('es-ES') + '</span>' :
                                        'Hasta ' + validTo.toLocaleDateString('es-ES')) :
                                    'Sin límite'}</p>
                                <p class="mb-1"><strong>Estado de uso:</strong> ${usageText}</p>
                            </div>
                        </div>
                    </div>

                    <div class="impact-alert alert ${isExpired ? 'alert-warning' : 'alert-info'}">
                        <h6 class="alert-heading mb-2"><i class="bi bi-info-circle"></i> Impacto de la reactivación:</h6>
                        <ul class="mb-0">
                            <li>Los clientes <strong>podrán volver a utilizar</strong> este descuento</li>
                            <li>Los códigos de descuento <strong>volverán a funcionar</strong> inmediatamente</li>
                            ${isExpired ?
                                '<li class="text-warning"><strong>Atención:</strong> El descuento expiró, pero será reactivado de todas formas</li>' :
                                '<li>El descuento estará disponible según su configuración de vigencia</li>'}
                            <li>El descuento aparecerá como <strong>"Activo"</strong> en la lista</li>
                            ${discount.max_uses && discount.used_count ?
                                `<li>Usos restantes: <strong>${discount.max_uses - discount.used_count}</strong></li>` :
                                ''}
                        </ul>
                    </div>

                    ${isExpired ? `
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Nota:</strong> Este descuento está configurado para expirar el ${validTo.toLocaleDateString('es-ES')}.
                            Si lo reactivas ahora, seguirá funcionando hasta esa fecha.
                        </div>
                    ` : ''}

                    <div class="confirmation-section text-center border rounded p-3 bg-success bg-opacity-10">
                        <div class="mb-3">
                            <i class="bi bi-unlock-fill text-success display-4"></i>
                        </div>
                        <p class="mb-1 fw-bold">¿Confirmas que deseas reactivar este descuento?</p>
                        <p class="text-muted small mb-0">Los clientes podrán utilizarlo inmediatamente.</p>
                    </div>
                </div>
                <input type="hidden" name="_method" value="POST">
            `;

            footerEl.innerHTML = `
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-unlock"></i> Sí, Reactivar Descuento
                </button>
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

    // ============================================
    // MULTI-STEP CREATE FUNCTIONS
    // ============================================

    function renderCreateStep(step) {
        const titleEl = document.getElementById('modalTitle');
        const bodyEl = document.getElementById('modalBody');
        const footerEl = document.getElementById('modalFooter');

        // Update title with step indicator
        titleEl.innerHTML = `<i class="bi bi-plus-circle"></i> Nuevo Descuento - Paso ${step} de ${totalSteps}`;

        // Render step content
        let stepContent = '';
        let footerButtons = '';

        switch(step) {
            case 1:
                stepContent = renderStep1();
                footerButtons = `
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="nextStep()">
                        <i class="bi bi-chevron-right"></i> Siguiente
                    </button>
                `;
                break;
            case 2:
                stepContent = renderStep2();
                footerButtons = `
                    <button type="button" class="btn btn-outline-secondary" onclick="prevStep()">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </button>
                    <button type="button" class="btn btn-primary" onclick="nextStep()">
                        <i class="bi bi-chevron-right"></i> Siguiente
                    </button>
                `;
                break;
            case 3:
                stepContent = renderStep3();
                footerButtons = `
                    <button type="button" class="btn btn-outline-secondary" onclick="prevStep()">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </button>
                    <button type="button" class="btn btn-primary" onclick="nextStep()">
                        <i class="bi bi-chevron-right"></i> Siguiente
                    </button>
                `;
                break;
            case 4:
                stepContent = renderStep4();
                footerButtons = `
                    <button type="button" class="btn btn-outline-secondary" onclick="prevStep()">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> ${currentAction === 'new' ? 'Crear' : 'Actualizar'} Descuento
                    </button>
                `;
                break;
        }

        bodyEl.innerHTML = stepContent;
        footerEl.innerHTML = footerButtons;

        // Render progress indicator
        renderProgressIndicator();
    }

    function renderProgressIndicator() {
        const existingIndicator = document.querySelector('.step-progress');
        if (existingIndicator) {
            existingIndicator.remove();
        }

        const progressHtml = `
            <div class="step-progress mb-3">
                <div class="d-flex justify-content-between mb-2">
                    ${Array.from({length: totalSteps}, (_, i) => {
                        const stepNum = i + 1;
                        const isActive = stepNum === currentStep;
                        const isCompleted = stepNum < currentStep;
                        return `
                            <div class="step-indicator ${isActive ? 'active' : ''} ${isCompleted ? 'completed' : ''}" data-step="${stepNum}">
                                <div class="step-circle">${stepNum}</div>
                                <div class="step-label">${getStepLabel(stepNum)}</div>
                            </div>
                        `;
                    }).join('')}
                </div>
                <div class="progress">
                    <div class="progress-bar" style="width: ${(currentStep / totalSteps) * 100}%"></div>
                </div>
            </div>
        `;

        const bodyEl = document.getElementById('modalBody');
        bodyEl.insertAdjacentHTML('afterbegin', progressHtml);
    }

    function getStepLabel(step) {
        switch(step) {
            case 1: return 'Información';
            case 2: return 'Configuración';
            case 3: return 'Vigencia';
            case 4: return 'Resumen';
            default: return '';
        }
    }

    function renderStep1() {
        return `
            <div class="step-content">
                <div class="mb-3">
                    <label class="form-label">Código <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" value="${escapeHtml(formData.code)}" required
                           placeholder="Ej: VERANO20" maxlength="50">
                    <div class="form-text">Código alfanumérico único, se guardará en mayúsculas.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="${escapeHtml(formData.name)}" required
                           placeholder="Ej: Descuento Verano 20%" maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" class="form-control" rows="3" maxlength="1000"
                              placeholder="Descripción opcional del descuento">${escapeHtml(formData.description)}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipo de Descuento <span class="text-danger">*</span></label>
                    <select name="discount_type" class="form-select" required>
                        <option value="percentage" ${formData.discount_type === 'percentage' ? 'selected' : ''}>Porcentaje (%)</option>
                        <option value="fixed" ${formData.discount_type === 'fixed' ? 'selected' : ''}>Importe Fijo ($)</option>
                    </select>
                </div>
            </div>
        `;
    }

    function renderStep2() {
        const isPercentage = formData.discount_type === 'percentage';
        return `
            <div class="step-content">
                <div class="mb-3">
                    <label class="form-label">Valor del Descuento <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="value" class="form-control" value="${formData.value}"
                               step="0.01" min="0" required>
                        <span class="input-group-text">${isPercentage ? '%' : '$'}</span>
                    </div>
                    <div class="form-text">
                        ${isPercentage ? 'Valor entre 0 y 100 para porcentajes.' : 'Monto en pesos para descuento fijo.'}
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Usos Máximos</label>
                    <input type="number" name="max_uses" class="form-control" value="${formData.max_uses}"
                           min="1" placeholder="Dejar vacío para usos ilimitados">
                    <div class="form-text">Número máximo de veces que se puede utilizar este descuento.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Aplicar a <span class="text-danger">*</span></label>
                    <select name="applies_to" class="form-select" required>
                        <option value="all" ${formData.applies_to === 'all' ? 'selected' : ''}>Todos los productos</option>
                        <option value="categories" ${formData.applies_to === 'categories' ? 'selected' : ''}>Categorías específicas</option>
                        <option value="products" ${formData.applies_to === 'products' ? 'selected' : ''}>Productos específicos</option>
                    </select>
                </div>

                <div class="mb-3 applies-to-details" style="${formData.applies_to === 'all' ? 'display: none;' : ''}">
                    <label class="form-label">
                        IDs Aplicables <span class="text-danger">*</span>
                        <small class="text-muted">(${formData.applies_to === 'categories' ? 'IDs de categorías' : 'IDs de productos'})</small>
                    </label>
                    <textarea name="applicable_ids_text" class="form-control" rows="3"
                              placeholder="Ingrese IDs separados por coma (ej: cat1, cat2, prod1)">${formData.applicable_ids.join(', ')}</textarea>
                    <div class="form-text">Solo requerido cuando "Aplicar a" no es "Todos los productos".</div>
                </div>
            </div>
        `;
    }

    function renderStep3() {
        const now = new Date();
        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);

        const formatDateTime = (date) => {
            return date.getFullYear() + '-' +
                   String(date.getMonth() + 1).padStart(2, '0') + '-' +
                   String(date.getDate()).padStart(2, '0') + 'T' +
                   String(date.getHours()).padStart(2, '0') + ':' +
                   String(date.getMinutes()).padStart(2, '0');
        };

        const defaultFrom = formData.valid_from || formatDateTime(now);
        const defaultTo = formData.valid_to || formatDateTime(tomorrow);

        return `
            <div class="step-content">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Define el período de vigencia del descuento. Los clientes solo podrán utilizarlo durante estas fechas.
                </div>

                <div class="mb-3">
                    <label class="form-label">Válido Desde <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="valid_from" class="form-control" value="${defaultFrom}" required>
                    <div class="form-text">Fecha y hora a partir de la cual el descuento estará disponible.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Válido Hasta <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="valid_to" class="form-control" value="${defaultTo}" required>
                    <div class="form-text">Fecha y hora hasta la cual el descuento estará disponible.</div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="active" class="form-check-input" id="activeCheck" ${formData.active ? 'checked' : ''}>
                        <label class="form-check-label" for="activeCheck">
                            Activar descuento inmediatamente
                        </label>
                    </div>
                    <div class="form-text">Si no está marcado, el descuento se creará como inactivo y podrás activarlo después.</div>
                </div>
            </div>
        `;
    }

    function renderStep4() {
        const isPercentage = formData.discount_type === 'percentage';
        const valueDisplay = isPercentage ?
            `${formData.value}%` :
            `$${parseFloat(formData.value || 0).toFixed(2)}`;

        const appliesToText = getAppliesToText(formData.applies_to);

        return `
            <div class="step-content">
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    Revisa la información antes de crear el descuento.
                </div>

                <div class="summary-card border rounded p-3 mb-3">
                    <h6 class="text-primary mb-2"><i class="bi bi-tag"></i> Información Básica</h6>
                    <p class="mb-1"><strong>Código:</strong> <code>${escapeHtml(formData.code.toUpperCase())}</code></p>
                    <p class="mb-1"><strong>Nombre:</strong> ${escapeHtml(formData.name)}</p>
                    ${formData.description ? `<p class="mb-1"><strong>Descripción:</strong> ${escapeHtml(formData.description)}</p>` : ''}
                </div>

                <div class="summary-card border rounded p-3 mb-3">
                    <h6 class="text-primary mb-2"><i class="bi bi-graph-up"></i> Configuración</h6>
                    <p class="mb-1"><strong>Tipo:</strong> ${isPercentage ? 'Porcentaje' : 'Importe Fijo'}</p>
                    <p class="mb-1"><strong>Valor:</strong> <span class="h6 text-success">${valueDisplay}</span></p>
                    ${formData.max_uses ? `<p class="mb-1"><strong>Usos Máximos:</strong> ${formData.max_uses}</p>` : ''}
                    <p class="mb-1"><strong>Aplica a:</strong> ${appliesToText}</p>
                    ${formData.applicable_ids.length > 0 ?
                        `<p class="mb-1"><strong>IDs Aplicables:</strong> ${formData.applicable_ids.join(', ')}</p>` : ''}
                </div>

                <div class="summary-card border rounded p-3 mb-3">
                    <h6 class="text-primary mb-2"><i class="bi bi-calendar-check"></i> Vigencia</h6>
                    <p class="mb-1"><strong>Desde:</strong> ${formatDateTime(formData.valid_from)}</p>
                    <p class="mb-1"><strong>Hasta:</strong> ${formatDateTime(formData.valid_to)}</p>
                    <p class="mb-1"><strong>Estado Inicial:</strong> ${formData.active ?
                        '<span class="badge bg-success">Activo</span>' :
                        '<span class="badge bg-secondary">Inactivo</span>'}</p>
                </div>

                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    Una vez creado, podrás editar algunos campos pero no el código ni las condiciones de aplicación.
                </div>
            </div>
        `;
    }

    function nextStep() {
        if (!validateCurrentStep()) {
            return;
        }

        saveStepData();

        if (currentStep < totalSteps) {
            currentStep++;
            renderCreateStep(currentStep);
        }
    }

    function prevStep() {
        saveStepData();

        if (currentStep > 1) {
            currentStep--;
            renderCreateStep(currentStep);
        }
    }

    function validateCurrentStep() {
        const stepInputs = document.querySelectorAll(`#modalBody input, #modalBody select, #modalBody textarea`);
        let isValid = true;

        // Clear previous errors
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        stepInputs.forEach(input => {
            if (input.hasAttribute('required') && !input.value.trim()) {
                showFieldError(input, 'Este campo es obligatorio.');
                isValid = false;
            }

            // Specific validations
            if (input.name === 'code' && input.value) {
                if (!/^[A-Za-z0-9]+$/.test(input.value)) {
                    showFieldError(input, 'El código solo puede contener letras y números.');
                    isValid = false;
                }
            }

            if (input.name === 'value' && input.value) {
                const value = parseFloat(input.value);
                if (formData.discount_type === 'percentage' && (value < 0 || value > 100)) {
                    showFieldError(input, 'El porcentaje debe estar entre 0 y 100.');
                    isValid = false;
                }
                if (value < 0) {
                    showFieldError(input, 'El valor no puede ser negativo.');
                    isValid = false;
                }
            }

            if (input.name === 'applicable_ids_text' && formData.applies_to !== 'all' && !input.value.trim()) {
                showFieldError(input, 'Debes especificar al menos un ID aplicable.');
                isValid = false;
            }

            if (input.name === 'valid_from' && input.name === 'valid_to') {
                const fromDate = new Date(formData.valid_from);
                const toDate = new Date(formData.valid_to);
                if (fromDate >= toDate) {
                    showFieldError(document.querySelector('[name="valid_to"]'), 'La fecha de fin debe ser posterior a la fecha de inicio.');
                    isValid = false;
                }
            }
        });

        return isValid;
    }

    function saveStepData() {
        const form = document.getElementById('modalForm');
        const formDataObj = new FormData(form);

        for (let [key, value] of formDataObj.entries()) {
            if (key === 'applicable_ids_text') {
                formData.applicable_ids = value ? value.split(',').map(id => id.trim()).filter(id => id) : [];
            } else if (key === 'active') {
                formData[key] = true; // Checkbox checked
            } else if (key === 'max_uses') {
                formData[key] = value ? parseInt(value) : '';
            } else if (key === 'value') {
                formData[key] = value ? parseFloat(value) : '';
            } else {
                formData[key] = value;
            }
        }

        // Handle unchecked checkbox
        if (!formDataObj.has('active')) {
            formData.active = false;
        }
    }

    function showFieldError(input, message) {
        input.classList.add('is-invalid');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        input.parentNode.appendChild(errorDiv);
    }

    // ============================================
    // MULTI-STEP EDIT FUNCTIONS
    // ============================================

    function renderEditStep(step) {
        const titleEl = document.getElementById('modalTitle');
        const bodyEl = document.getElementById('modalBody');
        const footerEl = document.getElementById('modalFooter');

        // Update title with step indicator
        titleEl.innerHTML = `<i class="bi bi-pencil"></i> Editar Descuento: ${escapeHtml(formData.code)} - Paso ${step} de ${totalSteps}`;

        // Render step content
        let stepContent = '';
        let footerButtons = '';

        switch(step) {
            case 1:
                stepContent = renderEditStep1();
                footerButtons = `
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="nextEditStep()">
                        <i class="bi bi-chevron-right"></i> Siguiente
                    </button>
                `;
                break;
            case 2:
                stepContent = renderEditStep2();
                footerButtons = `
                    <button type="button" class="btn btn-outline-secondary" onclick="prevEditStep()">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </button>
                    <button type="button" class="btn btn-primary" onclick="nextEditStep()">
                        <i class="bi bi-chevron-right"></i> Siguiente
                    </button>
                `;
                break;
            case 3:
                stepContent = renderEditStep3();
                footerButtons = `
                    <button type="button" class="btn btn-outline-secondary" onclick="prevEditStep()">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </button>
                    <button type="button" class="btn btn-primary" onclick="nextEditStep()">
                        <i class="bi bi-chevron-right"></i> Siguiente
                    </button>
                `;
                break;
            case 4:
                stepContent = renderEditStep4();
                footerButtons = `
                    <button type="button" class="btn btn-outline-secondary" onclick="prevEditStep()">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> ${currentAction === 'new' ? 'Crear' : 'Actualizar'} Descuento
                    </button>
                `;
                break;
        }

        bodyEl.innerHTML = stepContent;
        footerEl.innerHTML = footerButtons;

        // Render progress indicator
        renderProgressIndicator();
    }

    function renderEditStep1() {
        return `
            <div class="step-content">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    El código y tipo de descuento no se pueden modificar una vez creado.
                </div>

                <div class="mb-3">
                    <label class="form-label">Código</label>
                    <input type="text" class="form-control bg-light" value="${escapeHtml(formData.code)}" readonly>
                    <div class="form-text text-muted">El código no se puede modificar.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="${escapeHtml(formData.name)}" required maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" class="form-control" rows="3" maxlength="1000">${escapeHtml(formData.description)}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipo de Descuento</label>
                    <input type="text" class="form-control bg-light" value="${formData.discount_type === 'percentage' ? 'Porcentaje (%)' : 'Importe Fijo ($)'}" readonly>
                    <div class="form-text text-muted">El tipo no se puede modificar.</div>
                </div>
            </div>
        `;
    }

    function renderEditStep2() {
        const isPercentage = formData.discount_type === 'percentage';
        const hasBeenUsed = formData.used_count > 0;

        return `
            <div class="step-content">
                ${hasBeenUsed ? `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Este descuento ya ha sido utilizado ${formData.used_count} ${formData.used_count === 1 ? 'vez' : 'veces'}.
                        Solo puedes aumentar los usos máximos, no reducirlos.
                    </div>
                ` : ''}

                <div class="mb-3">
                    <label class="form-label">Valor del Descuento <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="value" class="form-control" value="${formData.value}"
                               step="0.01" min="0" required>
                        <span class="input-group-text">${isPercentage ? '%' : '$'}</span>
                    </div>
                    <div class="form-text">
                        ${isPercentage ? 'Valor entre 0 y 100 para porcentajes.' : 'Monto en pesos para descuento fijo.'}
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Usos Máximos</label>
                    <input type="number" name="max_uses" class="form-control" value="${formData.max_uses}"
                           min="${hasBeenUsed ? formData.used_count : 1}" placeholder="Dejar vacío para usos ilimitados">
                    <div class="form-text">
                        ${hasBeenUsed ?
                            `Debe ser al menos ${formData.used_count} (ya utilizado).` :
                            'Número máximo de veces que se puede utilizar este descuento.'
                        }
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Aplicar a</label>
                    <input type="text" class="form-control bg-light" value="${getAppliesToText(formData.applies_to)}" readonly>
                    <div class="form-text text-muted">Las condiciones de aplicación no se pueden modificar.</div>
                </div>

                ${formData.applies_to !== 'all' ? `
                    <div class="mb-3">
                        <label class="form-label">IDs Aplicables</label>
                        <textarea class="form-control bg-light" rows="3" readonly>${formData.applicable_ids.join(', ')}</textarea>
                        <div class="form-text text-muted">Los IDs aplicables no se pueden modificar.</div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    function renderEditStep3() {
        return `
            <div class="step-content">
                <div class="alert alert-info">
                    <i class="bi bi-calendar-event"></i>
                    Modifica las fechas de vigencia del descuento. Los cambios afectan inmediatamente.
                </div>

                <div class="mb-3">
                    <label class="form-label">Válido Desde <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="valid_from" class="form-control" value="${formData.valid_from}" required>
                    <div class="form-text">Fecha y hora a partir de la cual el descuento estará disponible.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Válido Hasta <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="valid_to" class="form-control" value="${formData.valid_to}" required>
                    <div class="form-text">Fecha y hora hasta la cual el descuento estará disponible.</div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="active" class="form-check-input" id="editActiveCheck" ${formData.active ? 'checked' : ''}>
                        <label class="form-check-label" for="editActiveCheck">
                            Descuento activo
                        </label>
                    </div>
                    <div class="form-text">Activa o desactiva el descuento inmediatamente.</div>
                </div>
            </div>
        `;
    }

    function renderEditStep4() {
        const isPercentage = formData.discount_type === 'percentage';
        const valueDisplay = isPercentage ?
            `${formData.value}%` :
            `$${parseFloat(formData.value || 0).toFixed(2)}`;

        const appliesToText = getAppliesToText(formData.applies_to);

        const changes = getChangesSummary();

        return `
            <div class="step-content">
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    Revisa los cambios antes de actualizar el descuento.
                </div>

                ${changes.length > 0 ? `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Cambios detectados:</strong>
                        <ul class="mb-0 mt-2">
                            ${changes.map(change => `<li>${change}</li>`).join('')}
                        </ul>
                    </div>
                ` : `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        No se detectaron cambios en los datos.
                    </div>
                `}

                <div class="summary-card border rounded p-3 mb-3">
                    <h6 class="text-primary mb-2"><i class="bi bi-tag"></i> Información Básica</h6>
                    <p class="mb-1"><strong>Código:</strong> <code>${escapeHtml(formData.code.toUpperCase())}</code></p>
                    <p class="mb-1"><strong>Nombre:</strong> ${escapeHtml(formData.name)}</p>
                    ${formData.description ? `<p class="mb-1"><strong>Descripción:</strong> ${escapeHtml(formData.description)}</p>` : ''}
                </div>

                <div class="summary-card border rounded p-3 mb-3">
                    <h6 class="text-primary mb-2"><i class="bi bi-graph-up"></i> Configuración</h6>
                    <p class="mb-1"><strong>Tipo:</strong> ${isPercentage ? 'Porcentaje' : 'Importe Fijo'}</p>
                    <p class="mb-1"><strong>Valor:</strong> <span class="h6 text-success">${valueDisplay}</span></p>
                    ${formData.max_uses ? `<p class="mb-1"><strong>Usos Máximos:</strong> ${formData.max_uses}</p>` : ''}
                    ${formData.used_count ? `<p class="mb-1"><strong>Usos Realizados:</strong> ${formData.used_count}</p>` : ''}
                    <p class="mb-1"><strong>Aplica a:</strong> ${appliesToText}</p>
                    ${formData.applicable_ids.length > 0 ?
                        `<p class="mb-1"><strong>IDs Aplicables:</strong> ${formData.applicable_ids.join(', ')}</p>` : ''}
                </div>

                <div class="summary-card border rounded p-3 mb-3">
                    <h6 class="text-primary mb-2"><i class="bi bi-calendar-check"></i> Vigencia</h6>
                    <p class="mb-1"><strong>Desde:</strong> ${formatDateTime(formData.valid_from)}</p>
                    <p class="mb-1"><strong>Hasta:</strong> ${formatDateTime(formData.valid_to)}</p>
                    <p class="mb-1"><strong>Estado:</strong> ${formData.active ?
                        '<span class="badge bg-success">Activo</span>' :
                        '<span class="badge bg-danger">Inactivo</span>'}</p>
                </div>
            </div>
        `;
    }

    function getChangesSummary() {
        const changes = [];

        // Compare with original data
        if (formData.name !== currentDiscount.name) {
            changes.push(`Nombre: "${currentDiscount.name}" → "${formData.name}"`);
        }
        if (formData.description !== (currentDiscount.description || '')) {
            changes.push(`Descripción modificada`);
        }
        if (formData.value !== currentDiscount.value) {
            changes.push(`Valor: ${currentDiscount.value} → ${formData.value}`);
        }
        if (formData.max_uses !== (currentDiscount.max_uses || '')) {
            changes.push(`Usos máximos: ${currentDiscount.max_uses || 'ilimitados'} → ${formData.max_uses || 'ilimitados'}`);
        }
        if (formData.valid_from !== (currentDiscount.valid_from ? new Date(currentDiscount.valid_from).toISOString().slice(0, 16) : '')) {
            changes.push(`Fecha de inicio modificada`);
        }
        if (formData.valid_to !== (currentDiscount.valid_to ? new Date(currentDiscount.valid_to).toISOString().slice(0, 16) : '')) {
            changes.push(`Fecha de fin modificada`);
        }
        if (formData.active !== (currentDiscount.active !== false)) {
            changes.push(`Estado: ${currentDiscount.active !== false ? 'Activo' : 'Inactivo'} → ${formData.active ? 'Activo' : 'Inactivo'}`);
        }

        return changes;
    }

    function nextEditStep() {
        if (!validateEditCurrentStep()) {
            return;
        }

        saveStepData();

        if (currentStep < totalSteps) {
            currentStep++;
            renderEditStep(currentStep);
        }
    }

    function prevEditStep() {
        saveStepData();

        if (currentStep > 1) {
            currentStep--;
            renderEditStep(currentStep);
        }
    }

    function validateEditCurrentStep() {
        const stepInputs = document.querySelectorAll(`#modalBody input:not([readonly]), #modalBody select, #modalBody textarea:not([readonly])`);
        let isValid = true;

        // Clear previous errors
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        stepInputs.forEach(input => {
            if (input.hasAttribute('required') && !input.value.trim()) {
                showFieldError(input, 'Este campo es obligatorio.');
                isValid = false;
            }

            // Specific validations
            if (input.name === 'value' && input.value) {
                const value = parseFloat(input.value);
                if (formData.discount_type === 'percentage' && (value < 0 || value > 100)) {
                    showFieldError(input, 'El porcentaje debe estar entre 0 y 100.');
                    isValid = false;
                }
                if (value < 0) {
                    showFieldError(input, 'El valor no puede ser negativo.');
                    isValid = false;
                }
            }

            if (input.name === 'max_uses' && input.value) {
                const maxUses = parseInt(input.value);
                const usedCount = formData.used_count || 0;
                if (maxUses < usedCount) {
                    showFieldError(input, `Los usos máximos no pueden ser menores a ${usedCount} (ya utilizado).`);
                    isValid = false;
                }
            }

            if (input.name === 'valid_from' && input.name === 'valid_to') {
                const fromDate = new Date(formData.valid_from);
                const toDate = new Date(formData.valid_to);
                if (fromDate >= toDate) {
                    showFieldError(document.querySelector('[name="valid_to"]'), 'La fecha de fin debe ser posterior a la fecha de inicio.');
                    isValid = false;
                }
            }
        });

        return isValid;
    }

    // ============================================
    // FORM SUBMISSION HANDLER
    // ============================================

    // Manejar formulario con AJAX para mejor UX - muestra errores inline sin cerrar el modal
    document.getElementById('modalForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // Only submit on final step
        if (currentStep < totalSteps) {
            if (currentAction === 'new') {
                nextStep();
            } else if (currentAction === 'edit') {
                nextEditStep();
            }
            return;
        }

        const form = e.target;
        const formDataObj = new FormData(form);

        // Add multi-step data to form
        Object.keys(formData).forEach(key => {
            if (key === 'applicable_ids') {
                formDataObj.append('applicable_ids', JSON.stringify(formData.applicable_ids));
            } else if (key === 'active') {
                formDataObj.append('active', formData.active ? '1' : '0');
            } else {
                formDataObj.append(key, formData[key]);
            }
        });

        const method = form.querySelector('input[name="_method"]')?.value || 'POST';
        const url = form.action;

        // Limpiar errores previos
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Creando...';

        // Enviar request
        fetch(url, {
            method: method,
            body: formDataObj,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(async (r) => {
            const data = await r.json();

            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;

            if (r.ok || r.status === 302) {
                // Éxito: mostrar mensaje y redirigir
                const footer = document.getElementById('modalFooter');
                const successDiv = document.createElement('div');
                successDiv.className = 'alert alert-success mt-3 mb-0';
                successDiv.innerHTML = `<i class="bi bi-check-circle"></i> Descuento ${currentAction === 'new' ? 'creado' : 'actualizado'} exitosamente.`;
                footer.insertBefore(successDiv, footer.firstChild);

                setTimeout(() => {
                    modal.hide();
                    window.location.href = data.redirect || window.location.href;
                }, 1500);
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

                // If validation errors, go back to appropriate step
                let renderFunction = currentAction === 'new' ? renderCreateStep : renderEditStep;
                if (data.errors.name || data.errors.description) {
                    currentStep = 1;
                    renderFunction(1);
                } else if (data.errors.value || data.errors.max_uses) {
                    currentStep = 2;
                    renderFunction(2);
                } else if (data.errors.valid_from || data.errors.valid_to || data.errors.active) {
                    currentStep = 3;
                    renderFunction(3);
                }
            } else {
                // Otros errores: mostrar alerta en el modal
                const footer = document.getElementById('modalFooter');
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger mt-3 mb-0';
                alertDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + (data.message || 'Ocurrió un error. Por favor, inténtalo nuevamente.');
                footer.insertBefore(alertDiv, footer.firstChild);
                setTimeout(() => alertDiv.remove(), 5000);
            }
        }).catch(err => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;

            const footer = document.getElementById('modalFooter');
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger mt-3 mb-0';
            alertDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Error de conexión. Por favor, inténtalo nuevamente.';
            footer.insertBefore(alertDiv, footer.firstChild);
            setTimeout(() => alertDiv.remove(), 5000);
        });
    });
    // Reset multi-step when modal is hidden
    document.getElementById('discountModal').addEventListener('hidden.bs.modal', function () {
        currentStep = 1;
        formData = {};
    });

    // Botones de nuevo descuento
    document.getElementById('btnNewDiscount')?.addEventListener('click', () => openModal('new', null));
    document.getElementById('btnNewDiscountEmpty')?.addEventListener('click', () => openModal('new', null));
</script>
@endsection