<!-- Activate User Modal -->
<div class="modal fade" id="activateModal{{ $user['id'] ?? '' }}" tabindex="-1" aria-labelledby="activateModalLabel{{ $user['id'] ?? '' }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activateModalLabel{{ $user['id'] ?? '' }}">Desbloquear Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro de desbloquear al usuario "<strong>{{ $user['name'] ?? '' }}</strong>"?<br>
                Este usuario podrá acceder al sistema nuevamente.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="activateForm{{ $user['id'] ?? '' }}" action="{{ route('admin.users.activate', $user['id']) }}" method="POST">
                    @csrf
                </form>
                <button type="submit" class="btn btn-success" form="activateForm{{ $user['id'] ?? '' }}">Desbloquear</button>
            </div>
        </div>
    </div>
</div>