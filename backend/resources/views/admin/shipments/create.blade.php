@extends('layouts.admin')

@section('title', 'Nuevo Envío - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Nuevo Envío</h1>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.shipments.store') }}" class="p-4">
        @csrf

        @include('admin.shipments._form_fields')

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Crear Envío</button>
            <a href="{{ route('admin.shipments.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
