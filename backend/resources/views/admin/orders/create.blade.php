@extends('layouts.admin')

@section('title', 'Nuevo Pedido - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Nuevo Pedido</h1>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.orders.store') }}" class="p-4">
        @csrf

        @include('admin.orders._form_fields')

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Crear Pedido</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection