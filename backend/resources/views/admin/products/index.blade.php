@extends('layouts.admin')

@section('title', 'Productos - MA Piscinas')
@section('page-title', 'Productos')
@section('page-subtitle', 'Gestión de productos del sistema')

@php
    $currentUser = Auth::user();
    $currentUserRole = $currentUser?->role ?? 'editor';
@endphp

@section('content')
<div class="flex justify-between items-center mb-4">
    <h1>Productos</h1>
    @if($currentUserRole == 'admin')
        <button type="button" class="btn btn-primary" id="btnNewProduct">
            <i class="bi bi-plus-circle"></i> Nuevo Producto
        </button>
    @else
        <button type="button" class="btn btn-outline-danger btn-sm" disabled title="Solo los administradores pueden crear productos">
            Nuevo Producto
        </button>
    @endif
</div>

<!-- Search and Filters -->
<div class="card mb-3">
    <div class="card-body">
        @include('admin.products.partials._search_form')
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                @php
                    $isAdmin = $currentUserRole == 'admin';
                    $isActive = $product['active'] ?? true;
                @endphp
                @include('admin.products.partials._product_row', [
                    'product' => $product,
                    'isAdmin' => $isAdmin,
                    'isActive' => $isActive,
                    'categoryName' => ($categories->firstWhere('id', $product['category_id'] ?? '')['name'] ?? 'N/A')
                ])
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-box display-6"></i>
                        <p class="lead mt-2">No hay productos registrados</p>
                        @if($currentUserRole == 'admin')
                            <button type="button" class="btn btn-primary mt-2" id="btnNewProductEmpty">
                                <i class="bi bi-plus-circle"></i> Crear primer producto
                            </button>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('admin.products.partials._pagination')

<!-- Modal Único Dinámico -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
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
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    let currentAction = '';
    let currentProduct = null;

    // Cargar categorías y subcategorías desde PHP
    const categories = @json($categories->toArray());
    let allSubcategories = @json($subcategories->toArray());

    function openModal(action, product) {
        currentAction = action;
        currentProduct = product;
        const titleEl = document.getElementById('modalTitle');
        const bodyEl = document.getElementById('modalBody');
        const footerEl = document.getElementById('modalFooter');
        const formEl = document.getElementById('modalForm');

        if (action === 'show') {
            titleEl.textContent = 'Detalles del Producto';
            bodyEl.innerHTML = `
                {{-- Sección de imagen pendiente de implementación --}}
                <div class="text-center mb-3">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width: 80px; height: 80px;">
                        <i class="bi bi-box display-6 text-muted"></i>
                    </div>
                </div>
                <p><strong>Nombre:</strong> ${escapeHtml(product.name)}</p>
                <p><strong>Descripción:</strong> ${escapeHtml(product.description || '—')}</p>
                <p><strong>Categoría:</strong> ${escapeHtml(categories.find(c => c.id === product.category_id)?.name || 'N/A')}</p>
                <p><strong>Subcategoría:</strong> ${escapeHtml(allSubcategories.find(s => s.id === product.subcategory_id)?.name || '—')}</p>
                <p><strong>SKU:</strong> ${escapeHtml(product.sku)}</p>
                <p><strong>Precio:</strong> $${Number(product.price).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                <p><strong>Stock:</strong> ${product.stock}</p>
                <p><strong>Stock Mínimo:</strong> ${product.min_stock ?? '—'}</p>
                <p><strong>Destacado:</strong> ${product.featured ? '<span class="badge bg-warning">Sí</span>' : '<span class="badge bg-secondary">No</span>'}</p>
                <p><strong>Estado:</strong> ${product.active ?
                    '<span class="badge bg-success">Activo</span>' :
                    '<span class="badge bg-danger">Inactivo</span>'}</p>
                <p><strong>Creado:</strong> ${new Date(product.created_at).toLocaleString()}</p>
                <p><strong>Actualizado:</strong> ${new Date(product.updated_at).toLocaleString()}</p>
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            `;
            formEl.setAttribute('method', 'GET');

        } else if (action === 'new') {
            titleEl.textContent = 'Nuevo Producto';
            loadFormFields({}, true);
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSubmit">Crear Producto</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '{{ route('admin.products.store') }}');

        } else if (action === 'edit') {
            titleEl.textContent = 'Editar Producto';
            loadFormFields(product, false);
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSubmit">Guardar Cambios</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/products/' + product.id);

        } else if (action === 'deactivate') {
            titleEl.textContent = 'Desactivar Producto';
            bodyEl.innerHTML = `
                <div class="text-center">
                    <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                          style="width: 60px; height: 60px;">
                        <i class="bi bi-lock display-6 text-danger"></i>
                    </div>
                    <p class="mt-3">¿Estás seguro de desactivar el producto <strong>${escapeHtml(product.name)}</strong>?</p>
                    <p class="text-muted mb-0">El producto no estará disponible para nuevas ventas.</p>
                </div>
                <input type="hidden" name="_method" value="DELETE">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Desactivar Producto</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/products/' + product.id);

        } else if (action === 'activate') {
            titleEl.textContent = 'Activar Producto';
            bodyEl.innerHTML = `
                <div class="text-center">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                          style="width: 60px; height: 60px;">
                        <i class="bi bi-unlock display-6 text-success"></i>
                    </div>
                    <p class="mt-3">¿Estás seguro de activar el producto <strong>${escapeHtml(product.name)}</strong>?</p>
                    <p class="text-muted mb-0">El producto volverá a estar disponible en el sistema.</p>
                </div>
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Activar Producto</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/products/' + product.id + '/activate');
        }

        modal.show();
    }

    function loadFormFields(product, isNew) {
        const bodyEl = document.getElementById('modalBody');

        const selectedCategory = product.category_id || '{{ old('category_id', '') }}';
        const selectedSubcategory = product.subcategory_id || '{{ old('subcategory_id', '') }}';

        // Filtrar subcategorías por categoría seleccionada
        const filteredSubcategories = selectedCategory
            ? allSubcategories.filter(s => s.category_id === selectedCategory)
            : [];

        bodyEl.innerHTML = `
            <div class="mb-3">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="${escapeHtml(product.name || '')}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="description" class="form-control" rows="3">${escapeHtml(product.description || '')}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Categoría <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select" id="categorySelect" required>
                    <option value="">Selecciona una categoría</option>
                    ${categories.map(cat => `<option value="${cat.id}" ${selectedCategory === cat.id ? 'selected' : ''}>${escapeHtml(cat.name)}</option>`).join('')}
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Subcategoría (opcional)</label>
                <select name="subcategory_id" class="form-select" id="subcategorySelect">
                    <option value="">Sin subcategoría</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">SKU <span class="text-danger">*</span></label>
                <input type="text" name="sku" class="form-control" value="${escapeHtml(product.sku || '')}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Precio <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="price" class="form-control" value="${product.price || ''}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Costo (opcional)</label>
                <input type="number" step="0.01" name="cost" class="form-control" value="${product.cost || ''}">
            </div>
            <div class="mb-3">
                <label class="form-label">Stock <span class="text-danger">*</span></label>
                <input type="number" name="stock" class="form-control" value="${product.stock ?? 0}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Stock Mínimo <span class="text-danger">*</span></label>
                <input type="number" name="min_stock" class="form-control" value="${product.min_stock ?? 0}" required>
            </div>
            {{-- Campo Imagen Principal (pendiente) --}}
            {{-- <div class="mb-3">
                <label class="form-label">URL Imagen Principal</label>
                <input type="text" name="main_image" class="form-control" placeholder="https://..." value="${product.main_image || ''}">
            </div> --}}
            {{-- Campo Galería Imágenes (pendiente) --}}
            {{-- <div class="mb-3">
                <label class="form-label">Galería de Imágenes (URLs, una por línea)</label>
                <textarea name="images" class="form-control" rows="3" placeholder="https://...&#10;https://...">${(product.images || []).join('\n')}</textarea>
            </div> --}}
            <div class="mb-3 form-check">
                <input type="checkbox" name="featured" class="form-check-input" id="featuredCheck" value="1" ${product.featured ? 'checked' : ''}>
                <label class="form-check-label" for="featuredCheck">Destacado</label>
            </div>
            <div class="mb-3">
                <label class="form-label">Estado</label>
                <select name="active" class="form-select">
                    <option value="1" ${(product.active ?? true) ? 'selected' : ''}>Activo</option>
                    <option value="0" ${!(product.active ?? true) ? 'selected' : ''}>Inactivo</option>
                </select>
            </div>
            <input type="hidden" name="_method" value="PUT">
        `;

        // Llenar subcategorías después de renderizar
        setTimeout(() => {
            const subcatSelect = document.getElementById('subcategorySelect');
            if (subcatSelect) {
                filteredSubcategories.forEach(sc => {
                    const opt = document.createElement('option');
                    opt.value = sc.id;
                    opt.textContent = sc.name;
                    if (selectedSubcategory === sc.id) opt.selected = true;
                    subcatSelect.appendChild(opt);
                });
            }
        }, 0);
    }

    function escapeHtml(text) {
        if (!text) return '';
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

    // Filtro dinámico de subcategorías
    document.getElementById('categorySelect')?.addEventListener('change', function() {
        const categoryId = this.value;
        const subcatSelect = document.getElementById('subcategorySelect');
        subcatSelect.innerHTML = '<option value="">Sin subcategoría</option>';

        if (categoryId) {
            allSubcategories
                .filter(s => s.category_id === categoryId && s.active)
                .forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name;
                    subcatSelect.appendChild(opt);
                });
        }
    });

    // Botones de nuevo producto
    document.getElementById('btnNewProduct')?.addEventListener('click', () => openModal('new', null));
    document.getElementById('btnNewProductEmpty')?.addEventListener('click', () => openModal('new', null));
</script>
@endsection
