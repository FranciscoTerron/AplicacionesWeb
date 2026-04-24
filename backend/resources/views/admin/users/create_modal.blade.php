<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">Crear Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    
                    @include('admin.components.form-field', [
                        'name' => 'name',
                        'label' => 'Nombre',
                        'type' => 'text',
                        'required' => true
                    ])
                    
                    @include('admin.components.form-field', [
                        'name' => 'email',
                        'label' => 'Email',
                        'type' => 'email',
                        'required' => true
                    ])
                    
                    @include('admin.components.form-field', [
                        'name' => 'password',
                        'label' => 'Contraseña',
                        'type' => 'password',
                        'required' => true
                    ])
                    
                    @include('admin.components.form-field', [
                        'name' => 'password_confirmation',
                        'label' => 'Confirmar Contraseña',
                        'type' => 'password'
                    ])
                    
@php
    $roleOptions = [
        'admin' => 'Administrador',
        'editor' => 'Editor'
    ];
@endphp
@include('admin.components.form-field', [
    'name' => 'role',
    'label' => 'Rol',
    'type' => 'select',
    'required' => true,
    'options' => $roleOptions,
    'selected' => old('role')
])
                    
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
