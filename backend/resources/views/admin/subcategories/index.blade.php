@extends('layouts.admin')

@section('title', 'Subcategorías - MA Piscinas')
@section('page-title', 'Subcategorías')
@section('page-subtitle', 'Gestión de subcategorías del sistema')

@section('content')
@php
    $currentUser = Auth::user();
    $currentUserRole = $currentUser?->role ?? 'editor';
@endphp

<div class="flex justify-between items-center mb-4">
    <div>
        <h1>Subcategorías</h1>
    </div>
    <div class="d-flex gap-2">
        @if($currentUserRole == 'admin')
            @include('admin.partials._import_button', ['entityName' => 'subcategories'])
        @endif
        @include('admin.partials._export_button', ['entityName' => 'subcategories'])
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnNewSubcategory" aria-label="Crear nueva subcategoría">
            <i class="bi bi-plus-circle"></i> Nueva Subcategoría
        </button>
    </div>
</div>

<!-- Search and Filters -->
<div class="card mb-3">
    <div class="card-body">
        @include('admin.subcategories.partials._search_form')
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>@include('admin.partials._sort_header', ['field' => 'name', 'label' => 'Nombre', 'sort' => $sort ?? '', 'order' => $order ?? ''])</th>
                <th>Slug</th>
                <th>Categoría</th>
                <th>Descripción</th>
                <th>@include('admin.partials._sort_header', ['field' => 'active', 'label' => 'Estado', 'sort' => $sort ?? '', 'order' => $order ?? ''])</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($subcategories as $subcategory)
                @php
                    $isAdmin = $currentUserRole == 'admin';
                    $canManage = in_array($currentUserRole, ['admin', 'editor']);
                    $isActive = $subcategory['active'] ?? true;
                @endphp
                @include('admin.subcategories.partials._subcategory_row', ['subcategory' => $subcategory, 'categories' => $categories, 'isAdmin' => $isAdmin, 'canManage' => $canManage, 'isActive' => $isActive])
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-diagram-3 display-6"></i>
                        <p class="lead mt-2">No hay subcategorías registradas</p>
                        @if($currentUserRole == 'admin')
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btnNewSubcategoryEmpty" aria-label="Crear primera subcategoría">
                                <i class="bi bi-plus-circle"></i> Crear primera subcategoría
                            </button>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('admin.subcategories.partials._pagination')

<!-- Modal Único Dinámico -->
<div class="modal fade" id="subcategoryModal" tabindex="-1" aria-hidden="true" aria-labelledby="modalTitle">
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
    let currentSubcategory = null;

    function getCategoryName(categoryId) {
        @if($categories && $categories->count() > 0)
            @foreach($categories as $category)
                if (categoryId === '{{ $category['id'] }}') {
                    return '{{ addslashes($category['name']) }}';
                }
            @endforeach
        @endif
        return 'N/A';
    }

    function generateCategoryOptions(selectedId = null) {
        let options = '';
        @if($categories && $categories->count() > 0)
            @foreach($categories as $category)
                options += `<option value="{{ $category['id'] }}" ${selectedId === '{{ $category['id'] }}' ? 'selected' : ''}>{{ addslashes($category['name']) }}</option>`;
            @endforeach
        @endif
        return options;
    }

    function openModal(action, subcategory, triggerButton) {
        if (!modal) {
            modalElement = document.getElementById('subcategoryModal');
            if (!modalElement) return;
            modal = new bootstrap.Modal(modalElement);
        }
        
        lastFocusedButton = triggerButton || document.activeElement;
        currentAction = action;
        currentSubcategory = subcategory;
        
        const titleEl = document.getElementById('modalTitle');
        const bodyEl = document.getElementById('modalBody');
        const footerEl = document.getElementById('modalFooter');
        const formEl = document.getElementById('modalForm');

        if (action === 'show') {
            titleEl.textContent = 'Detalles de la Subcategoría';
            bodyEl.innerHTML = `
                <div class="text-center mb-3"></div>
                <p><strong>Nombre:</strong> ${escapeHtml(subcategory.name)}</p>
                <p><strong>Slug:</strong> <code>${escapeHtml(subcategory.slug)}</code></p>
                <p><strong>Categoría:</strong> ${getCategoryName(subcategory.category_id)}</p>
                <p><strong>Descripción:</strong> ${escapeHtml(subcategory.description || '—')}</p>
                <p><strong>Estado:</strong> ${subcategory.active ?
                    '<span class="badge bg-success">Activo</span>' :
                    '<span class="badge bg-danger">Inactivo</span>'}</p>
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            `;
            formEl.setAttribute('method', 'GET');

        } else if (action === 'new') {
            titleEl.textContent = 'Nueva Subcategoría';
            bodyEl.innerHTML = `
                <div class="mb-3">
                    <label for="nameInput" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" id="nameInput" required aria-required="true"
                           aria-describedby="nameHelp">
                    <div class="form-text" id="nameHelp">El slug se generará automáticamente al escribir el nombre.</div>
                </div>
                <div class="mb-3">
                    <label for="slugInput" class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" id="slugInput" readonly aria-readonly="true">
                </div>
                <div class="mb-3">
                    <label for="categorySelect" class="form-label">Categoría <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" id="categorySelect" required aria-required="true"
                            aria-describedby="categoryHelp">
                        <option value="">Seleccione una categoría</option>
                        ${generateCategoryOptions()}
                    </select>
                    <div class="form-text" id="categoryHelp">La categoría padre de esta subcategoría.</div>
                </div>
                <div class="mb-3">
                    <label for="descriptionInput" class="form-label">Descripción</label>
                    <textarea name="description" class="form-control" id="descriptionInput" rows="3"
                              aria-describedby="descriptionHelp"></textarea>
                    <div class="form-text" id="descriptionHelp">Descripción corta (opcional).</div>
                </div>
                <input type="hidden" name="active" value="0">
                <div class="mb-3 form-check">
                    <input type="checkbox" name="active" class="form-check-input" id="activeCheck" value="1" checked>
                    <label class="form-check-label" for="activeCheck">Activo</label>
                </div>
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Subcategoría</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/subcategories');

            document.getElementById('nameInput').addEventListener('input', function() {
                const slug = this.value.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim('-');
                document.getElementById('slugInput').value = slug;
            });

        } else if (action === 'edit') {
            titleEl.textContent = 'Editar Subcategoría';
            bodyEl.innerHTML = `
                <div class="mb-3">
                    <label for="nameInputEdit" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" id="nameInputEdit" value="${escapeHtml(subcategory.name)}" required aria-required="true"
                           aria-describedby="nameHelpEdit">
                    <div class="form-text" id="nameHelpEdit">El slug se actualizará automáticamente.</div>
                </div>
                <div class="mb-3">
                    <label for="slugInputEdit" class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" id="slugInputEdit" value="${escapeHtml(subcategory.slug)}" readonly aria-readonly="true">
                </div>
                <div class="mb-3">
                    <label for="categorySelectEdit" class="form-label">Categoría <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" id="categorySelectEdit" required aria-required="true"
                            aria-describedby="categoryHelpEdit">
                        <option value="">Seleccione una categoría</option>
                        ${generateCategoryOptions(subcategory.category_id)}
                    </select>
                    <div class="form-text" id="categoryHelpEdit">La categoría padre de esta subcategoría.</div>
                </div>
                <div class="mb-3">
                    <label for="descriptionInputEdit" class="form-label">Descripción</label>
                    <textarea name="description" class="form-control" id="descriptionInputEdit" rows="3"
                              aria-describedby="descriptionHelpEdit">${escapeHtml(subcategory.description || '')}</textarea>
                    <div class="form-text" id="descriptionHelpEdit">Descripción corta.</div>
                </div>
                <input type="hidden" name="active" value="0">
                <div class="mb-3 form-check">
                    <input type="checkbox" name="active" class="form-check-input" id="activeCheckEdit" value="1" ${subcategory.active ? 'checked' : ''}>
                    <label class="form-check-label" for="activeCheckEdit">Activo</label>
                </div>
                <input type="hidden" name="_method" value="PUT">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/subcategories/' + subcategory.id);

            document.getElementById('nameInputEdit').addEventListener('input', function() {
                const slug = this.value.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim('-');
                document.getElementById('slugInputEdit').value = slug;
            });

        } else if (action === 'deactivate') {
            titleEl.textContent = 'Desactivar Subcategoría';
            bodyEl.innerHTML = `
                <div class="text-center">
                    <p class="mt-3">¿Estás seguro de desactivar la subcategoría <strong>${escapeHtml(subcategory.name)}</strong>?</p>
                    <p class="text-muted mb-0">La subcategoría no podrá ser utilizada en nuevos productos.</p>
                </div>
                <input type="hidden" name="_method" value="DELETE">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Desactivar Subcategoría</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/subcategories/' + subcategory.id);

        } else if (action === 'activate') {
            titleEl.textContent = 'Activar Subcategoría';
            bodyEl.innerHTML = `
                <div class="text-center">
                    <p class="mt-3">¿Estás seguro de activar la subcategoría <strong>${escapeHtml(subcategory.name)}</strong>?</p>
                    <p class="text-muted mb-0">La subcategoría volverá a estar disponible en el sistema.</p>
                </div>
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Activar Subcategoría</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/subcategories/' + subcategory.id + '/activate');
        }

        ModalFocusManager.trapFocus(modalElement);
        modal.show();
        
        const firstInput = modalElement.querySelector('input:not([type="hidden"]), textarea, select');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 150);
        }
    }

    modalElement?.addEventListener('hide.bs.modal', function() {
        setTimeout(() => {
            if (lastFocusedButton) {
                lastFocusedButton.focus();
            }
        }, 150);
    });

    document.getElementById('subcategoryModal').addEventListener('click', function(e) {
        if (e.target.matches('[data-action]')) {
            const action = e.target.getAttribute('data-action');
            const subcategory = JSON.parse(e.target.getAttribute('data-category'));
            openModal(action, subcategory, e.target);
        }
    });

    document.getElementById('modalForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const method = form.querySelector('input[name="_method"]')?.value || 'POST';
        const url = form.action;

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
                        input.setAttribute('aria-invalid', 'true');
                        const errorId = `${field}-error-inline`;
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'invalid-feedback';
                        errorDiv.id = errorId;
                        errorDiv.setAttribute('role', 'alert');
                        errorDiv.textContent = Array.isArray(data.errors[field])
                            ? data.errors[field][0]
                            : data.errors[field];
                        input.setAttribute('aria-describedby', errorId);
                        input.parentNode.appendChild(errorDiv);
                    }
                });
            } else {
                const footer = document.getElementById('modalFooter');
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger mt-3 mb-0';
                alertDiv.setAttribute('role', 'alert');
                alertDiv.textContent = data.message || 'Ocurrió un error. Por favor, inténtalo nuevamente.';
                footer.insertBefore(alertDiv, footer.firstChild);
                setTimeout(() => alertDiv.remove(), 5000);
            }
        }).catch(err => {
            const footer = document.getElementById('modalFooter');
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger mt-3 mb-0';
            alertDiv.setAttribute('role', 'alert');
            alertDiv.textContent = 'Error de conexión. Por favor, inténtalo nuevamente.';
            footer.insertBefore(alertDiv, footer.firstChild);
            setTimeout(() => alertDiv.remove(), 5000);
        });
    });

    window.openSubcategoryModal = openModal;

    document.getElementById('btnNewSubcategory')?.addEventListener('click', function() {
        openModal('new', null, this);
    });
    
    document.getElementById('btnNewSubcategoryEmpty')?.addEventListener('click', function() {
        openModal('new', null, this);
    });
</script>
@endsection