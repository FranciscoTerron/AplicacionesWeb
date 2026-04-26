@extends('layouts.admin')

@section('title', 'Editar Pedido - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Editar Pedido</h1>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.orders.update', $id) }}" class="p-4">
        @csrf
        @method('PUT')

        @include('admin.orders._form_fields', ['item' => $item, 'clients' => $clients ?? collect([]), 'products' => $products ?? collect([])])

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection