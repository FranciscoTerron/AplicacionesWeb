@php
    $user = $user ?? [];
    $userId = $user['id'] ?? '';
@endphp

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal{{ $userId }}" tabindex="-1" aria-labelledby="editUserModalLabel{{ $userId }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel{{ $userId }}">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.users.update', $userId) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    @include('admin.components.form-field', [
                        'name' => 'name',
                        'label' => 'Nombre',
                        'type' => 'text',
                        'value' => old('name', $user['name'] ?? ''),
                        'required' => true
                    ])
                    
                    @include('admin.components.form-field', [
                        'name' => 'email',
                        'label' => 'Email',
                        'type' => 'email',
                        'value' => old('email', $user['email'] ?? ''),
                        'required' => true
                    ])
                    
                    @include('admin.components.form-field', [
                        'name' => 'password',
                        'label' => 'Nueva Contraseña (opcional)',
                        'type' => 'password'
                    ])
                    
                    @include('admin.components.form-field', [
                        'name' => 'password_confirmation',
                        'label' => 'Confirmar Nueva Contraseña',
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
    'selected' => old('role', $user['role'] ?? '')
])
                    
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- No custom script needed for Bootstrap modal -->
