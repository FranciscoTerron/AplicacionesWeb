@extends('layouts.app')

@section('title', 'MA Piscinas — Tu piscina, nuestra pasión')

@section('styles')
/* ────── HERO ────── */
.hero {
    padding: 6rem 0 5rem;
    border-bottom: 1px solid var(--border);
}
.hero-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 4rem;
    align-items: center;
}
@media (max-width: 900px) { .hero-grid { grid-template-columns: 1fr; gap: 2.5rem; } }

.hero h1 {
    font-size: clamp(2rem, 4.2vw, 3.2rem);
    font-weight: 600; line-height: 1.15;
    letter-spacing: -.02em;
    color: var(--text);
    margin-bottom: 1.2rem;
}
.hero-lead {
    font-size: 1rem; line-height: 1.7;
    color: var(--text-soft); max-width: 480px; margin-bottom: 2rem;
}

.hero-search {
    display: flex; gap: .4rem;
    background: var(--surface);
    padding: .35rem;
    border-radius: 10px;
    max-width: 480px;
    margin-bottom: 1.5rem;
}
.hero-search input {
    flex: 1;
    border: 0; background: transparent;
    padding: .65rem .9rem;
    font-family: inherit; font-size: .92rem; color: var(--text);
    outline: none;
}
.hero-search input::placeholder { color: var(--muted); }
.hero-search button {
    background: var(--text); color: #fff;
    border: 0; border-radius: 7px;
    padding: .65rem 1.1rem;
    font-size: .88rem; font-weight: 500; cursor: pointer;
    transition: background .15s;
}
.hero-search button:hover { background: var(--primary); }

.hero-cta { display: flex; gap: .8rem; flex-wrap: wrap; }

.hero-image {
    aspect-ratio: 4 / 5;
    border-radius: 12px;
    background: url('https://images.unsplash.com/photo-1572331165267-854da2b10ccc?auto=format&fit=crop&w=900&q=80') center/cover;
}

/* ────── BENEFICIOS ────── */
.benefits { padding: 3rem 0; border-bottom: 1px solid var(--border); }
.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 2rem;
}
.benefit { display: flex; align-items: flex-start; gap: .8rem; }
.benefit-icon { color: var(--primary); flex-shrink: 0; margin-top: 2px; }
.benefit-text strong { display: block; font-size: .92rem; color: var(--text); font-weight: 500; margin-bottom: .15rem; }
.benefit-text span { font-size: .82rem; color: var(--muted); }

/* ────── SECCIONES ────── */
.section { padding: 5rem 0; }
.section-head {
    margin-bottom: 2.5rem;
}
.section-head .eyebrow {
    font-size: .78rem; font-weight: 500;
    color: var(--muted); margin-bottom: .35rem;
}
.section-head h2 {
    font-size: clamp(1.5rem, 2.8vw, 2rem);
    font-weight: 600; color: var(--text);
    line-height: 1.2;
    letter-spacing: -.015em;
}
.section-head .head-row {
    display: flex; justify-content: space-between; align-items: end; gap: 2rem; flex-wrap: wrap;
}
.section-head .link {
    color: var(--muted); font-size: .88rem;
}
.section-head .link:hover { color: var(--text); }

/* ────── CATEGORÍAS ────── */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}
.cat-card {
    aspect-ratio: 3 / 4;
    border-radius: 10px;
    overflow: hidden;
    background: var(--surface);
    border: 1px solid var(--border);
    display: flex; align-items: end;
    padding: 1.1rem;
    color: var(--text);
    transition: border-color .2s, transform .2s;
}
.cat-card:hover { border-color: var(--text); transform: translateY(-2px); }
.cat-card .cat-name {
    font-size: 1rem; font-weight: 500;
}
.cat-card .cat-arrow {
    margin-left: auto; opacity: .5;
}

/* ────── PRODUCTOS ────── */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}
.prod {
    background: #fff; border: 1px solid var(--border);
    border-radius: 10px; overflow: hidden;
    transition: border-color .2s, transform .2s;
    display: flex; flex-direction: column;
}
.prod:hover { border-color: var(--text); transform: translateY(-2px); }
.prod-img {
    aspect-ratio: 1 / 1;
    background: var(--surface) center/cover;
    border-bottom: 1px solid var(--border);
}
.prod-body { padding: 1rem; flex: 1; display: flex; flex-direction: column; }
.prod-name {
    font-size: .92rem; font-weight: 500; color: var(--text);
    margin-bottom: .2rem; line-height: 1.4;
}
.prod-sku { font-size: .72rem; color: var(--muted); margin-bottom: .8rem; }
.prod-price {
    font-size: 1.1rem; font-weight: 600; color: var(--text);
    margin-top: auto;
}

/* ────── PROCESO ────── */
.process { border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
.process-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 2.5rem;
}
.process-step {}
.process-num {
    font-size: .8rem; color: var(--muted);
    margin-bottom: .8rem;
    letter-spacing: .05em;
}
.process-step h3 { font-size: 1rem; font-weight: 500; color: var(--text); margin-bottom: .35rem; }
.process-step p { font-size: .88rem; color: var(--muted); line-height: 1.6; }

/* ────── FAQ ────── */
.faq-list { max-width: 720px; }
.faq-item {
    border-bottom: 1px solid var(--border);
}
.faq-item summary {
    list-style: none;
    padding: 1.2rem 0;
    cursor: pointer;
    display: flex; align-items: center; justify-content: space-between;
    font-weight: 500; color: var(--text);
    font-size: .95rem;
}
.faq-item summary::-webkit-details-marker { display: none; }
.faq-item summary::after {
    content: '+';
    color: var(--muted); font-size: 1.2rem;
    transition: transform .2s;
}
.faq-item[open] summary::after { content: '−'; }
.faq-item .faq-body {
    padding: 0 0 1.2rem;
    color: var(--text-soft);
    font-size: .9rem; line-height: 1.7;
    max-width: 600px;
}

/* ────── EQUIPO ────── */
.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}
.team-card {
    border: 1px solid var(--border);
    border-radius: 10px; padding: 1.5rem;
    transition: border-color .2s;
}
.team-card:hover { border-color: var(--text); }
.team-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: var(--surface);
    color: var(--text);
    display: flex; align-items: center; justify-content: center;
    font-size: .88rem; font-weight: 500;
    margin-bottom: 1rem;
    border: 1px solid var(--border);
}
.team-card h3 { font-size: .98rem; color: var(--text); margin-bottom: .15rem; font-weight: 500; }
.team-card p { font-size: .8rem; color: var(--muted); }

/* ────── WHATSAPP FLOTANTE ────── */
.wa-float {
    position: fixed;
    bottom: 1.5rem; right: 1.5rem;
    width: 52px; height: 52px;
    border-radius: 50%;
    background: #25D366;
    box-shadow: 0 6px 20px rgba(37,211,102,.3);
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    z-index: 99;
    transition: transform .2s ease;
}
.wa-float:hover { transform: scale(1.06); }
.wa-float svg { width: 24px; height: 24px; }
@endsection

@section('content')

{{-- HERO --}}
<section class="hero">
    <div class="container hero-grid">
        <div>
            <h1>Tu piscina, nuestra pasión.</h1>
            <p class="hero-lead">
                Productos de calidad, asesoramiento experto y envío a todo el país.
                Todo lo que tu piscina necesita en un solo lugar.
            </p>

            <form action="{{ route('catalog.index') }}" method="GET" class="hero-search">
                <input type="text" name="search" placeholder="Buscar productos...">
                <button type="submit">Buscar</button>
            </form>

            <div class="hero-cta">
                <a href="{{ route('catalog.index') }}" class="btn btn-dark">Ver catálogo</a>
                <a href="#categorias" class="btn btn-outline">Categorías</a>
            </div>
        </div>
        <div class="hero-image"></div>
    </div>
</section>

{{-- BENEFICIOS --}}
<section class="benefits">
    <div class="container benefits-grid">
        <div class="benefit">
            <svg class="benefit-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 7h13l4 5v5h-2a2 2 0 1 1-4 0H9a2 2 0 1 1-4 0H3V7z"/></svg>
            <div class="benefit-text"><strong>Envíos a todo el país</strong><span>24-72hs hábiles</span></div>
        </div>
        <div class="benefit">
            <svg class="benefit-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 12l2 2 4-4M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z"/></svg>
            <div class="benefit-text"><strong>Calidad garantizada</strong><span>Marcas seleccionadas</span></div>
        </div>
        <div class="benefit">
            <svg class="benefit-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <div class="benefit-text"><strong>Asesoramiento</strong><span>Te ayudamos a elegir</span></div>
        </div>
        <div class="benefit">
            <svg class="benefit-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M2 12h4l3-9 6 18 3-9h4"/></svg>
            <div class="benefit-text"><strong>Stock real</strong><span>Disponible para envío</span></div>
        </div>
    </div>
</section>

{{-- CATEGORÍAS --}}
@if($categories->isNotEmpty())
<section class="section" id="categorias">
    <div class="container">
        <div class="section-head">
            <div class="head-row">
                <div>
                    <p class="eyebrow">Categorías</p>
                    <h2>Encontrá lo que necesitás.</h2>
                </div>
                <a href="{{ route('catalog.index') }}" class="link">Ver catálogo →</a>
            </div>
        </div>

        <div class="categories-grid">
            @foreach($categories as $cat)
                @php $cid = $cat['id'] ?? ''; @endphp
                <a href="{{ $cid ? route('catalog.index', ['category' => $cid]) : route('catalog.index') }}" class="cat-card">
                    <span class="cat-name">{{ $cat['name'] ?? '—' }}</span>
                    <svg class="cat-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- PRODUCTOS DESTACADOS --}}
@if($featured->isNotEmpty())
<section class="section" id="destacados" style="padding-top:1rem;">
    <div class="container">
        <div class="section-head">
            <div class="head-row">
                <div>
                    <p class="eyebrow">Destacados</p>
                    <h2>Lo más buscado.</h2>
                </div>
                <a href="{{ route('catalog.index') }}" class="link">Ver todos →</a>
            </div>
        </div>

        <div class="products-grid">
            @foreach($featured as $product)
                @php
                    $pid = $product['id'] ?? '';
                    $img = $product['main_image'] ?? null;
                @endphp
                <a href="{{ $pid ? route('catalog.show', $pid) : '#' }}" class="prod">
                    <div class="prod-img" @if($img) style="background-image:url('{{ $img }}');" @endif></div>
                    <div class="prod-body">
                        <h3 class="prod-name">{{ $product['name'] ?? '—' }}</h3>
                        <p class="prod-sku">SKU · {{ $product['sku'] ?? '—' }}</p>
                        <p class="prod-price">${{ number_format((float) ($product['price'] ?? 0), 2, ',', '.') }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- CÓMO COMPRAR --}}
<section class="section process">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow">Cómo comprar</p>
            <h2>3 pasos.</h2>
        </div>

        <div class="process-grid">
            <div class="process-step">
                <p class="process-num">01</p>
                <h3>Elegí tus productos</h3>
                <p>Navegá el catálogo y filtrá por categoría.</p>
            </div>
            <div class="process-step">
                <p class="process-num">02</p>
                <h3>Confirmá la compra</h3>
                <p>Coordinamos pago y envío. Te asesoramos.</p>
            </div>
            <div class="process-step">
                <p class="process-num">03</p>
                <h3>Recibí en 24-72hs</h3>
                <p>Despachamos el mismo día.</p>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="section">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow">Preguntas frecuentes</p>
            <h2>¿Tenés dudas?</h2>
        </div>

        <div class="faq-list">
            <details class="faq-item">
                <summary>¿Cuánto tarda el envío?</summary>
                <div class="faq-body">Despachamos el mismo día hábil si tu pedido entra antes de las 16hs. La entrega tarda entre 24 y 72 horas según tu localidad.</div>
            </details>
            <details class="faq-item">
                <summary>¿Qué métodos de pago aceptan?</summary>
                <div class="faq-body">Transferencia bancaria, efectivo, tarjeta de débito y crédito (también en cuotas).</div>
            </details>
            <details class="faq-item">
                <summary>¿Hacen instalaciones?</summary>
                <div class="faq-body">Sí, contamos con un equipo de instaladores en CABA y GBA. Para otras zonas trabajamos con profesionales locales.</div>
            </details>
            <details class="faq-item">
                <summary>¿Tienen garantía los productos?</summary>
                <div class="faq-body">Todos los productos tienen garantía oficial del fabricante.</div>
            </details>
            <details class="faq-item">
                <summary>¿Puedo retirar en el local?</summary>
                <div class="faq-body">Sí. Una vez confirmada la compra, te enviamos la dirección y horarios sin costo adicional.</div>
            </details>
        </div>
    </div>
</section>

{{-- WHATSAPP --}}
<a href="https://wa.me/5491155551234?text=Hola!%20Quiero%20consultar%20por%20productos%20de%20MA%20Piscinas" target="_blank" rel="noopener" class="wa-float" aria-label="WhatsApp">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.768.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
</a>

@endsection
