@extends('layouts.admin')

@section('title', 'Detalle Categoría - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Detalle Categoría</h1>
    <div>
        <a href="{{ route('admin.categories.edit', $id) }}" class="btn btn-primary">Editar</a>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>

<div class="card">
    <div class="p-4">
        <dl class="row">
            <dt class="col-sm-3">Nombre:</dt>
            <dd class="col-sm-9">{{ $item['name'] ?? '-' }}</dd>

            <dt class="col-sm-3">Descripción:</dt>
            <dd class="col-sm-9">{{ $item['description'] ?? '-' }}</dd>

            <dt class="col-sm-3">Estado:</dt>
            <dd class="col-sm-9">
                @if(($item['active'] ?? true) === true || $item['active'] === 1)
                    <span class="badge bg-success">Activo</span>
                @else
                    <span class="badge bg-secondary">Inactivo</span>
                @endif
            </dd>
        </dl>
    </div>
</div>
@endsection