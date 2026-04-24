<!-- Show User Modal -->
<div class="modal fade" id="showUserModal{{ $user['id'] ?? '' }}" tabindex="-1" aria-labelledby="showUserModalLabel{{ $user['id'] ?? '' }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="showUserModalLabel{{ $user['id'] ?? '' }}">Detalles del Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <p class="form-control-plaintext">{{ $user['name'] ?? 'N/A' }}</p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <p class="form-control-plaintext">{{ $user['email'] ?? 'N/A' }}</p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Rol</label>
                    <p class="form-control-plaintext">
                        @if(isset($user['role']) && $user['role'] == 'admin')
                            Administrador
                        @else
                            Editor
                        @endif
                    </p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Fecha de Creación</label>
                    <p class="form-control-plaintext">{{ isset($user['created_at']) ? \Carbon\Carbon::parse($user['created_at'])->format('d/m/Y H:i') : 'N/A' }}</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>