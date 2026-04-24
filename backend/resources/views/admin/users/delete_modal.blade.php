<!-- Delete User Modal -->
<div class="modal fade" id="deleteModal{{ $user['id'] ?? '' }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $user['id'] ?? '' }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel{{ $user['id'] ?? '' }}">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro de eliminar al usuario "<strong>{{ $user['name'] ?? '' }}</strong>"?<br>
                Esta acción marcará al usuario como inactivo y no podrá acceder al sistema.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm{{ $user['id'] ?? '' }}" action="{{ route('admin.users.destroy', $user['id']) }}" method="POST">
                    @csrf
                    @method('DELETE')
                </form>
                <button type="button" class="btn btn-danger" form="deleteForm{{ $user['id'] ?? '' }}">Eliminar</button>
            </div>
        </div>
    </div>
</div>