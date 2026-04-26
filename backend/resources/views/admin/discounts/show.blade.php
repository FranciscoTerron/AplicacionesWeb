@extends('layouts.admin')

@section('title', 'Detalle Descuento - MA Piscinas')

@section('content')
<div class="page-header">
    <h1>Detalle Descuento</h1>
    <div>
        <a href="{{ route('admin.discounts.edit', $id) }}" class="btn btn-primary">Editar</a>
        <a href="{{ route('admin.discounts.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</div>

<div class="card">
    <div class="p-4">
        <dl class="row">
            <dt class="col-sm-3">Código:</dt>
            <dd class="col-sm-9"><code>{{ $item['code'] ?? '-' }}</code></dd>

            <dt class="col-sm-3">Descripción:</dt>
            <dd class="col-sm-9">{{ $item['description'] ?? '-' }}</dd>

            <dt class="col-sm-3">Tipo:</dt>
            <dd class="col-sm-9">{{ ($item['discountType'] ?? '') === 'percentage' ? 'Porcentaje' : 'Fijo' }}</dd>

            <dt class="col-sm-3">Valor:</dt>
            <dd class="col-sm-9">
                @if(($item['discountType'] ?? '') === 'percentage')
                    {{ $item['discountValue'] ?? 0 }}%
                @else
                    ${{ number_format($item['discountValue'] ?? 0, 2, ',', '.') }}
                @endif
            </dd>

            <dt class="col-sm-3">Compra Mínima:</dt>
            <dd class="col-sm-9">${{ number_format($item['minPurchase'] ?? 0, 2, ',', '.') }}</dd>

            <dt class="col-sm-3">Válido Desde:</dt>
            <dd class="col-sm-9">{{ $item['validFrom'] ?? '-' }}</dd>

            <dt class="col-sm-3">Válido Hasta:</dt>
            <dd class="col-sm-9">{{ $item['validUntil'] ?? '-' }}</dd>

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