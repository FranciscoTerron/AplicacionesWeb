@extends('layouts.admin')

@section('title', 'Dashboard - MA Piscinas')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Resumen general del sistema')

@section('content')
@php
    $statusLabels = \App\Support\OrderStatus::statuses();
    $statusBadgeMap = [
        'pending' => 'badge-amber',
        'confirmed' => 'badge-sky',
        'processing' => 'badge-indigo',
        'shipped' => 'badge-sky',
        'delivered' => 'badge-emerald',
        'cancelled' => 'badge-rose',
    ];
@endphp

<style>
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .kpi-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        padding: 1.5rem;
        box-shadow: var(--shadow-card);
        border-left: 3px solid var(--border);
        transition: transform .15s, border-color .15s;
    }
    .kpi-card:hover { transform: translateY(-2px); }
    .kpi-card.kpi-success { border-left-color: var(--success); }
    .kpi-card.kpi-info { border-left-color: var(--primary); }
    .kpi-card.kpi-default { border-left-color: #cbd5e1; }
    .kpi-label {
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.6rem;
    }
    .kpi-value {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--text);
        letter-spacing: -0.02em;
        line-height: 1.1;
    }
    .kpi-sub {
        font-size: 0.78rem;
        color: var(--text-muted);
        margin-top: 0.4rem;
    }

    .panel-grid {
        display: grid;
        grid-template-columns: 1.4fr 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 992px) { .panel-grid { grid-template-columns: 1fr; } }

    .panel {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        padding: 1.25rem;
        box-shadow: var(--shadow-card);
    }
    .panel-title {
        font-size: 0.92rem;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 1rem;
    }

    /* Pedidos por estado: barras horizontales */
    .status-row {
        display: flex; align-items: center; gap: 0.75rem;
        padding: 0.4rem 0;
        font-size: 0.85rem;
    }
    .status-row .status-name { width: 100px; color: var(--text-soft); }
    .status-row .status-bar {
        flex: 1; height: 6px; background: var(--bg-light);
        border-radius: 999px; overflow: hidden;
    }
    .status-row .status-fill {
        height: 100%; border-radius: 999px;
        background: var(--primary);
    }
    .status-row .status-count { font-weight: 600; color: var(--text); width: 30px; text-align: right; }

    .panel-big {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text);
        letter-spacing: -0.02em;
        margin: 0.4rem 0 0.6rem;
    }
    .panel-link {
        display: inline-flex; align-items: center; gap: 0.3rem;
        color: var(--primary); font-size: 0.85rem; font-weight: 500;
        text-decoration: none;
    }
    .panel-link:hover { color: var(--primary-dark); }

    /* Accesos rápidos: list-actions */
    .quick-actions { display: flex; flex-direction: column; gap: 0.4rem; }
    .quick-action {
        display: flex; align-items: center; gap: 0.7rem;
        padding: 0.6rem 0.8rem;
        background: var(--bg-light);
        border: 1px solid transparent;
        border-radius: 8px;
        color: var(--text);
        font-size: 0.88rem; font-weight: 500;
        text-decoration: none;
        transition: all 0.15s;
    }
    .quick-action:hover {
        background: #fff;
        border-color: var(--primary);
        color: var(--primary-dark);
    }
    .quick-action svg { color: var(--primary); flex-shrink: 0; }
    .quick-action .qa-arrow { margin-left: auto; opacity: 0.4; }

    /* Tabla pedidos recientes */
    .orders-table {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        overflow: hidden;
        box-shadow: var(--shadow-card);
    }
    .orders-table .orders-head {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
    }
    .orders-table table { width: 100%; border-collapse: collapse; }
    .orders-table th {
        text-align: left;
        padding: 0.75rem 1.25rem;
        background: var(--bg-light);
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid var(--border);
    }
    .orders-table td {
        padding: 0.85rem 1.25rem;
        font-size: 0.88rem;
        border-bottom: 1px solid var(--border);
        color: var(--text-soft);
    }
    .orders-table tr:last-child td { border-bottom: 0; }
    .orders-table .empty-row { text-align: center; padding: 2.5rem; color: var(--text-muted); font-size: 0.9rem; }

    /* Status badges */
    .status-badge {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
    }
    .badge-amber { background: #fef3c7; color: #92400e; }
    .badge-sky { background: #e0f2fe; color: #075985; }
    .badge-indigo { background: #e0e7ff; color: #3730a3; }
    .badge-emerald { background: #d1fae5; color: #065f46; }
    .badge-rose { background: #fee2e2; color: #991b1b; }
</style>

{{-- KPIs --}}
<div class="kpi-grid">
    <div class="kpi-card kpi-success">
        <div class="kpi-label">Ventas cobradas</div>
        <div class="kpi-value">${{ number_format((float) $kpis['monthlyRevenue'], 2, ',', '.') }}</div>
        <div class="kpi-sub">Pedidos completados o pagados</div>
    </div>

    <div class="kpi-card kpi-info">
        <div class="kpi-label">Pedidos totales</div>
        <div class="kpi-value">{{ $kpis['totalOrders'] }}</div>
        <div class="kpi-sub">Todos los estados</div>
    </div>

    <div class="kpi-card kpi-default">
        <div class="kpi-label">Clientes activos</div>
        <div class="kpi-value">{{ $kpis['activeClients'] }}</div>
        <div class="kpi-sub">Registrados en el sistema</div>
    </div>

    <div class="kpi-card kpi-default">
        <div class="kpi-label">Productos activos</div>
        <div class="kpi-value">{{ $kpis['activeProducts'] }}</div>
        <div class="kpi-sub">Disponibles para vender</div>
    </div>
</div>

{{-- 3-col panels --}}
<div class="panel-grid">
    <div class="panel">
        <h3 class="panel-title">Pedidos por estado</h3>
        @if($ordersByStatus->isEmpty())
            <p style="color:var(--text-muted);font-size:0.88rem;">Aún no hay pedidos.</p>
        @else
            @php $maxCount = $ordersByStatus->max() ?: 1; @endphp
            @foreach($ordersByStatus as $status => $count)
                @php
                    $label = $statusLabels[$status] ?? $status;
                    $pct = ($count / $maxCount) * 100;
                @endphp
                <div class="status-row">
                    <span class="status-name">{{ $label }}</span>
                    <div class="status-bar">
                        <div class="status-fill" style="width:{{ $pct }}%;"></div>
                    </div>
                    <span class="status-count">{{ $count }}</span>
                </div>
            @endforeach
        @endif
    </div>

    <div class="panel">
        <h3 class="panel-title">Envíos en tránsito</h3>
        <p class="panel-big">{{ $kpis['shipmentsInTransit'] }}</p>
        <a href="{{ route('admin.shipments.index') }}?status=in_transit" class="panel-link">
            Ver envíos
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </a>
    </div>

    <div class="panel">
        <h3 class="panel-title">Accesos rápidos</h3>
        <div class="quick-actions">
            <a href="{{ route('admin.products.create') }}" class="quick-action">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V6h16v12z"/><path d="M11 8h2v8h-2zm-3 3h8v2h-8z"/></svg>
                Nuevo producto
                <svg class="qa-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </a>
            @if (auth()->user()->role === 'editor')
                <a href="{{ route('admin.categories.create') }}" class="quick-action">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V6h16v12z"/><path d="M11 8h2v8h-2zm-3 3h8v2h-8z"/></svg>
                    Nueva categoría
                    <svg class="qa-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </a>
                <a href="{{ route('admin.subcategories.create') }}" class="quick-action">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V6h16v12z"/><path d="M11 8h2v8h-2zm-3 3h8v2h-8z"/></svg>
                    Nueva subcategoría
                    <svg class="qa-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            @else
                <a href="{{ route('admin.orders.create') }}" class="quick-action">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 11h-4v4h-2v-4H7v-2h4V8h2v4h4v2z"/></svg>
                    Nuevo pedido
                    <svg class="qa-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </a>
                <a href="{{ route('admin.shipments.create') }}" class="quick-action">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h13l4 5v5h-2a2 2 0 1 1-4 0H9a2 2 0 1 1-4 0H3V7z"/></svg>
                    Nuevo envío
                    <svg class="qa-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            @endif
        </div>
    </div>
</div>

{{-- Pedidos recientes --}}
<div class="orders-table">
    <div class="orders-head">
        <h3 class="panel-title" style="margin:0;">Pedidos recientes</h3>
        <a href="{{ route('admin.orders.index') }}" class="panel-link">Ver todos →</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentOrders as $order)
                @php
                    $statusKey = $order['status'] ?? 'pending';
                    $statusLabel = $statusLabels[$statusKey] ?? $statusKey;
                    $badgeClass = $statusBadgeMap[$statusKey] ?? 'badge-sky';
                    $total = $order['total_amount'] ?? $order['total'] ?? 0;
                @endphp
                <tr>
                    <td><code style="background:var(--bg-light);padding:0.15rem 0.4rem;border-radius:4px;font-size:0.78rem;">{{ \Illuminate\Support\Str::limit($order['id'] ?? '—', 10) }}</code></td>
                    <td>{{ $order['client_name'] ?? $order['clientId'] ?? $order['client_id'] ?? '—' }}</td>
                    <td style="font-weight:600;color:var(--text);">${{ number_format((float) $total, 2, ',', '.') }}</td>
                    <td><span class="status-badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                    <td>{{ isset($order['created_at']) ? \Illuminate\Support\Str::of($order['created_at'])->substr(0, 10) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-row">No hay pedidos aún.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
