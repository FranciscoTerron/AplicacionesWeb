@extends('layouts.admin')

@section('title', 'Usuarios - MA Piscinas')
@section('page-title', 'Usuarios')
@section('page-subtitle', 'Gestión de usuarios del sistema')

@section('styles')
<style>
    .modal-backdrop.show { opacity: 0.5; }
    .empty-state { text-align: center; padding: 2rem; color: #6c757d; }
</style>
@endsection

@section('content')
@php
    $currentUser = Auth::user();
    $currentUserId = $currentUser?->getAuthIdentifier();
    $currentUserRole = $currentUser?->role ?? 'editor';
@endphp

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1>Usuarios</h1>
    @if($currentUser && $currentUserRole == 'admin')
        <button type="button" class="btn btn-primary" id="btnNewUser">
            <i class="bi bi-plus-circle"></i> Nuevo Usuario
        </button>
    @else
        <button type="button" class="btn btn-outline-danger btn-sm" disabled title="No puedes crear usuarios">
            Nuevo Usuario
        </button>
    @endif    
</div>

<!-- Search and Filters -->
<div class="card mb-3">
    <div class="card-body">
        @include('admin.users.partials._search_form')
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                @php
                    $isAdmin = $currentUserRole == 'admin';
                    $isOwnProfile = $currentUserId == ($user['id'] ?? '');
                    $isActive = $user['active'] ?? true;
                @endphp
                @include('admin.users.partials._user_row', ['user' => $user, 'isAdmin' => $isAdmin, 'isOwnProfile' => $isOwnProfile, 'isActive' => $isActive])
            @empty
                <tr>
                    <td colspan="5" class="empty-state">
                        <i class="bi bi-people display-6"></i>
                        <p class="lead mt-2">No hay usuarios registrados</p>
                        @if($currentUserRole == 'admin')
                            <button type="button" class="btn btn-primary mt-2" id="btnNewUserEmpty">
                                <i class="bi bi-plus-circle"></i> Crear primer usuario
                            </button>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('admin.users.partials._pagination')

<!-- Modal Único Dinámico -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
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
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    let currentAction = '';
    let currentUser = null;

    function openModal(action, user) {
        currentAction = action;
        currentUser = user;
        const titleEl = document.getElementById('modalTitle');
        const bodyEl = document.getElementById('modalBody');
        const footerEl = document.getElementById('modalFooter');
        const formEl = document.getElementById('modalForm');

        if (action === 'show') {
            titleEl.textContent = 'Detalles del Usuario';
            bodyEl.innerHTML = `
                <div class="text-center mb-3">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px;">
                        <i class="bi bi-person display-6 text-muted"></i>
                    </div>
                </div>
                <p><strong>Nombre:</strong> ${escapeHtml(user.name)}</p>
                <p><strong>Email:</strong> ${escapeHtml(user.email)}</p>
                <p><strong>Rol:</strong> ${user.role === 'admin' ? 
                    '<span class="badge bg-primary">Administrador</span>' : 
                    '<span class="badge bg-secondary">Editor</span>'}</p>
                <p><strong>Estado:</strong> ${user.active ? 
                    '<span class="badge bg-success">Activo</span>' : 
                    '<span class="badge bg-danger">Bloqueado</span>'}</p>
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            `;
            formEl.setAttribute('method', 'GET');

        } else if (action === 'edit') {
            titleEl.textContent = 'Editar Usuario';
            bodyEl.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" value="${escapeHtml(user.name)}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="${escapeHtml(user.email)}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Rol</label>
                    <select name="role" class="form-select">
                        <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Administrador</option>
                        <option value="editor" ${user.role === 'editor' ? 'selected' : ''}>Editor</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nueva contraseña (dejar en blanco para mantener)</label>
                    <input type="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres">
                    <div class="form-text">Si no completas este campo, la contraseña actual se mantendrá.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar nueva contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Repite la nueva contraseña">
                    <div class="form-text">Solo si cambias la contraseña arriba.</div>
                </div>
                <input type="hidden" name="_method" value="PUT">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/users/' + user.id);

        } else if (action === 'new') {
            titleEl.textContent = 'Nuevo Usuario';
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
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Rol</label>
                    <select name="role" class="form-select">
                        <option value="editor">Editor</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Usuario</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/users');

        } else if (action === 'delete') {
            titleEl.textContent = 'Bloquear Usuario';
            bodyEl.innerHTML = `
                <div class="text-center">
                    <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 60px; height: 60px;">
                        <i class="bi bi-lock display-6 text-danger"></i>
                    </div>
                    <p class="mt-3">¿Estás seguro de bloquear al usuario <strong>${escapeHtml(user.name)}</strong>?</p>
                    <p class="text-muted mb-0">Esta acción evitará que el usuario acceda al sistema.</p>
                </div>
                <input type="hidden" name="_method" value="DELETE">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Bloquear Usuario</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/users/' + user.id);

        } else if (action === 'activate') {
            titleEl.textContent = 'Desbloquear Usuario';
            bodyEl.innerHTML = `
                <div class="text-center">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 60px; height: 60px;">
                        <i class="bi bi-unlock display-6 text-success"></i>
                    </div>
                    <p class="mt-3">¿Estás seguro de desbloquear al usuario <strong>${escapeHtml(user.name)}</strong>?</p>
                    <p class="text-muted mb-0">El usuario podrá acceder al sistema normalmente.</p>
                </div>
                <input type="hidden" name="_method" value="POST">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" formmethod="post" class="btn btn-success">Desbloquear Usuario</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/users/' + user.id + '/activate');
        }

        modal.show();
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
    // Botones de nuevo usuario
    document.getElementById('btnNewUser')?.addEventListener('click', () => openModal('new', null));
    document.getElementById('btnNewUserEmpty')?.addEventListener('click', () => openModal('new', null));
</script>
@endsection
