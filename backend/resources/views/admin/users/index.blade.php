@extends('layouts.admin')

@section('title', 'Usuarios - MA Piscinas')
@section('page-title', 'Usuarios')
@section('page-subtitle', 'Gestión de usuarios del sistema')

@section('styles')
/* No custom styles needed - using Bootstrap */
@endsection

@section('content')
@php
    $currentUser = Auth::user();
    $currentUserId = $currentUser?->getAuthIdentifier();
    $currentUserRole = $currentUser?->role ?? 'editor';
@endphp

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1>Usuarios</h1>
    @if($currentUser && $currentUserRole == 'admin')
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">+ Nuevo Usuario</button>
    @else
        <button type="button" class="btn btn-outline-danger btn-sm" disabled title="No puedes crear usuarios">Nuevo Usuario</button>
    @endif    
</div>

<!-- Search and Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o email..." value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-2">
                <select name="role" class="form-select">
                    <option value="">Todos los roles</option>
                    <option value="admin" {{ ($roleFilter ?? '') == 'admin' ? 'selected' : '' }}>Administrador</option>
                    <option value="editor" {{ ($roleFilter ?? '') == 'editor' ? 'selected' : '' }}>Editor</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="active" {{ ($statusFilter ?? '') == 'active' ? 'selected' : '' }}>Activo</option>
                    <option value="inactive" {{ ($statusFilter ?? '') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                @if($search || $roleFilter || $statusFilter)
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary w-100 mt-1">Limpiar</a>
                @endif
            </div>
        </form>
    </div>
</div>

@include('admin.users.create_modal')

<div class="card">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user['name'] ?? 'N/A' }}</td>
                    <td>{{ $user['email'] ?? 'N/A' }}</td>
                    <td>
                        @if(isset($user['role']) && $user['role'] == 'admin')
                            <span class="badge bg-primary">Administrador</span>
                        @else
                            <span class="badge bg-warning text-dark">Editor</span>
                        @endif
                    </td>
<td class="actions">
    <!-- Ver button - always visible (subject to permission checks in controller) -->
    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#showUserModal{{ $user['id'] }}">Ver</button>
    
    @php
        // Check if current user is admin
        $isAdmin = Auth::check() && Auth::user()->role === 'admin';
        // Check if current user is trying to access their own record
        $isOwnProfile = Auth::check() && Auth::user()->getAuthIdentifier() == ($user['id'] ?? '');
        // Check if target user is active
        $isActive = $user['active'] ?? true;
    @endphp
    
    <!-- Edit button logic -->
    @if($isAdmin || $isOwnProfile)
        <!-- Admins can edit anyone, users can edit themselves -->
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user['id'] }}">Editar</button>
    @else
        <!-- Others cannot edit -->
        <button type="button" class="btn btn-outline-danger btn-sm" disabled title="No tienes permisos para editar">Editar</button>
    @endif
    
    <!-- Delete/Deactivate button logic -->
    @if($isAdmin)
        <!-- Admins can deactivate anyone -->
        @if($isActive)
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $user['id'] }}">Bloquear</button>
        @else
            <!-- If inactive, show activate button instead -->
            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#activateModal{{ $user['id'] }}">Desbloquear</button>
        @endif
    @elseif($isOwnProfile)
        <!-- Users can only deactivate/activate themselves -->
        @if($isActive)
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $user['id'] }}">Bloquear</button>
        @else
            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#activateModal{{ $user['id'] }}">Desbloquear</button>
        @endif
    @else
        <!-- Regular users (editors) cannot touch others -->
        @if($isActive)
            <button type="button" class="btn btn-outline-danger btn-sm" disabled title="Solo los administradores pueden bloquear usuarios">Bloquear</button>
        @else
            <button type="button" class="btn btn-outline-success btn-sm" disabled title="Solo los administradores pueden desbloquear usuarios">Desbloquear</button>
        @endif
    @endif
</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty-state">
                        No hay usuarios aún.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if(isset($hasMore) && ($hasMore || $page > 1))
<div class="d-flex justify-content-between align-items-center mt-3">
    <div>
        @if($page > 1)
            <a href="{{ route('admin.users.index', array_merge(['page' => $page - 1, 'after' => request('after_prev')], array_filter(['search' => $search ?? '', 'role' => $roleFilter ?? '', 'status' => $statusFilter ?? '']))) }}" 
               class="btn btn-outline-primary btn-sm">← Anterior</a>
        @endif
    </div>
    <div>
        Página {{ $page ?? 1 }}
    </div>
    <div>
        @if($hasMore ?? false)
            <a href="{{ route('admin.users.index', array_merge(['page' => ($page ?? 1) + 1, 'after' => $lastDocumentId ?? ''], array_filter(['search' => $search ?? '', 'role' => $roleFilter ?? '', 'status' => $statusFilter ?? '']))) }}" 
               class="btn btn-outline-primary btn-sm">Siguiente →</a>
        @endif
    </div>
</div>
@endif

@foreach($users as $user)
    @include('admin.users.edit_modal', ['user' => $user])
    @include('admin.users.show_modal', ['user' => $user])
    @include('admin.users.delete_modal', ['user' => $user])
    @include('admin.users.activate_modal', ['user' => $user])
@endforeach
@endsection

@section('scripts')
<script>
function confirmDelete(event, userName) {
    event.preventDefault();
    if (confirm('¿Está seguro de bloquear al usuario "' + userName + '"? Esta acción evitará que el usuario acceda al sistema.')) {
        event.target.closest('form').submit();
    }
}
</script>
@endsection
