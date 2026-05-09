@extends('layouts.app')

@section('title', 'Catálogo — MA Piscinas')

@section('styles')
.catalog-hero {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 3rem 0 2.5rem;
}
.catalog-hero p.eyebrow {
    font-size: .78rem; letter-spacing: .08em; text-transform: uppercase;
    font-weight: 600;
    color: var(--primary); margin-bottom: .5rem;
}
.catalog-hero h1 {
    font-size: clamp(1.8rem, 3.5vw, 2.4rem); font-weight: 700;
    letter-spacing: -.015em; color: var(--text);
}

.filters {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1rem;
    margin: 1.5rem 0 2rem;
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: .75rem;
}
@media (max-width: 768px) { .filters { grid-template-columns: 1fr; } }
.filters input, .filters select {
    padding: .7rem .9rem; border: 1px solid var(--border);
    border-radius: 8px; font-family: inherit; font-size: .9rem;
    color: var(--text); background: #fff;
    transition: border-color .2s;
}
.filters input:focus, .filters select:focus {
    outline: none; border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(2,132,199,.15);
}
.filters button {
    padding: .7rem 1.3rem; background: var(--primary); color: #fff;
    border: 0; border-radius: 8px; font-weight: 500; font-size: .9rem; cursor: pointer;
    transition: background .2s;
}
.filters button:hover { background: var(--primary-dark); }

.catalog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1.25rem;
    padding-bottom: 3rem;
}
.cprod {
    background: #fff; border: 1px solid var(--border);
    border-radius: 12px; overflow: hidden;
    transition: border-color .2s, transform .2s, box-shadow .2s;
    display: flex; flex-direction: column;
}
.cprod:hover {
    border-color: var(--primary); transform: translateY(-2px);
    box-shadow: var(--shadow);
}
.cprod-img {
    aspect-ratio: 1 / 1;
    background: var(--surface) center/cover;
    border-bottom: 1px solid var(--border);
}
.cprod-body { padding: 1rem; flex: 1; display: flex; flex-direction: column; }
.cprod-name { font-size: .95rem; font-weight: 600; color: var(--text); margin-bottom: .25rem; line-height: 1.35; }
.cprod-sku { font-size: .72rem; color: var(--muted); margin-bottom: .6rem; }
.cprod-price {
    font-size: 1.25rem; font-weight: 700; color: var(--text); margin-top: auto;
}
.cprod-stock {
    font-size: .75rem; color: var(--success); font-weight: 500;
    margin-top: .35rem;
    display: inline-flex; align-items: center; gap: .3rem;
}
.cprod-stock.empty { color: var(--danger); }

.empty-state {
    text-align: center; padding: 4rem 0; color: var(--muted);
}
.empty-state h3 {
    color: var(--text); margin-bottom: .5rem; font-size: 1.3rem; font-weight: 600;
}
@endsection

@section('content')
<section class="catalog-hero">
    <div class="container">
        <p class="eyebrow">Tienda online</p>
        <h1>Catálogo de productos</h1>
    </div>
</section>

<div class="container">
    <form method="GET" action="{{ route('catalog.index') }}" class="filters">
        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Buscar por nombre, SKU o palabra clave...">
        <select name="category">
            <option value="">Todas las categorías</option>
            @foreach($categories as $cat)
                <option value="{{ $cat['id'] ?? '' }}" {{ ($categoryFilter ?? '') === ($cat['id'] ?? '') ? 'selected' : '' }}>
                    {{ $cat['name'] ?? '' }}
                </option>
            @endforeach
        </select>
        <button type="submit">Buscar</button>
    </form>

    @if($products->isEmpty())
        <div class="empty-state">
            <h3>Sin resultados</h3>
            <p>No encontramos productos que coincidan con tu búsqueda.</p>
        </div>
    @else
        <div class="catalog-grid">
            @foreach($products as $product)
                @php
                    $pid = $product['id'] ?? '';
                    $img = $product['main_image'] ?? null;
                    $stock = (int) ($product['stock'] ?? 0);
                @endphp
                <a href="{{ $pid ? route('catalog.show', $pid) : '#' }}" class="cprod">
                    <div class="cprod-img" @if($img) style="background-image:url('{{ $img }}');" @endif></div>
                    <div class="cprod-body">
                        <h3 class="cprod-name">{{ $product['name'] ?? '—' }}</h3>
                        <p class="cprod-sku">SKU · {{ $product['sku'] ?? '—' }}</p>
                        <p class="cprod-price">${{ number_format((float) ($product['price'] ?? 0), 2, ',', '.') }}</p>
                        @if($stock > 0)
                            <p class="cprod-stock">● En stock</p>
                        @else
                            <p class="cprod-stock empty">● Sin stock</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
