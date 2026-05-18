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
    <button type="button" class="btn btn-sm btn-outline-primary" id="btnNewProduct">
        <i class="bi bi-plus-circle"></i> Nuevo Producto
    </button>
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
                    $canManage = in_array($currentUserRole, ['admin', 'editor']);
                    $isActive = $product['active'] ?? true;
                @endphp
                @include('admin.products.partials._product_row', [
                    'product' => $product,
                    'isAdmin' => $isAdmin,
                    'canManage' => $canManage,
                    'isActive' => $isActive,
                    'categoryName' => ($categories->firstWhere('id', $product['category_id'] ?? '')['name'] ?? 'N/A')
                ])
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-box display-6"></i>
                        <p class="lead mt-2">No hay productos registrados</p>
                        @if(in_array($currentUserRole, ['admin', 'editor']))
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btnNewProductEmpty">
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
    // Wait for DOM and Bootstrap to be ready
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap not loaded!');
            return;
        }
    });

    let modal = null;
    let currentAction = '';
    let currentProduct = null;

    function openModal(action, product) {
        // Initialize modal if not already done
        if (!modal) {
            const modalElement = document.getElementById('productModal');
            if (!modalElement) {
                console.error('Modal element not found!');
                return;
            }
            modal = new bootstrap.Modal(modalElement);
        }

        currentAction = action;
        currentProduct = product;
        const titleEl = document.getElementById('modalTitle');
        const bodyEl = document.getElementById('modalBody');
        const footerEl = document.getElementById('modalFooter');
        const formEl = document.getElementById('modalForm');

        if (action === 'show') {
            titleEl.textContent = 'Detalles del Producto';
            bodyEl.innerHTML = `
                <div class="text-center mb-3">
                </div>
                <p><strong>Nombre:</strong> ${escapeHtml(product.name)}</p>
                <p><strong>Descripción:</strong> ${escapeHtml(product.description || '—')}</p>
                <p><strong>Categoría:</strong> ${getCategoryName(product.category_id)}</p>
                <p><strong>Subcategoría:</strong> ${getSubcategoryName(product.subcategory_id)}</p>
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
            bodyEl.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Categoría <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" id="categorySelect" required>
                        <option value="">Selecciona una categoría</option>
                        ${generateCategoryOptions()}
                    </select>
                </div>
                <div class="mb-3" id="subcategoryContainer" style="display: none;">
                    <label class="form-label">Subcategoría (opcional)</label>
                    <select name="subcategory_id" class="form-select" id="subcategorySelect">
                        <option value="">Sin subcategoría</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">SKU <span class="text-danger">*</span></label>
                    <input type="text" name="sku" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Precio <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Costo (opcional)</label>
                    <input type="number" step="0.01" name="cost" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Stock <span class="text-danger">*</span></label>
                    <input type="number" name="stock" class="form-control" value="0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Stock Mínimo <span class="text-danger">*</span></label>
                    <input type="number" name="min_stock" class="form-control" value="0" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="featured" class="form-check-input" id="featuredCheck" value="1">
                    <label class="form-check-label" for="featuredCheck">Destacado</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Estado</label>
                    <select name="active" class="form-select">
                        <option value="1" selected>Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Producto</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/products');

            handleCategoryChangeNew();

        } else if (action === 'edit') {
            titleEl.textContent = 'Editar Producto';
            bodyEl.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="${escapeHtml(product.name)}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" class="form-control" rows="3">${escapeHtml(product.description || '')}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Categoría <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" id="categorySelectEdit" required>
                        <option value="">Selecciona una categoría</option>
                        ${generateCategoryOptions(product.category_id)}
                    </select>
                </div>
                <div class="mb-3" id="subcategoryContainerEdit" style="display: none;">
                    <label class="form-label">Subcategoría (opcional)</label>
                    <select name="subcategory_id" class="form-select" id="subcategorySelectEdit">
                        <option value="">Sin subcategoría</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">SKU <span class="text-danger">*</span></label>
                    <input type="text" name="sku" class="form-control" value="${escapeHtml(product.sku)}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Precio <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="price" class="form-control" value="${product.price}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Costo (opcional)</label>
                    <input type="number" step="0.01" name="cost" class="form-control" value="${product.cost || ''}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Stock <span class="text-danger">*</span></label>
                    <input type="number" name="stock" class="form-control" value="${product.stock}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Stock Mínimo <span class="text-danger">*</span></label>
                    <input type="number" name="min_stock" class="form-control" value="${product.min_stock}" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="featured" class="form-check-input" id="featuredCheckEdit" value="1" ${product.featured ? 'checked' : ''}>
                    <label class="form-check-label" for="featuredCheckEdit">Destacado</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Estado</label>
                    <select name="active" class="form-select">
                        <option value="1" ${product.active ? 'selected' : ''}>Activo</option>
                        <option value="0" ${!product.active ? 'selected' : ''}>Inactivo</option>
                    </select>
                </div>
                <input type="hidden" name="_method" value="PUT">
            `;
            footerEl.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            `;
            formEl.setAttribute('method', 'POST');
            formEl.setAttribute('action', '/admin/products/' + product.id);

            // Populate subcategories after render
            setTimeout(() => {
                const subcatSelect = document.getElementById('subcategorySelectEdit');
                const subcatContainer = document.getElementById('subcategoryContainerEdit');
                if (subcatSelect && subcatContainer) {
                    const filtered = allSubcategories.filter(s => s.category_id === product.category_id && s.active);
                    filtered.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.id;
                        opt.textContent = s.name;
                        if (product.subcategory_id === s.id) opt.selected = true;
                        subcatSelect.appendChild(opt);
                    });
                    if (filtered.length > 0) {
                        subcatContainer.style.display = 'block';
                    } else {
                        subcatContainer.style.display = 'none';
                    }
                }
            }, 0);

            handleCategoryChangeEdit();

        } else if (action === 'deactivate') {
            titleEl.textContent = 'Desactivar Producto';
            bodyEl.innerHTML = `
                <div class="text-center">
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

    function escapeHtml(text) {
        if (!text && text !== 0) return '';
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

    function getSubcategoryName(subcategoryId) {
        if (!subcategoryId) return '—';
        @if($subcategories && $subcategories->count() > 0)
            @foreach($subcategories as $subcategory)
                if (subcategoryId === '{{ $subcategory['id'] }}') {
                    return '{{ addslashes($subcategory['name']) }}';
                }
            @endforeach
        @endif
        return '—';
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

        // Remove subcategory name if container is hidden
        const subcatContainerNew = document.getElementById('subcategoryContainer');
        if (subcatContainerNew && subcatContainerNew.style.display === 'none') {
            const subcatSelect = document.getElementById('subcategorySelect');
            if (subcatSelect) subcatSelect.removeAttribute('name');
        }
        const subcatContainerEdit = document.getElementById('subcategoryContainerEdit');
        if (subcatContainerEdit && subcatContainerEdit.style.display === 'none') {
            const subcatSelect = document.getElementById('subcategorySelectEdit');
            if (subcatSelect) subcatSelect.removeAttribute('name');
        }

        const formData = new FormData(form);
        const categoryId = formData.get('category_id');
        const subcategoryId = formData.get('subcategory_id');

        // Validar que la subcategoría corresponda a la categoría
        if (subcategoryId && subcategoryId !== "") {
            const validSubcategories = allSubcategories.filter(s => s.category_id === categoryId && s.active);
            const isValid = validSubcategories.some(s => s.id === subcategoryId);
            if (!isValid) {
                alert('La subcategoría seleccionada no corresponde a la categoría elegida.');
                return;
            }
        }

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

    // Variables JS desde PHP
    const categories = @json($categories->toArray());
    let allSubcategories = @json($subcategories->toArray());

    function handleCategoryChangeNew() {
        const categorySelect = document.getElementById('categorySelect');
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                const categoryId = this.value;
                const subcatSelect = document.getElementById('subcategorySelect');
                const subcatContainer = document.getElementById('subcategoryContainer');
                if (subcatSelect && subcatContainer) {
                    subcatSelect.innerHTML = '<option value="">Sin subcategoría</option>';
                    if (categoryId) {
                        const filtered = allSubcategories.filter(s => s.category_id === categoryId && s.active);
                        filtered.forEach(s => {
                            const opt = document.createElement('option');
                            opt.value = s.id;
                            opt.textContent = s.name;
                            subcatSelect.appendChild(opt);
                        });
                        if (filtered.length > 0) {
                            subcatContainer.style.display = 'block';
                        } else {
                            subcatContainer.style.display = 'none';
                        }
                    } else {
                        subcatContainer.style.display = 'none';
                    }
                }
            });
        }
    }

    function handleCategoryChangeEdit() {
        const categorySelect = document.getElementById('categorySelectEdit');
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                const categoryId = this.value;
                const subcatSelect = document.getElementById('subcategorySelectEdit');
                const subcatContainer = document.getElementById('subcategoryContainerEdit');
                if (subcatSelect && subcatContainer) {
                    subcatSelect.innerHTML = '<option value="">Sin subcategoría</option>';
                    if (categoryId) {
                        const filtered = allSubcategories.filter(s => s.category_id === categoryId && s.active);
                        filtered.forEach(s => {
                            const opt = document.createElement('option');
                            opt.value = s.id;
                            opt.textContent = s.name;
                            subcatSelect.appendChild(opt);
                        });
                        if (filtered.length > 0) {
                            subcatContainer.style.display = 'block';
                        } else {
                            subcatContainer.style.display = 'none';
                        }
                        // Reset subcategory if not valid for new category
                        const currentSubcat = subcatSelect.value;
                        if (currentSubcat && !filtered.some(s => s.id === currentSubcat)) {
                            subcatSelect.value = '';
                        }
                    } else {
                        subcatContainer.style.display = 'none';
                    }
                }
            });
        }
    }

    // Botones de nuevo producto
    document.getElementById('btnNewProduct')?.addEventListener('click', function(e) {
        e.preventDefault();
        openModal('new', null);
    });
    document.getElementById('btnNewProductEmpty')?.addEventListener('click', function(e) {
        e.preventDefault();
        openModal('new', null);
    });
</script>
@endsection
