@extends('layouts.admin')

@section('title', 'Nuevo Cliente - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Nuevo Cliente</h1>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.clients.store') }}" class="p-4">
        @csrf

        @include('admin.clients._form_fields')

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Crear Cliente</button>
            <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection