@extends('layouts.admin')

@section('title', 'Editar Envío - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Editar Envío</h1>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.shipments.update', $id) }}" class="p-4">
        @csrf
        @method('PUT')

        @include('admin.shipments._form_fields')

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="{{ route('admin.shipments.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
