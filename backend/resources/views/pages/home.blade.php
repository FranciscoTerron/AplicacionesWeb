@extends('layouts.app')

@section('title', 'MA Piscinas — E-commerce de Piscinas e Insumos')

@section('styles')
/* ── HERO ─────────────────────────────────────── */
.hero {
    min-height: 100vh;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

/* Grilla de azulejos de pileta */
.hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(6,182,212,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(6,182,212,.06) 1px, transparent 1px);
    background-size: 60px 60px;
    animation: gridDrift 25s linear infinite;
}

@keyframes gridDrift {
    from { transform: translateY(0); }
    to   { transform: translateY(60px); }
}

/* Glow central */
.hero::after {
    content: '';
    position: absolute;
    width: 80vw; height: 80vw;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(6,182,212,.18) 0%, transparent 68%);
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    animation: breathe 5s ease-in-out infinite;
}

@keyframes breathe {
    0%,100% { opacity: .7; transform: translate(-50%,-50%) scale(1); }
    50%      { opacity: 1;  transform: translate(-50%,-50%) scale(1.08); }
}

.hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    padding: 2rem;
}

.badge {
    display: inline-block;
    font-size: .65rem;
    font-weight: 500;
    letter-spacing: .45em;
    text-transform: uppercase;
    color: var(--cyan);
    border: 1px solid rgba(6,182,212,.35);
    padding: .5rem 1.6rem;
    border-radius: 2rem;
    margin-bottom: 2.5rem;
    animation: fadeDown .8s ease both;
}

@keyframes fadeDown {
    from { opacity: 0; transform: translateY(-16px); }
    to   { opacity: 1; transform: translateY(0); }
}

.hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(5.5rem, 16vw, 14rem);
    font-weight: 700;
    line-height: .88;
    letter-spacing: -.02em;
    background: linear-gradient(135deg, #fff 0%, var(--light) 35%, var(--aqua) 65%, #0ea5e9 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    background-size: 200% 200%;
    animation: shimmer 5s ease infinite, fadeUp 1s ease .2s both;
}

@keyframes shimmer {
    0%,100% { background-position: 0% 50%; }
    50%      { background-position: 100% 50%; }
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(28px); }
    to   { opacity: 1; transform: translateY(0); }
}

.hero-sub {
    font-size: clamp(.9rem, 2.2vw, 1.25rem);
    font-weight: 300;
    color: rgba(186,230,253,.65);
    letter-spacing: .18em;
    text-transform: uppercase;
    margin-top: 1.5rem;
    animation: fadeUp 1s ease .4s both;
}

.scroll-hint {
    position: absolute;
    bottom: 2.5rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .5rem;
    color: rgba(186,230,253,.35);
    font-size: .6rem;
    letter-spacing: .25em;
    text-transform: uppercase;
    animation: fadeUp 1s ease 1s both;
}

.scroll-line {
    width: 1px; height: 55px;
    background: linear-gradient(to bottom, transparent, var(--cyan));
    animation: lineGrow 2s ease-in-out infinite;
}

@keyframes lineGrow {
    0%,100% { opacity: .3; transform: scaleY(.5); transform-origin: top; }
    50%      { opacity: 1;  transform: scaleY(1);  transform-origin: top; }
}

/* ── WAVES ────────────────────────────────────── */
.wave-wrap { overflow: hidden; line-height: 0; margin-top: -1px; }
.wave-wrap svg { display: block; width: 200%; }
.w1 { animation: waveMove 9s  linear infinite; }
.w2 { animation: waveMove 12s linear infinite reverse; opacity: .5; }
.w3 { animation: waveMove 7s  linear infinite; opacity: .3; }

@keyframes waveMove {
    from { transform: translateX(0); }
    to   { transform: translateX(-50%); }
}

/* ── SECCIONES ─────────────────────────────────── */
.section { max-width: 1080px; margin: 0 auto; padding: 6rem 2rem; }

.label {
    font-size: .62rem;
    letter-spacing: .5em;
    text-transform: uppercase;
    color: var(--cyan);
    font-weight: 500;
    margin-bottom: .75rem;
}

.section-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.4rem, 5.5vw, 4rem);
    font-weight: 600;
    line-height: 1.1;
    margin-bottom: 3rem;
}

/* ── PRODUCTOS ─────────────────────────────────── */
.products {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.5px;
    background: rgba(6,182,212,.1);
    border: 1px solid rgba(6,182,212,.1);
    margin-bottom: 7rem;
}

.product-card {
    background: var(--navy);
    padding: 2.5rem;
    position: relative;
    overflow: hidden;
    transition: background .3s;
}

.product-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 3px; height: 0;
    background: var(--cyan);
    transition: height .4s ease;
}

.product-card:hover { background: rgba(6,182,212,.05); }
.product-card:hover::before { height: 100%; }

.product-icon { font-size: 2rem; margin-bottom: 1rem; display: block; }

.product-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.6rem;
    font-weight: 600;
    margin-bottom: .6rem;
}

.product-desc {
    font-size: .88rem;
    color: rgba(186,230,253,.5);
    line-height: 1.65;
}

/* ── EQUIPO ────────────────────────────────────── */
.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.team-card {
    border: 1px solid rgba(6,182,212,.15);
    overflow: hidden;
    transition: border-color .3s, transform .3s;
}

.team-card:hover {
    border-color: rgba(6,182,212,.5);
    transform: translateY(-5px);
}

.team-top {
    height: 150px;
    background: linear-gradient(135deg, var(--deep-blue), rgba(6,182,212,.2));
    position: relative;
    overflow: hidden;
}

.team-top::before {
    content: '';
    position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(6,182,212,.13) 1px, transparent 1px),
        linear-gradient(90deg, rgba(6,182,212,.13) 1px, transparent 1px);
    background-size: 22px 22px;
}

.avatar {
    position: absolute;
    bottom: -2rem; left: 2rem;
    width: 4.5rem; height: 4.5rem;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--pool), var(--cyan));
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.4rem;
    font-weight: 700;
    color: #fff;
    border: 3px solid var(--navy);
    z-index: 2;
}

.team-body {
    padding: 3rem 2rem 2rem;
    background: var(--deep-blue);
}

.team-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.9rem;
    font-weight: 600;
    margin-bottom: .3rem;
}

.team-role {
    font-size: .7rem;
    letter-spacing: .32em;
    text-transform: uppercase;
    color: var(--cyan);
    font-weight: 400;
}
@endsection

@section('content')

{{-- HERO --}}
<div class="hero">
    <div class="hero-content">
        <span class="badge">E-Commerce · Piscinas · Insumos</span>
        <h1 class="hero-title">MA<br>Piscinas</h1>
        <p class="hero-sub">Tu piscina, nuestra pasión</p>
    </div>
    <div class="scroll-hint">
        <span>Explorar</span>
        <div class="scroll-line"></div>
    </div>
</div>

{{-- ONDAS DE TRANSICIÓN --}}
<div class="wave-wrap">
    <svg viewBox="0 0 2880 80" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path class="w1" d="M0,40 C240,80 480,0 720,40 C960,80 1200,0 1440,40 C1680,80 1920,0 2160,40 C2400,80 2640,0 2880,40 L2880,80 L0,80 Z" fill="rgba(6,182,212,0.08)"/>
        <path class="w2" d="M0,55 C280,15 560,75 840,55 C1120,35 1400,75 1680,55 C1960,35 2240,75 2520,55 C2700,40 2880,55 2880,55 L2880,80 L0,80 Z" fill="rgba(14,165,233,0.06)"/>
        <path class="w3" d="M0,65 C200,35 400,80 600,65 C800,50 1000,80 1200,65 C1400,50 1600,80 1800,65 C2000,48 2200,70 2400,65 C2600,58 2880,65 2880,65 L2880,80 L0,80 Z" fill="rgba(6,182,212,0.04)"/>
    </svg>
</div>

{{-- PRODUCTOS --}}
<div class="section">
    <p class="label">Nuestros Productos</p>
    <h2 class="section-title">Todo lo que<br>tu piscina necesita</h2>
    <div class="products">
        <div class="product-card">
            <span class="product-icon">🏊</span>
            <h3 class="product-name">Piscinas</h3>
            <p class="product-desc">Diseño y construcción de piscinas a medida para cada espacio y presupuesto.</p>
        </div>
        <div class="product-card">
            <span class="product-icon">🧴</span>
            <h3 class="product-name">Insumos</h3>
            <p class="product-desc">Cloro, algicidas, balanceadores de pH y todo lo necesario para el agua perfecta.</p>
        </div>
        <div class="product-card">
            <span class="product-icon">⚙️</span>
            <h3 class="product-name">Equipamiento</h3>
            <p class="product-desc">Bombas, filtros, skimmers y calefactores de las mejores marcas del mercado.</p>
        </div>
    </div>
</div>

{{-- EQUIPO — los datos vienen del HomeController --}}
<div class="section" style="padding-top: 0">
    <p class="label">El Equipo</p>
    <h2 class="section-title">Quiénes somos</h2>
    <div class="team-grid">
        @foreach($integrantes as $integrante)
        <div class="team-card">
            <div class="team-top">
                <div class="avatar">{{ $integrante['iniciales'] }}</div>
            </div>
            <div class="team-body">
                <h3 class="team-name">{{ $integrante['nombre'] }}</h3>
                <p class="team-role">Desarrollador · Aplicaciones Web</p>
            </div>
        </div>
        @endforeach
    </div>
</div>

@endsection
