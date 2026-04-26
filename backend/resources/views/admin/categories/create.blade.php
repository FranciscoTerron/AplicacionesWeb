@extends('layouts.admin')

@section('title', 'Nueva Categoría - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Nueva Categoría</h1>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.categories.store') }}" class="p-4">
        @csrf

        @include('admin.categories._form_fields')

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Crear Categoría</button>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection