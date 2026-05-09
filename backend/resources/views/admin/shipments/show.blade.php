@extends('layouts.admin')

@section('title', 'Detalle de Envío - MA Piscinas')

@section('content')
@php
    $statuses = \App\Models\Shipment::statuses();
    $statusLabel = $statuses[$item['status'] ?? ''] ?? '—';
@endphp

<div class="page-header d-flex justify-content-between align-items-center">
    <h1>Detalle del Envío</h1>
    <a href="{{ route('admin.shipments.index') }}" class="btn btn-outline-secondary">← Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Orden asociada</dt>
            <dd class="col-sm-9"><code>{{ $item['order_id'] ?? '—' }}</code></dd>

            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><span class="badge bg-info">{{ $statusLabel }}</span></dd>

            <dt class="col-sm-3">Dirección</dt>
            <dd class="col-sm-9">{{ $item['address'] ?? '—' }}</dd>

            <dt class="col-sm-3">Transportista</dt>
            <dd class="col-sm-9">{{ $item['carrier'] ?? '—' }}</dd>

            <dt class="col-sm-3">Código de seguimiento</dt>
            <dd class="col-sm-9"><code>{{ $item['tracking_code'] ?? '—' }}</code></dd>

            <dt class="col-sm-3">Fecha de despacho</dt>
            <dd class="col-sm-9">{{ $item['shipped_at'] ?? '—' }}</dd>

            <dt class="col-sm-3">Fecha de entrega</dt>
            <dd class="col-sm-9">{{ $item['delivered_at'] ?? '—' }}</dd>

            <dt class="col-sm-3">Notas</dt>
            <dd class="col-sm-9">{{ $item['notes'] ?? '—' }}</dd>
        </dl>

        <div class="mt-3">
            <a href="{{ route('admin.shipments.edit', $id) }}" class="btn btn-primary">Editar</a>
        </div>
    </div>
</div>
@endsection
