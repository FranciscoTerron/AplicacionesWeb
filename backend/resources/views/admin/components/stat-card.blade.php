@php
    $variant = $variant ?? 'default';
    $icon = $icon ?? null;
    $trend = $trend ?? null;
    $trendUp = $trendUp ?? true;
@endphp

<div class="stat-card stat-card--{{ $variant }}">
    <div class="stat-card__header">
        <span class="stat-card__label">{{ $label }}</span>
        @if($icon)
            <div class="stat-card__icon">
                {{ $icon }}
            </div>
        @endif
    </div>
    <div class="stat-card__value">{{ $value }}</div>
    @if($trend)
        <div class="stat-card__trend stat-card__trend--{{ $trendUp ? 'up' : 'down' }}">
            <span class="stat-card__trend-icon">{{ $trendUp ? '↑' : '↓' }}</span>
            <span>{{ $trend }}</span>
        </div>
    @endif
</div>

<style>
.stat-card {
    background: var(--white);
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border-left: 4px solid var(--primary);
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stat-card--success { border-left-color: var(--success); }
.stat-card--warning { border-left-color: var(--warning); }
.stat-card--danger { border-left-color: var(--danger); }
.stat-card--info { border-left-color: var(--primary-light); }

.stat-card__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.stat-card__label {
    font-size: 0.85rem;
    color: var(--text-muted);
    font-weight: 500;
}

.stat-card__icon {
    width: 36px;
    height: 36px;
    border-radius: 0.5rem;
    background: var(--bg-light);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
}

.stat-card__value {
    font-size: 2rem;
    font-weight: 600;
    color: var(--dark);
    line-height: 1;
}

.stat-card__trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    margin-top: 0.75rem;
}

.stat-card__trend--up { color: var(--success); }
.stat-card__trend--down { color: var(--danger); }

.stat-card__trend-icon {
    font-size: 0.9rem;
}
</style>