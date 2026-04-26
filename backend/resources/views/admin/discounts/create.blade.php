@extends('layouts.admin')

@section('title', 'Nuevo Descuento - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Nuevo Descuento</h1>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.discounts.store') }}" class="p-4">
        @csrf

        @include('admin.discounts._form_fields')

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Crear Descuento</button>
            <a href="{{ route('admin.discounts.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection