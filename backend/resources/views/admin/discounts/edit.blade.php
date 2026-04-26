@extends('layouts.admin')

@section('title', 'Editar Descuento - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Editar Descuento</h1>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.discounts.update', $id) }}" class="p-4">
        @csrf
        @method('PUT')

        @include('admin.discounts._form_fields', ['item' => $item])

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="{{ route('admin.discounts.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection