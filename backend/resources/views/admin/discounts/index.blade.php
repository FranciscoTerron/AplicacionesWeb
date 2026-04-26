@extends('layouts.admin')

@section('title', 'Descuentos - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Descuentos</h1>
    <a href="{{ route('admin.discounts.create') }}" class="btn btn-primary">+ Nuevo Descuento</a>
</div>

<div class="card">
    @if($items->isEmpty())
        <div class="empty-state">
            No hay descuentos aún. Crea el primero.
        </div>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Válido Desde</th>
                    <th>Válido Hasta</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $discount)
                <tr>
                    <td><strong>{{ $discount['name'] ?? $discount['code'] ?? '-' }}</strong></td>
                    <td>{{ ($discount['discountType'] ?? '') === 'percentage' ? '%' : '$' }}</td>
                    <td>{{ $discount['discountValue'] ?? 0 }}</td>
                    <td>{{ $discount['validFrom'] ?? '-' }}</td>
                    <td>{{ $discount['validUntil'] ?? '-' }}</td>
                    <td>
                        @if(($discount['active'] ?? true) === true || $discount['active'] === 1)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-secondary">Inactivo</span>
                        @endif
                    </td>
                    <td>
                        <a href="#">Ver</a> |
                        <a href="#">Editar</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection