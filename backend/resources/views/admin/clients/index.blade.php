@extends('layouts.admin')

@section('title', 'Clientes - MA Piscinas')
@section('page-title', 'Clientes')
@section('page-subtitle', 'Gestión de clientes del sistema')

@section('styles')
<style>
    .modal-backdrop.show { opacity: 0.5; }
    .empty-state { text-align: center; padding: 2rem; color: #6c757d; }
</style>
@endsection

@section('content')
@php
    $currentUser = Auth::user();
    $currentUserRole = $currentUser?->role ?? 'editor';
@endphp

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1>Clientes</h1>
    @if($currentUserRole == 'admin')
        <button type="button" class="btn btn-primary" id="btnNewClient">
            <i class="bi bi-plus-circle"></i> Nuevo Cliente
        </button>
    @else
        <button type="button" class="btn btn-outline-danger btn-sm" disabled title="Solo los administradores pueden crear clientes">
            Nuevo Cliente
        </button>
    @endif    
</div>

<!-- Search and Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.clients.index') }}" class="row g-3">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o email..." value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="active" {{ ($statusFilter ?? '') == 'active' ? 'selected' : '' }}>Activo</option>
                    <option value="inactive" {{ ($statusFilter ?? '') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                @if($search || $statusFilter)
                    <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary w-100 mt-1">Limpiar</a>
                @endif
            </div>
        </form>
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
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($clients as $client)
                @php
                    $isAdmin = $currentUserRole == 'admin';
                    $isActive = $client['active'] ?? true;
                @endphp
                <tr class="{{ !$isActive ? 'table-light' : '' }}">
                    <td>{{ $client['name'] ?? 'N/A' }}</td>
                    <td>{{ $client['email'] ?? 'N/A' }}</td>
                    <td>{{ $client['phone'] ?? '—' }}</td>
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
                            onclick="openModal('show', {{ json_encode($client) }})" 
                            title="Ver detalles">
                            <i class="bi bi-eye">Ver Detalles</i>
                        </button>
                        
                        <!-- Editar -->
                        @if($isAdmin)
                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                onclick="openModal('edit', {{ json_encode($client) }})" 
                                title="Editar">
                                <i class="bi bi-pencil">Editar</i>
                            </button>
                        @else
                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled 
                                title="Solo los administradores pueden editar clientes">
                                <i class="bi bi-pencil">Editar</i>
                            </button>
                        @endif
                        
                        <!-- Desactivar/Activar -->
                        @if($isAdmin)
                            @if($isActive)
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="openModal('deactivate', {{ json_encode($client) }})" 
                                    title="Desactivar cliente">
                                    <i class="bi bi-lock">Desactivar</i>
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-outline-success" 
                                    onclick="openModal('activate', {{ json_encode($client) }})" 
                                    title="Activar cliente">
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
            @empty
                <tr>
                    <td colspan="5" class="empty-state">
                        <i class="bi bi-people display-6"></i>
                        <p class="lead mt-2">No hay clientes registrados</p>
                        @if($currentUserRole == 'admin')
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

<!-- Pagination -->
@if(isset($hasMore) && ($hasMore || ($page ?? 1) > 1))
<div class="d-flex justify-content-between align-items-center mt-3">
    <div>
        @if(($page ?? 1) > 1)
            <a href="{{ route('admin.clients.index', array_merge(['page' => ($page ?? 1) - 1, 'after' => request('after_prev')], array_filter(['search' => $search ?? '', 'status' => $statusFilter ?? '']))) }}" 
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
            <a href="{{ route('admin.clients.index', array_merge(['page' => ($page ?? 1) + 1, 'after' => $lastDocumentId ?? ''], array_filter(['search' => $search ?? '', 'status' => $statusFilter ?? '']))) }}" 
               class="btn btn-outline-primary btn-sm">
               Siguiente <i class="bi bi-chevron-right"></i>
            </a>
        @endif
    </div>
</div>
@endif

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
        currentAction = action;
        currentClient = client;
        const titleEl = document.getElementById('modalTitle');
        const bodyEl = document.getElementById('modalBody');
        const footerEl = document.getElementById('modalFooter');
        const formEl = document.getElementById('modalForm');

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
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" value="${escapeHtml(client.name)}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="${escapeHtml(client.email)}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="phone" class="form-control" value="${escapeHtml(client.phone || '')}">
                </div>
                <input type="hidden" name="_method" value="PUT">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/clients/' + client.id);

        } else if (action === 'new') {
            titleEl.textContent = 'Nuevo Cliente';
            bodyEl.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="phone" class="form-control">
                </div>
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Cliente</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/clients');

        } else if (action === 'deactivate') {
            titleEl.textContent = 'Desactivar Cliente';
            bodyEl.innerHTML = `
                <div class="text-center">
                    <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 60px; height: 60px;">
                        <i class="bi bi-lock display-6 text-danger"></i>
                    </div>
                    <p class="mt-3">¿Estás seguro de desactivar al cliente <strong>${escapeHtml(client.name)}</strong>?</p>
                    <p class="text-muted mb-0">El cliente no podrá ser seleccionado en futuras operaciones.</p>
                </div>
                <input type="hidden" name="_method" value="DELETE">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Desactivar Cliente</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/clients/' + client.id);

        } else if (action === 'activate') {
            titleEl.textContent = 'Activar Cliente';
            bodyEl.innerHTML = `
                <div class="text-center">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 60px; height: 60px;">
                        <i class="bi bi-unlock display-6 text-success"></i>
                    </div>
                    <p class="mt-3">¿Estás seguro de activar al cliente <strong>${escapeHtml(client.name)}</strong>?</p>
                    <p class="text-muted mb-0">El cliente volverá a estar disponible en el sistema.</p>
                </div>
                <input type="hidden" name="_method" value="POST">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" formmethod="post" class="btn btn-success">Activar Cliente</button>
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

    // Manejo AJAX del formulario (errores inline)
    document.getElementById('modalForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const method = form.querySelector('input[name="_method"]')?.value || 'POST';
        const url = form.action;

        // Limpiar errores previos
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

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
                window.location.href = data.redirect || window.location.href;
            } else if (r.status === 422) {
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

    // Botones de nuevo cliente
    document.getElementById('btnNewClient')?.addEventListener('click', () => openModal('new', null));
    document.getElementById('btnNewClientEmpty')?.addEventListener('click', () => openModal('new', null));
</script>
@endsection