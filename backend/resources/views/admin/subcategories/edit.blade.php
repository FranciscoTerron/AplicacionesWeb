@extends('layouts.admin')

@section('title', 'Editar Subcategoría - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Editar Subcategoría</h1>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.subcategories.update', $id) }}" class="p-4">
        @csrf
        @method('PUT')

        @include('admin.subcategories._form_fields', ['categories' => $categories])

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="{{ route('admin.subcategories.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection