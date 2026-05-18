@extends('layouts.app')

@section('title', ($product['name'] ?? 'Producto') . ' — MA Piscinas')

@section('styles')
.detail { padding: 3rem 0; }
.detail-back {
    display: inline-flex; align-items: center; gap: .4rem;
    color: var(--muted); font-size: .9rem; margin-bottom: 2rem;
}
.detail-back:hover { color: var(--primary); }

.detail-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;
}
@media (max-width: 768px) { .detail-grid { grid-template-columns: 1fr; } }

.detail-img {
    aspect-ratio: 1 / 1;
    background: var(--surface) center/cover;
    border: 1px solid var(--border);
    border-radius: 16px;
}

.detail-info .sku {
    font-size: .78rem; color: var(--muted); letter-spacing: .04em;
    text-transform: uppercase; margin-bottom: .75rem;
}
.detail-info h1 {
    font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 700;
    letter-spacing: -.015em;
    color: var(--text); margin-bottom: 1rem;
}
.stock-badge {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .35rem .8rem;
    background: rgba(16,185,129,.1);
    color: var(--success);
    border-radius: 999px;
    font-size: .78rem; font-weight: 600;
    margin-bottom: 1.5rem;
}
.stock-badge.empty { background: rgba(220,38,38,.08); color: var(--danger); }

.price {
    font-size: 2.5rem; font-weight: 700; color: var(--text);
    letter-spacing: -.02em;
    margin: 0 0 1.5rem;
}
.description { color: var(--text-soft); line-height: 1.7; margin-bottom: 2rem; font-size: .98rem; }

.meta {
    border-top: 1px solid var(--border);
    padding-top: 1.5rem; margin-top: 1.5rem;
    display: grid; gap: .5rem;
}
.meta-row { display: flex; justify-content: space-between; padding: .35rem 0; font-size: .9rem; }
.meta-row span:first-child { color: var(--muted); }
.meta-row span:last-child { color: var(--text); font-weight: 500; }
@endsection

@section('content')
<div class="container detail">
    <a href="{{ route('catalog.index') }}" class="detail-back">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Volver al catálogo
    </a>

    <div class="detail-grid">
        <div class="detail-img" @if(!empty($product['main_image'])) style="background-image:url('{{ $product['main_image'] }}');" @endif></div>

        <div class="detail-info">
            <p class="sku">SKU · {{ $product['sku'] ?? '—' }}</p>
            <h1>{{ $product['name'] ?? 'Producto' }}</h1>

            @php $stock = (int) ($product['stock'] ?? 0); @endphp
            @if($stock > 0)
                <span class="stock-badge">● En stock — {{ $stock }} disponibles</span>
            @else
                <span class="stock-badge empty">● Sin stock</span>
            @endif

            <p class="price">${{ number_format((float) ($product['price'] ?? 0), 2, ',', '.') }}</p>

            <p class="description">{{ $product['description'] ?? 'Sin descripción.' }}</p>

            <div class="meta">
                @if($category)
                    <div class="meta-row"><span>Categoría</span><span>{{ $category['name'] ?? '—' }}</span></div>
                @endif
                @if(!empty($product['dimensions']))
                    @php $d = $product['dimensions']; @endphp
                    @if(isset($d['weight_kg']))
                        <div class="meta-row"><span>Peso</span><span>{{ $d['weight_kg'] }} kg</span></div>
                    @endif
                    @if(isset($d['length_cm']))
                        <div class="meta-row"><span>Dimensiones</span><span>{{ $d['length_cm'] }} × {{ $d['width_cm'] ?? '?' }} × {{ $d['height_cm'] ?? '?' }} cm</span></div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
