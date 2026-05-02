@extends('layouts.admin')

@section('title', 'Categorías - MA Piscinas')

@section('content')
<div class="flex justify-between items-center mb-4">
    <h1 class="text-xl font-semibold text-dark"></h1>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">+ Nueva Categoría</a>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Slug</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="4" class="text-center p-4 text-muted">
                    No hay categorías aún. Crea la primera.
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection