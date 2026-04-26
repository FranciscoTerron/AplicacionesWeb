@extends('layouts.admin')

@section('title', 'Editar Producto - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Editar Producto</h1>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.products.update', $id) }}" class="p-4">
        @csrf
        @method('PUT')

        @include('admin.products._form_fields', ['item' => $item])

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection