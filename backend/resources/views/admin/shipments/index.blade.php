@extends('layouts.admin')

@section('title', 'Envíos - MA Piscinas')
@section('page-title', 'Envíos')
@section('page-subtitle', 'Gestión de envíos de pedidos')

@section('content')
@php
    $currentUser = Auth::user();
    $currentUserRole = $currentUser?->role ?? 'editor';
    $isAdmin = $currentUserRole === 'admin';
@endphp

<div class="flex justify-between items-center mb-4">
    <h1>Envíos</h1>
    <div class="d-flex gap-2">
        @include('admin.partials._export_button', ['entityName' => 'shipments'])
        @if($isAdmin)
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnNewShipment">
                <i class="bi bi-plus-circle"></i> Nuevo Envío
            </button>
        @endif
    </div>
</div>

<!-- Search and Filters -->
<div class="card mb-3">
    <div class="card-body">
        @include('admin.shipments.partials._search_form')
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Orden</th>
                <th>Tracking</th>
                <th>Transportista</th>
                <th>Estado</th>
                <th>Dirección</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shipments as $shipment)
                @include('admin.shipments.partials._shipment_row', [
                    'shipment' => $shipment,
                    'statuses' => $statuses,
                    'isAdmin' => $isAdmin,
                ])
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-truck display-6"></i>
                        <p class="lead mt-2">No hay envíos registrados</p>
                        @if($isAdmin)
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btnNewShipmentEmpty">
                                <i class="bi bi-plus-circle"></i> Crear primer envío
                            </button>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('admin.shipments.partials._pagination')

<!-- Modal Único Dinámico -->
<div class="modal fade" id="shipmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top: 2rem; margin-bottom: 2rem;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">-</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="modalForm" action="#" method="POST">
                @csrf
                <div class="modal-body" id="modalBody"></div>
                <div class="modal-footer" id="modalFooter"></div>
            </form>
        </div>
    </div>
</div>

<!-- Toast container (feedback global) -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer" style="z-index: 1090;"></div>

@if(session('success'))
    <script>window.__flashSuccess = @json(session('success'));</script>
@endif
@if(session('error'))
    <script>window.__flashError = @json(session('error'));</script>
@endif

@endsection

@section('scripts')
<script>
    const modal = new bootstrap.Modal(document.getElementById('shipmentModal'));
    const shipmentStatuses = @json($statuses);
    const shipmentOrders = @json($orders->values());

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function buildOptions(map, selected) {
        return Object.entries(map).map(([k, label]) =>
            `<option value="${escapeHtml(k)}" ${k === selected ? 'selected' : ''}>${escapeHtml(label)}</option>`
        ).join('');
    }

    function buildOrderOptions(selected) {
        return '<option value="">Seleccionar orden...</option>' +
            shipmentOrders.map(o => {
                const value = o.id || '';
                const label = (o.id || '') + ' — ' + (o.client_name || o.clientId || o.client_id || 'Cliente');
                return `<option value="${escapeHtml(value)}" ${value === selected ? 'selected' : ''}>${escapeHtml(label)}</option>`;
            }).join('');
    }

    function buildShipmentForm(shipment) {
        const s = shipment || {};
        return `
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Orden asociada <span class="text-danger">*</span></label>
                    <select name="order_id" class="form-select" required>${buildOrderOptions(s.order_id || '')}</select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Estado <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>${buildOptions(shipmentStatuses, s.status || 'preparing')}</select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Transportista</label>
                    <input type="text" name="carrier" class="form-control" value="${escapeHtml(s.carrier || '')}" placeholder="OCA, Andreani, Correo Argentino...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Código de seguimiento</label>
                    <input type="text" name="tracking_code" class="form-control" value="${escapeHtml(s.tracking_code || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha de despacho</label>
                    <input type="date" name="shipped_at" class="form-control" value="${escapeHtml((s.shipped_at || '').toString().substring(0, 10))}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha de entrega</label>
                    <input type="date" name="delivered_at" class="form-control" value="${escapeHtml((s.delivered_at || '').toString().substring(0, 10))}">
                </div>
                <div class="col-12">
                    <label class="form-label">Dirección <span class="text-danger">*</span></label>
                    <textarea name="address" class="form-control" rows="2" required>${escapeHtml(s.address || '')}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <textarea name="notes" class="form-control" rows="2">${escapeHtml(s.notes || '')}</textarea>
                </div>
            </div>
            <input type="hidden" name="active" value="1">
        `;
    }

    function openModal(action, shipment) {
        @if(!$isAdmin)
        if (action !== 'show') return;
        @endif

        const titleEl = document.getElementById('modalTitle');
        const bodyEl = document.getElementById('modalBody');
        const footerEl = document.getElementById('modalFooter');
        const formEl = document.getElementById('modalForm');

        formEl.querySelectorAll('input[name="_method"]').forEach(i => i.remove());

        if (action === 'show') {
            const s = shipment || {};
            const statusKey = s.status || 'preparing';
            const statusLabel = shipmentStatuses[statusKey] || statusKey;
            titleEl.textContent = 'Detalles del Envío';
            bodyEl.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-6"><strong>Orden:</strong> <code>${escapeHtml(s.order_id || '—')}</code></div>
                    <div class="col-md-6"><strong>Tracking:</strong> <code>${escapeHtml(s.tracking_code || '—')}</code></div>
                    <div class="col-md-6"><strong>Transportista:</strong> ${escapeHtml(s.carrier || '—')}</div>
                    <div class="col-md-6"><strong>Estado:</strong> ${escapeHtml(statusLabel)}</div>
                    <div class="col-md-6"><strong>Despacho:</strong> ${escapeHtml((s.shipped_at || '').toString().substring(0, 10) || '—')}</div>
                    <div class="col-md-6"><strong>Entrega:</strong> ${escapeHtml((s.delivered_at || '').toString().substring(0, 10) || '—')}</div>
                    <div class="col-12"><strong>Dirección:</strong> ${escapeHtml(s.address || '—')}</div>
                    <div class="col-12"><strong>Notas:</strong> ${escapeHtml(s.notes || '—')}</div>
                    <div class="col-12">
                        <strong>Vigencia:</strong>
                        ${s.active === false
                            ? '<span class="badge bg-danger">Cancelado</span>'
                            : '<span class="badge bg-success">Vigente</span>'}
                    </div>
                </div>
            `;
            footerEl.innerHTML = `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>`;
            formEl.setAttribute('method', 'GET');
            formEl.setAttribute('action', '#');

        } else if (action === 'new') {
            titleEl.textContent = 'Nuevo Envío';
            bodyEl.innerHTML = buildShipmentForm(null);
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Envío</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/shipments');

        } else if (action === 'edit') {
            titleEl.textContent = 'Editar Envío';
            bodyEl.innerHTML = buildShipmentForm(shipment) +
                '<input type="hidden" name="_method" value="PUT">';
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/shipments/' + encodeURIComponent(shipment.id || ''));

        } else if (action === 'cancel') {
            titleEl.textContent = 'Cancelar Envío';
            bodyEl.innerHTML = `
                <div class="text-center">
                    <p class="mt-3">¿Cancelar el envío de la orden <strong><code>${escapeHtml(shipment.order_id || '—')}</code></strong>?</p>
                    <p class="text-muted mb-0">Tracking: <code>${escapeHtml(shipment.tracking_code || '—')}</code></p>
                </div>
                <input type="hidden" name="_method" value="DELETE">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
                <button type="submit" class="btn btn-danger">Sí, Cancelar Envío</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/shipments/' + encodeURIComponent(shipment.id || ''));

        } else if (action === 'reactivate') {
            titleEl.textContent = 'Reactivar Envío';
            bodyEl.innerHTML = `
                <div class="text-center">
                    <p class="mt-3">¿Reactivar el envío de la orden <strong><code>${escapeHtml(shipment.order_id || '—')}</code></strong>?</p>
                    <p class="text-muted mb-0">Tracking: <code>${escapeHtml(shipment.tracking_code || '—')}</code></p>
                </div>
                <input type="hidden" name="_method" value="POST">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Sí, Reactivar</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/shipments/' + encodeURIComponent(shipment.id || '') + '/activate');
        }

        modal.show();
    }

    // Submit AJAX
    document.getElementById('modalForm').addEventListener('submit', function(e) {
        const form = e.target;
        if (form.getAttribute('method') === 'GET') return;
        e.preventDefault();
        const formData = new FormData(form);
        const method = form.querySelector('input[name="_method"]')?.value || 'POST';

        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        fetch(form.action, {
            method: method === 'GET' ? 'GET' : 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(async (r) => {
            const data = await r.json().catch(() => ({}));
            if (r.ok || r.status === 302) {
                const successMsg = data.success || 'Operación realizada correctamente.';
                sessionStorage.setItem('flashToast', JSON.stringify({ type: 'success', message: successMsg }));
                window.location.href = data.redirect || window.location.href;
            } else if (r.status === 422) {
                Object.keys(data.errors || {}).forEach(field => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'invalid-feedback';
                        errorDiv.textContent = Array.isArray(data.errors[field]) ? data.errors[field][0] : data.errors[field];
                        input.parentNode.appendChild(errorDiv);
                    }
                });
                showToast('Revisá los campos marcados.', 'warning');
            } else {
                showError(data.message || data.error || 'Ocurrió un error. Inténtalo nuevamente.');
                showToast(data.message || data.error || 'Ocurrió un error.', 'danger');
            }
        }).catch(() => {
            showError('Error de conexión. Inténtalo nuevamente.');
            showToast('Error de conexión.', 'danger');
        });
    });

    function showError(msg) {
        const footer = document.getElementById('modalFooter');
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger mt-3 mb-0';
        alertDiv.textContent = msg;
        footer.insertBefore(alertDiv, footer.firstChild);
        setTimeout(() => alertDiv.remove(), 5000);
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        const colors = { success: 'bg-success', danger: 'bg-danger', warning: 'bg-warning text-dark', info: 'bg-info' };
        const toastEl = document.createElement('div');
        toastEl.className = 'toast align-items-center text-white ' + (colors[type] || colors.success) + ' border-0 shadow';
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message.replace(/[<>&"']/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;',"'":'&#39;'}[c]))}</div>
                <button type="button" class="btn-close ${type === 'warning' ? '' : 'btn-close-white'} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        container.appendChild(toastEl);
        const t = new bootstrap.Toast(toastEl, { delay: 3500 });
        t.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.__flashSuccess) showToast(window.__flashSuccess, 'success');
        if (window.__flashError) showToast(window.__flashError, 'danger');
        const stored = sessionStorage.getItem('flashToast');
        if (stored) {
            try {
                const f = JSON.parse(stored);
                showToast(f.message, f.type || 'success');
            } catch (_) {}
            sessionStorage.removeItem('flashToast');
        }
    });

    @if($isAdmin)
    document.getElementById('btnNewShipment')?.addEventListener('click', () => openModal('new', null));
    document.getElementById('btnNewShipmentEmpty')?.addEventListener('click', () => openModal('new', null));
    @endif
</script>
@endsection
