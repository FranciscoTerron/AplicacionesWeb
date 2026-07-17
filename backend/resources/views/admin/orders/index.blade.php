@extends('layouts.admin')

@section('title', 'Pedidos - MA Piscinas')
@section('page-title', 'Pedidos')
@section('page-subtitle', 'Gestión de pedidos del e-commerce')

@section('content')
@php
    $currentUser = Auth::user();
    $currentUserRole = $currentUser?->role ?? 'editor';
    $isAdmin = $currentUserRole === 'admin';
    $isEditor = $currentUserRole === 'editor';
    $paymentMethods = [
        'cash' => 'Efectivo',
        'transfer' => 'Transferencia',
        'card' => 'Tarjeta',
        'mercado_pago' => 'Mercado Pago',
    ];
@endphp

<div class="flex justify-between items-center mb-4">
    <h1>Pedidos</h1>
    <div class="d-flex gap-2">
        @include('admin.partials._export_button', ['entityName' => 'orders'])
        @if($isAdmin)
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnNewOrder">
                <i class="bi bi-plus-circle"></i> Nuevo Pedido
            </button>
        @endif
    </div>
</div>

<!-- Search and Filters -->
<div class="card mb-3">
    <div class="card-body">
        @include('admin.orders.partials._search_form')
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Pago</th>
                <th>Fecha</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                @include('admin.orders.partials._order_row', [
                    'order' => $order,
                    'statuses' => $statuses,
                    'paymentStatuses' => $paymentStatuses,
                    'isAdmin' => $isAdmin,
                    'isEditor' => $isEditor,
                ])
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-receipt display-6"></i>
                        <p class="lead mt-2">No hay pedidos registrados</p>
                        @if($isAdmin)
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btnNewOrderEmpty">
                                <i class="bi bi-plus-circle"></i> Crear primer pedido
                            </button>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('admin.orders.partials._pagination')

<!-- Modal Único Dinámico -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="margin-top: 2rem; margin-bottom: 2rem;">
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
    const modal = new bootstrap.Modal(document.getElementById('orderModal'));
    const orderStatuses = @json($statuses);
    const orderPaymentStatuses = @json($paymentStatuses);
    const orderPaymentMethods = @json($paymentMethods);
    const orderClients = @json($clients->values());
    const orderProducts = @json($products->values());

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function formatMoney(value) {
        const n = Number(value || 0);
        return '$' + n.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function buildOptions(map, selected) {
        return Object.entries(map).map(([k, label]) =>
            `<option value="${escapeHtml(k)}" ${k === selected ? 'selected' : ''}>${escapeHtml(label)}</option>`
        ).join('');
    }

    function buildClientOptions(selected) {
        return '<option value="">Seleccionar cliente...</option>' +
            orderClients.map(c => {
                const value = c.name || '';
                return `<option value="${escapeHtml(value)}" ${value === selected ? 'selected' : ''}>${escapeHtml(value)}</option>`;
            }).join('');
    }

    function buildProductOptions(selected) {
        return '<option value="">Seleccionar producto...</option>' +
            orderProducts.map(p => {
                const value = p.name || '';
                const price = p.price != null ? ' — ' + formatMoney(p.price) : '';
                return `<option value="${escapeHtml(value)}" data-price="${escapeHtml(p.price || 0)}" ${value === selected ? 'selected' : ''}>${escapeHtml(value)}${escapeHtml(price)}</option>`;
            }).join('');
    }

    let itemRowSeq = 0;

    function buildItemRow(item = {}) {
        const idx = itemRowSeq++;
        const productSelected = item.productId || item.product_id || '';
        const qty = item.quantity != null ? item.quantity : 1;
        const price = item.unitPrice != null ? item.unitPrice : (item.unit_price ?? '');
        return `
            <tr class="item-row" data-row="${idx}">
                <td>
                    <select name="items[${idx}][productId]" class="form-select form-select-sm item-product" required>
                        ${buildProductOptions(productSelected)}
                    </select>
                </td>
                <td style="width:110px;">
                    <input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm item-qty" min="1" value="${escapeHtml(qty)}" required>
                </td>
                <td style="width:140px;">
                    <input type="number" name="items[${idx}][unitPrice]" class="form-control form-control-sm item-price" step="0.01" min="0" value="${escapeHtml(price)}" required>
                </td>
                <td style="width:120px;" class="text-end item-subtotal">${formatMoney(qty * (price || 0))}</td>
                <td style="width:80px;" class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(${idx})" title="Quitar producto">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    function recalcTotals() {
        let total = 0;
        document.querySelectorAll('#itemsBody .item-row').forEach(row => {
            const qty = Number(row.querySelector('.item-qty')?.value || 0);
            const price = Number(row.querySelector('.item-price')?.value || 0);
            const subtotal = qty * price;
            const cell = row.querySelector('.item-subtotal');
            if (cell) cell.textContent = formatMoney(subtotal);
            total += subtotal;
        });
        const totalEl = document.getElementById('orderTotal');
        if (totalEl) totalEl.textContent = formatMoney(total);
    }

    function attachItemEvents() {
        document.querySelectorAll('#itemsBody .item-row').forEach(row => {
            const productSel = row.querySelector('.item-product');
            const priceInput = row.querySelector('.item-price');
            const qtyInput = row.querySelector('.item-qty');

            productSel?.addEventListener('change', (e) => {
                const opt = e.target.selectedOptions[0];
                const price = opt?.getAttribute('data-price');
                if (price && !priceInput.value) priceInput.value = price;
                recalcTotals();
            });
            qtyInput?.addEventListener('input', recalcTotals);
            priceInput?.addEventListener('input', recalcTotals);
        });
    }

    function addItemRow(item) {
        const tbody = document.getElementById('itemsBody');
        tbody.insertAdjacentHTML('beforeend', buildItemRow(item || {}));
        attachItemEvents();
        recalcTotals();
    }

    function removeItemRow(idx) {
        const row = document.querySelector(`#itemsBody .item-row[data-row="${idx}"]`);
        if (row) row.remove();
        recalcTotals();
    }

    function buildOrderForm(order) {
        const isEdit = !!order;
        const o = order || {};
        const items = o.items && o.items.length ? o.items : [{}];
        const itemsHtml = items.map(it => buildItemRow(it)).join('');
        return `
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Cliente <span class="text-danger">*</span></label>
                    <select name="clientId" class="form-select" required>${buildClientOptions(o.clientId || o.client_id || '')}</select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-select">${buildOptions(orderStatuses, o.status || 'pending')}</select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado de Pago</label>
                    <select name="payment_status" class="form-select">${buildOptions(orderPaymentStatuses, o.payment_status || o.paymentStatus || 'pending')}</select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Método de Pago</label>
                    <select name="paymentMethod" class="form-select">
                        <option value="">Sin definir</option>
                        ${buildOptions(orderPaymentMethods, o.paymentMethod || o.payment_method || '')}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notes" class="form-control" value="${escapeHtml(o.notes || '')}" placeholder="Comentarios opcionales">
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Productos del pedido <span class="text-danger">*</span></h6>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addItemRow()">
                    <i class="bi bi-plus-circle"></i> Agregar producto
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th class="text-end">Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">${itemsHtml}</tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td class="text-end"><strong id="orderTotal">$0,00</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
    }

    function openModal(action, order) {
        @if(!$isAdmin && !$isEditor)
        return;
        @endif

        const titleEl = document.getElementById('modalTitle');
        const bodyEl = document.getElementById('modalBody');
        const footerEl = document.getElementById('modalFooter');
        const formEl = document.getElementById('modalForm');

        formEl.querySelectorAll('input[name="_method"]').forEach(i => i.remove());
        itemRowSeq = 0;

        if (action === 'show') {
            const o = order || {};
            const orderId = o.id || '';
            const clientLabel = o.client_name || o.clientId || o.client_id || '—';
            const statusKey = o.status || 'pending';
            const paymentKey = o.payment_status || o.paymentStatus || 'pending';
            const total = o.total_amount || o.total || (o.items || []).reduce((s, it) => s + (Number(it.quantity || 0) * Number(it.unitPrice || it.unit_price || 0)), 0);
            const itemsHtml = (o.items || []).map(it => `
                <tr>
                    <td>${escapeHtml(it.productId || it.product_id || '—')}</td>
                    <td>${escapeHtml(it.quantity || 0)}</td>
                    <td>${formatMoney(it.unitPrice || it.unit_price || 0)}</td>
                    <td class="text-end">${formatMoney((Number(it.quantity) || 0) * (Number(it.unitPrice || it.unit_price) || 0))}</td>
                </tr>
            `).join('');

            titleEl.textContent = 'Detalles del Pedido';
            bodyEl.innerHTML = `
                <div class="row g-3 mb-3">
                    <div class="col-md-6"><strong>ID:</strong> <code>${escapeHtml(orderId)}</code></div>
                    <div class="col-md-6"><strong>Cliente:</strong> ${escapeHtml(clientLabel)}</div>
                    <div class="col-md-4"><strong>Estado:</strong> ${escapeHtml(orderStatuses[statusKey] || statusKey)}</div>
                    <div class="col-md-4"><strong>Pago:</strong> ${escapeHtml(orderPaymentStatuses[paymentKey] || paymentKey)}</div>
                    <div class="col-md-4"><strong>Método:</strong> ${escapeHtml(orderPaymentMethods[o.paymentMethod || ''] || '—')}</div>
                    <div class="col-12"><strong>Notas:</strong> ${escapeHtml(o.notes || '—')}</div>
                </div>
                <h6>Productos</h6>
                <table class="table table-sm">
                    <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>${itemsHtml || '<tr><td colspan="4" class="text-center text-muted">Sin items</td></tr>'}</tbody>
                    <tfoot><tr><td colspan="3" class="text-end"><strong>Total:</strong></td><td class="text-end"><strong>${formatMoney(total)}</strong></td></tr></tfoot>
                </table>
            `;
            footerEl.innerHTML = `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>`;
            formEl.setAttribute('method', 'GET');
            formEl.setAttribute('action', '#');

        } else if (action === 'new') {
            titleEl.textContent = 'Nuevo Pedido';
            bodyEl.innerHTML = buildOrderForm(null);
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Pedido</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/orders');
            attachItemEvents();
            recalcTotals();

        } else if (action === 'edit') {
            titleEl.textContent = 'Editar Pedido';
            bodyEl.innerHTML = buildOrderForm(order) +
                '<input type="hidden" name="_method" value="PUT">';
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/orders/' + encodeURIComponent(order.id || ''));
            attachItemEvents();
            recalcTotals();

        } else if (action === 'status') {
            const statusKey = order.status || 'pending';
            titleEl.textContent = 'Cambiar Estado del Pedido';
            bodyEl.innerHTML = `
                <p>Pedido <code>${escapeHtml(order.id || '')}</code> — Cliente: ${escapeHtml(order.client_name || order.clientId || '—')}</p>
                <div class="mb-3">
                    <label class="form-label">Estado actual: <strong>${escapeHtml(orderStatuses[statusKey] || statusKey)}</strong></label>
                    <select name="status" class="form-select" required>${buildOptions(orderStatuses, statusKey)}</select>
                </div>
                <input type="hidden" name="_method" value="PATCH">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar Estado</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/orders/' + encodeURIComponent(order.id || '') + '/status');

        } else if (action === 'cancel') {
            const total = order.total_amount || order.total || 0;
            titleEl.textContent = 'Cancelar Pedido';
            bodyEl.innerHTML = `
                <div class="text-center">
                    <p class="mt-3">¿Cancelar el pedido <strong><code>${escapeHtml(order.id || '')}</code></strong>?</p>
                    <p class="text-muted mb-0">Cliente: ${escapeHtml(order.client_name || order.clientId || '—')} — Total: ${formatMoney(total)}</p>
                </div>
                <input type="hidden" name="_method" value="DELETE">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
                <button type="submit" class="btn btn-danger">Sí, Cancelar Pedido</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/orders/' + encodeURIComponent(order.id || ''));
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

    // Mostrar toasts en page-load: flash de servidor + flash de AJAX (sessionStorage).
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
    document.getElementById('btnNewOrder')?.addEventListener('click', () => openModal('new', null));
    document.getElementById('btnNewOrderEmpty')?.addEventListener('click', () => openModal('new', null));
    @endif
</script>
@endsection
