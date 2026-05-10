@extends('layouts.admin')

@section('title', 'Clientes - MA Piscinas')
@section('page-title', 'Clientes')
@section('page-subtitle', 'Gestión de clientes del sistema')

@section('content')
@php
    $currentUser = Auth::user();
    $currentUserRole = $currentUser?->role ?? 'editor';
    $isAdmin = $currentUserRole === 'admin';
@endphp

<div class="flex justify-between items-center mb-4">
    <h1>Clientes</h1>
    @if($isAdmin)
        <button type="button" class="btn btn-primary" id="btnNewClient">
            <i class="bi bi-plus-circle"></i> Nuevo Cliente
        </button>
    @endif
</div>

<!-- Search and Filters -->
<div class="card mb-3">
    <div class="card-body">
        @include('admin.clients.partials._search_form')
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($clients as $client)
                @php
                    $isActive = $client['active'] ?? true;
                @endphp
                @include('admin.clients.partials._client_row', ['client' => $client, 'isAdmin' => $isAdmin, 'isActive' => $isActive])
            @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="bi bi-people display-6"></i>
                        <p class="lead mt-2">No hay clientes registrados</p>
                        @if($isAdmin)
                            <button type="button" class="btn btn-primary mt-2" id="btnNewClientEmpty">
                                <i class="bi bi-plus-circle"></i> Crear primer cliente
                            </button>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('admin.clients.partials._pagination')

<!-- Modal Único Dinámico -->
<div class="modal fade" id="clientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
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
    const modal = new bootstrap.Modal(document.getElementById('clientModal'));
    let currentAction = '';
    let currentClient = null;

    function openModal(action, client) {
        @if(!$isAdmin)
        if (action === 'new' || action === 'edit') {
            // Solo admin puede crear/editar clientes
            return;
        }
        @endif

        currentAction = action;
        currentClient = client;
        const titleEl = document.getElementById('modalTitle');
        const bodyEl = document.getElementById('modalBody');
        const footerEl = document.getElementById('modalFooter');
        const formEl = document.getElementById('modalForm');

        // Remove any previous method override hidden input
        const existingMethodInput = formEl.querySelector('input[name="_method"]');
        if (existingMethodInput) {
            existingMethodInput.remove();
        }

        if (action === 'show') {
            titleEl.textContent = 'Detalles del Cliente';
            bodyEl.innerHTML = `
                <div class="text-center mb-3">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width: 80px; height: 80px;">
                        <i class="bi bi-person display-6 text-muted"></i>
                    </div>
                </div>
                <p><strong>Nombre:</strong> ${escapeHtml(client.name)}</p>
                <p><strong>Email:</strong> ${escapeHtml(client.email)}</p>
                <p><strong>Teléfono:</strong> ${escapeHtml(client.phone || '—')}</p>
                <p><strong>Dirección:</strong> ${escapeHtml(client.address || '—')}</p>
                <p><strong>Ciudad:</strong> ${escapeHtml(client.city || '—')}</p>
                <p><strong>Notas:</strong> ${escapeHtml(client.notes || '—')}</p>
                <p><strong>Estado:</strong> ${client.active ?
                    '<span class="badge bg-success">Activo</span>' :
                    '<span class="badge bg-danger">Inactivo</span>'}</p>
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            `;
            formEl.setAttribute('method', 'GET');
        } else if (action === 'edit') {
            titleEl.textContent = 'Editar Cliente';
            bodyEl.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Nombre *</label>
                    <input type="text" class="form-control" name="name" value="${escapeHtml(client.name)}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" name="email" value="${escapeHtml(client.email)}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" class="form-control" name="phone" value="${escapeHtml(client.phone || '')}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Dirección</label>
                    <textarea class="form-control" name="address" rows="2">${escapeHtml(client.address || '')}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ciudad</label>
                    <input type="text" class="form-control" name="city" value="${escapeHtml(client.city || '')}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notas</label>
                    <textarea class="form-control" name="notes" rows="2">${escapeHtml(client.notes || '')}</textarea>
                </div>
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/clients/' + client.id);
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'PUT';
            formEl.appendChild(methodInput);
        } else if (action === 'new') {
            titleEl.textContent = 'Nuevo Cliente';
            bodyEl.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Nombre *</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" class="form-control" name="phone">
                </div>
                <div class="mb-3">
                    <label class="form-label">Dirección</label>
                    <textarea class="form-control" name="address" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ciudad</label>
                    <input type="text" class="form-control" name="city">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notas</label>
                    <textarea class="form-control" name="notes" rows="2"></textarea>
                </div>
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Cliente</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/clients');
        } else if (action === 'deactivate') {
            titleEl.innerHTML = '<i class="bi bi-lock"></i> Bloquear Cliente: ' + escapeHtml(client.name);
            bodyEl.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>¿Estás seguro de bloquear este cliente?</strong>
                </div>
                <div class="border rounded p-3 mb-3 bg-light">
                    <p class="mb-1"><strong>Nombre:</strong> ${escapeHtml(client.name)}</p>
                    <p class="mb-1"><strong>Email:</strong> ${escapeHtml(client.email)}</p>
                </div>
                <p class="text-muted">El cliente no podrá acceder al sistema hasta que sea desbloqueado.</p>
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-lock"></i> Sí, Bloquear Cliente
                </button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/clients/' + client.id + '/deactivate');
        } else if (action === 'activate') {
            titleEl.innerHTML = '<i class="bi bi-unlock"></i> Desbloquear Cliente: ' + escapeHtml(client.name);
            bodyEl.innerHTML = `
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <strong>¿Estás seguro de desbloquear este cliente?</strong>
                </div>
                <div class="border rounded p-3 mb-3 bg-light">
                    <p class="mb-1"><strong>Nombre:</strong> ${escapeHtml(client.name)}</p>
                    <p class="mb-1"><strong>Email:</strong> ${escapeHtml(client.email)}</p>
                </div>
                <p class="text-muted">El cliente podrá acceder al sistema nuevamente.</p>
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-unlock"></i> Sí, Desbloquear Cliente
                </button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/clients/' + client.id + '/activate');
        }

        modal.show();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Botones de nuevo cliente (solo admin)
    @if($isAdmin)
    document.getElementById('btnNewClient')?.addEventListener('click', () => openModal('new', null));
    document.getElementById('btnNewClientEmpty')?.addEventListener('click', () => openModal('new', null));
    @endif
</script>
@endsection