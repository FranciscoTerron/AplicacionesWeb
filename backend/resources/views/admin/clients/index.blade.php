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
    <div class="d-flex gap-2">
        @include('admin.partials._export_button', ['entityName' => 'clients'])
        @if($isAdmin)
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnNewClient" aria-label="Crear nuevo cliente">
                <i class="bi bi-plus-circle"></i> Nuevo Cliente
            </button>
        @endif
    </div>
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
                 <th>@include('admin.partials._sort_header', ['field' => 'name', 'label' => 'Nombre', 'sort' => $sort ?? '', 'order' => $order ?? ''])</th>
                 <th>@include('admin.partials._sort_header', ['field' => 'email', 'label' => 'Email', 'sort' => $sort ?? '', 'order' => $order ?? ''])</th>
                 <th>@include('admin.partials._sort_header', ['field' => 'phone', 'label' => 'Teléfono', 'sort' => $sort ?? '', 'order' => $order ?? ''])</th>
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
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btnNewClientEmpty" aria-label="Crear primer cliente">
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
<div class="modal fade" id="clientModal" tabindex="-1" aria-hidden="true" aria-labelledby="modalTitle">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">-</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="modalForm" action="#" method="POST" aria-describedby="modalDescription">
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

@include('admin.partials._modal_focus')

@section('scripts')
<script>
    let modal = null;
    let modalElement = null;
    let lastFocusedButton = null;
    let currentAction = '';
    let currentClient = null;

    function openModal(action, client, triggerButton) {
        @if(!$isAdmin)
        if (action === 'new' || action === 'edit') {
            return;
        }
        @endif

        if (!modal) {
            modalElement = document.getElementById('clientModal');
            if (!modalElement) return;
            modal = new bootstrap.Modal(modalElement);
        }

        ModalFocusManager.rememberFocus();
        lastFocusedButton = triggerButton || document.activeElement;
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
                    <label for="nameInputEdit" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" id="nameInputEdit" value="${escapeHtml(client.name)}" required aria-required="true" aria-describedby="nameHelpEdit">
                    <div class="form-text" id="nameHelpEdit">Nombre completo del cliente.</div>
                </div>
                <div class="mb-3">
                    <label for="emailInputEdit" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" id="emailInputEdit" value="${escapeHtml(client.email)}" required aria-required="true" aria-describedby="emailHelpEdit">
                    <div class="form-text" id="emailHelpEdit">Correo electrónico válido para contacto y acceso.</div>
                </div>
                <div class="mb-3">
                    <label for="passwordInputEdit" class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" id="passwordInputEdit" minlength="8" aria-describedby="passwordHelpEdit">
                    <div class="form-text" id="passwordHelpEdit">Mínimo 8 caracteres. Dejar vacío para no cambiar.</div>
                </div>
                <div class="mb-3">
                    <label for="phoneInputEdit" class="form-label">Teléfono</label>
                    <input type="tel" name="phone" class="form-control" id="phoneInputEdit" value="${escapeHtml(client.phone || '')}" aria-describedby="phoneHelpEdit">
                    <div class="form-text" id="phoneHelpEdit">Número de teléfono de contacto (opcional).</div>
                </div>
                <div class="mb-3">
                    <label for="addressInputEdit" class="form-label">Dirección</label>
                    <textarea name="address" class="form-control" id="addressInputEdit" rows="2" aria-describedby="addressHelpEdit">${escapeHtml(client.address || '')}</textarea>
                    <div class="form-text" id="addressHelpEdit">Dirección física del cliente (opcional).</div>
                </div>
                <div class="mb-3">
                    <label for="cityInputEdit" class="form-label">Ciudad</label>
                    <input type="text" name="city" class="form-control" id="cityInputEdit" value="${escapeHtml(client.city || '')}">
                </div>
                <div class="mb-3">
                    <label for="notesInputEdit" class="form-label">Notas</label>
                    <textarea name="notes" class="form-control" id="notesInputEdit" rows="2">${escapeHtml(client.notes || '')}</textarea>
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
                    <label for="nameInputNew" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" id="nameInputNew" required aria-required="true" aria-describedby="nameHelpNew">
                    <div class="form-text" id="nameHelpNew">Nombre completo del cliente.</div>
                </div>
                <div class="mb-3">
                    <label for="emailInputNew" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" id="emailInputNew" required aria-required="true" aria-describedby="emailHelpNew">
                    <div class="form-text" id="emailHelpNew">Correo electrónico válido para contacto y acceso.</div>
                </div>
                <div class="mb-3">
                    <label for="passwordInputNew" class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" id="passwordInputNew" minlength="8" aria-describedby="passwordHelpNew">
                    <div class="form-text" id="passwordHelpNew">Mínimo 8 caracteres. Dejar vacío si no requiere acceso e-commerce.</div>
                </div>
                <div class="mb-3">
                    <label for="phoneInputNew" class="form-label">Teléfono</label>
                    <input type="tel" name="phone" class="form-control" id="phoneInputNew" aria-describedby="phoneHelpNew">
                    <div class="form-text" id="phoneHelpNew">Número de teléfono de contacto (opcional).</div>
                </div>
                <div class="mb-3">
                    <label for="addressInputNew" class="form-label">Dirección</label>
                    <textarea name="address" class="form-control" id="addressInputNew" rows="2" aria-describedby="addressHelpNew"></textarea>
                    <div class="form-text" id="addressHelpNew">Dirección física del cliente (opcional).</div>
                </div>
                <div class="mb-3">
                    <label for="cityInputNew" class="form-label">Ciudad</label>
                    <input type="text" name="city" class="form-control" id="cityInputNew">
                </div>
                <div class="mb-3">
                    <label for="notesInputNew" class="form-label">Notas</label>
                    <textarea name="notes" class="form-control" id="notesInputNew" rows="2"></textarea>
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
                <div class="alert alert-danger" role="alert">
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
                <div class="alert alert-success" role="alert">
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

        ModalFocusManager.trapFocus(modalElement);
        modal.show();

        const firstInput = modalElement.querySelector('input:not([type="hidden"]), textarea, select');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 150);
        }
    }

    modalElement?.addEventListener('hide.bs.modal', function () {
        setTimeout(() => {
            if (lastFocusedButton) {
                lastFocusedButton.focus();
            }
        }, 150);
    });

    function showFieldError(input, message) {
        input.classList.add('is-invalid');
        const errorId = input.name + '-error-' + Date.now();
        input.setAttribute('aria-invalid', 'true');
        const existingDesc = input.getAttribute('aria-describedby');
        input.setAttribute('aria-describedby', existingDesc ? existingDesc + ' ' + errorId : errorId);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.id = errorId;
        errorDiv.setAttribute('role', 'alert');
        errorDiv.textContent = message;
        input.parentNode.appendChild(errorDiv);
    }

    function clearFieldErrors() {
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    }

    function validateForm() {
        clearFieldErrors();
        let isValid = true;
        const form = document.getElementById('modalForm');

        ['name', 'email'].forEach(fieldName => {
            const input = form.querySelector(`[name="${fieldName}"]`);
            if (input && input.hasAttribute('required') && !input.value.trim()) {
                showFieldError(input, 'Este campo es obligatorio.');
                isValid = false;
            }
        });

        const emailInput = form.querySelector('[name="email"]');
        if (emailInput && emailInput.value.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.value.trim())) {
                showFieldError(emailInput, 'Ingrese un correo electrónico válido.');
                isValid = false;
            }
        }

        return isValid;
    }

    document.getElementById('modalForm').addEventListener('submit', function(e) {
        if ((currentAction === 'new' || currentAction === 'edit') && !validateForm()) {
            e.preventDefault();
            return;
        }
    });

    // Botones de nuevo cliente (solo admin)
    @if($isAdmin)
    document.getElementById('btnNewClient')?.addEventListener('click', function() { openModal('new', null, this); });
    document.getElementById('btnNewClientEmpty')?.addEventListener('click', function() { openModal('new', null, this); });
    @endif
</script>
@endsection