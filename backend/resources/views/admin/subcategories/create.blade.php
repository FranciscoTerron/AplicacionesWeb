@extends('layouts.admin')

@section('title', 'Nueva Subcategoría - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Nueva Subcategoría</h1>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.subcategories.store') }}" class="p-4">
        @csrf

        @include('admin.subcategories._form_fields')

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Crear Subcategoría</button>
            <a href="{{ route('admin.subcategories.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection