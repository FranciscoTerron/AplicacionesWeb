@extends('layouts.admin')

@section('title', 'Nuevo Producto - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Nuevo Producto</h1>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.products.store') }}" class="p-4">
        @csrf

        @include('admin.products._form_fields')

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Crear Producto</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection