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
    <button type="button" class="btn btn-primary" id="btnNewSubcategory">
        <i class="bi bi-plus-circle"></i> Nueva Subcategoría
    </button>
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
                <th>Nombre</th>
                <th>Slug</th>
                <th>Categoría</th>
                <th>Descripción</th>
                <th>Estado</th>
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
                            <button type="button" class="btn btn-primary mt-2" id="btnNewSubcategoryEmpty">
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
<div class="modal fade" id="subcategoryModal" tabindex="-1" aria-hidden="true">
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
    // Wait for DOM and Bootstrap to be ready
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap not loaded!');
            return;
        }
    });

    let modal = null;
    let currentAction = '';
    let currentSubcategory = null;

    function openModal(action, subcategory) {

        // Initialize modal if not already done
        if (!modal) {
            const modalElement = document.getElementById('subcategoryModal');
            if (!modalElement) {
                console.error('Modal element not found!');
                return;
            }
            modal = new bootstrap.Modal(modalElement);
        }

        currentAction = action;
        currentSubcategory = subcategory;
        const titleEl = document.getElementById('modalTitle');
        const bodyEl = document.getElementById('modalBody');
        const footerEl = document.getElementById('modalFooter');
        const formEl = document.getElementById('modalForm');

        if (action === 'show') {
            titleEl.textContent = 'Detalles de la Subcategoría';
            bodyEl.innerHTML = `
                <div class="text-center mb-3">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                          style="width: 80px; height: 80px;">
                        <i class="bi bi-diagram-3 display-6 text-muted"></i>
                    </div>
                </div>
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
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" id="nameInput" required>
                    <div class="form-text">El slug se generará automáticamente</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" id="slugInput" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Categoría</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Seleccione una categoría</option>
                        ${generateCategoryOptions()}
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
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

            // Update slug in real-time
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
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" id="nameInputEdit" value="${escapeHtml(subcategory.name)}" required>
                    <div class="form-text">El slug se actualizará automáticamente</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" id="slugInputEdit" value="${escapeHtml(subcategory.slug)}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Categoría</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Seleccione una categoría</option>
                        ${generateCategoryOptions(subcategory.category_id)}
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" class="form-control" rows="3">${escapeHtml(subcategory.description || '')}</textarea>
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

            // Update slug in real-time
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
                    <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                          style="width: 60px; height: 60px;">
                        <i class="bi bi-lock display-6 text-danger"></i>
                    </div>
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
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                          style="width: 60px; height: 60px;">
                        <i class="bi bi-unlock display-6 text-success"></i>
                    </div>
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

        modal.show();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

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

    // Botones de nueva subcategoría - delegación de eventos
    document.addEventListener('click', function(e) {
        if (e.target && (e.target.id === 'btnNewSubcategory' || e.target.id === 'btnNewSubcategoryEmpty')) {
            e.preventDefault();
            openModal('new', null);
        }
    });
</script>
@endsection