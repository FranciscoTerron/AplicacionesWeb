@php
    $variant = $variant ?? 'default';
    $icon = $icon ?? null;
    $trend = $trend ?? null;
    $trendUp = $trendUp ?? true;
@endphp

<div class="stat-card stat-card--{{ $variant }} bg-white rounded-lg p-6 shadow-sm border-l-4 border-primary transition-transform hover:-translate-y-1 hover:shadow-md">
    <div class="flex justify-between items-start mb-3">
        <span class="text-sm text-muted font-medium">{{ $label }}</span>
        @if($icon)
            <div class="w-9 h-9 rounded-lg bg-light flex items-center justify-center text-primary">
                {{ $icon }}
            </div>
        @endif
    </div>
    <div class="text-2xl font-bold text-dark mb-2">{{ $value }}</div>
    @if($trend)
        <div class="flex items-center gap-1 text-sm mt-2 {{ $trendUp ? 'text-success' : 'text-danger' }}">
            <span class="text-lg">{{ $trendUp ? '↑' : '↓' }}</span>
            <span>{{ $trend }}</span>
        </div>
    @endif
</div>