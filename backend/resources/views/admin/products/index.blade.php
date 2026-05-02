@extends('layouts.admin')

@section('title', 'Productos - MA Piscinas')

@section('content')
<div class="flex justify-between items-center mb-4">
    <h1 class="text-xl font-semibold text-dark"></h1>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">+ Nuevo Producto</a>
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
            <tr>
                <td colspan="6" class="text-center p-4 text-muted">
                    No hay productos aún.
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection