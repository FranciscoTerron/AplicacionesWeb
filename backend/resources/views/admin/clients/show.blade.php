@extends('layouts.admin')

@section('title', 'Detalle Cliente - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Detalle Cliente</h1>
    <div>
        <a href="{{ route('admin.clients.edit', $id) }}" class="btn btn-primary">Editar</a>
        <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>

<div class="card">
    <div class="p-4">
        <dl class="row">
            <dt class="col-sm-3">Nombre:</dt>
            <dd class="col-sm-9">{{ $item['name'] ?? '-' }}</dd>

            <dt class="col-sm-3">Correo:</dt>
            <dd class="col-sm-9">{{ $item['email'] ?? '-' }}</dd>

            <dt class="col-sm-3">Teléfono:</dt>
            <dd class="col-sm-9">{{ $item['phone'] ?? '-' }}</dd>

            <dt class="col-sm-3">Dirección:</dt>
            <dd class="col-sm-9">{{ $item['address'] ?? '-' }}</dd>

            <dt class="col-sm-3">Ciudad:</dt>
            <dd class="col-sm-9">{{ $item['city'] ?? '-' }}</dd>

            <dt class="col-sm-3">Notas:</dt>
            <dd class="col-sm-9">{{ $item['notes'] ?? '-' }}</dd>

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