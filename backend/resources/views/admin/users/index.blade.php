@extends('layouts.admin')

@section('title', 'Usuarios - MA Piscinas')

@section('styles')
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 1.5rem;
    color: var(--dark);
}

.btn {
    display: inline-block;
    padding: .5rem 1rem;
    border-radius: .375rem;
    text-decoration: none;
    font-size: .9rem;
    font-weight: 500;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background: var(--primary);
    color: var(--white);
}

.btn-primary:hover {
    background: var(--dark);
}

.card {
    background: var(--white);
    border-radius: .5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.1);
    overflow: hidden;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.table th {
    background: var(--bg-light);
    font-weight: 500;
    font-size: .85rem;
    color: #6c757d;
}

.table tr:hover {
    background: var(--bg-light);
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.badge {
    display: inline-block;
    padding: .25rem .5rem;
    font-size: .75rem;
    border-radius: .25rem;
    font-weight: 500;
}

.badge-admin {
    background: #e7f1ff;
    color: var(--primary);
}

.badge-empleado {
    background: #fff3cd;
    color: #856404;
}
@endsection

@section('content')
<div class="page-header">
    <h1></h1>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">+ Nuevo Usuario</a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="5" class="empty-state">
                    No hay usuarios aún.
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection