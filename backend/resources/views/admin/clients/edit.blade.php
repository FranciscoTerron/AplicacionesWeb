@extends('layouts.admin')

@section('title', 'Editar Cliente - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Editar Cliente</h1>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.clients.update', $id) }}" class="p-4">
        @csrf
        @method('PUT')

        @include('admin.clients._form_fields', ['item' => $item])

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection