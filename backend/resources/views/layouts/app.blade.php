<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MA Piscinas — E-commerce de Piscinas e Insumos')</title>
    <meta name="description" content="MA Piscinas — venta online de piscinas, insumos y equipamiento. Calidad, asesoramiento y envíos a todo el país.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #ffffff;
            --surface:   #f8fafc;
            --primary:   #0284c7;
            --primary-dark: #075985;
            --accent:    #06b6d4;
            --light:     #e0f2fe;
            --text:      #0f172a;
            --text-soft: #334155;
            --muted:     #64748b;
            --border:    #e2e8f0;
            --success:   #10b981;
            --danger:    #dc2626;
            --shadow-sm: 0 1px 2px rgba(15,23,42,.04);
            --shadow:    0 4px 16px rgba(15,23,42,.06);
            --shadow-lg: 0 16px 40px rgba(15,23,42,.08);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            font-feature-settings: 'cv11', 'ss01';
        }

        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        button { font-family: inherit; }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; }

        /* ────── NAVBAR ────── */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255,255,255,.9);
            backdrop-filter: saturate(180%) blur(20px);
            border-bottom: 1px solid var(--border);
        }
        .nav-inner {
            display: flex; align-items: center; justify-content: space-between;
            height: 68px;
        }
        .nav-brand {
            font-size: 1.15rem; font-weight: 700; letter-spacing: -.01em;
            color: var(--text);
            display: flex; align-items: center; gap: .55rem;
        }
        .nav-brand .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--primary);
        }
        .nav-links {
            display: flex; gap: 2rem; list-style: none;
            font-size: .9rem; font-weight: 500;
        }
        .nav-links a {
            color: var(--text-soft); padding: .5rem 0;
            transition: color .2s;
        }
        .nav-links a:hover { color: var(--primary); }

        .nav-cta {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .55rem 1.1rem;
            background: var(--text); color: #fff;
            border-radius: 8px;
            font-size: .85rem; font-weight: 500;
            transition: background .2s;
        }
        .nav-cta:hover { background: var(--primary); }

        @media (max-width: 768px) {
            .nav-links { display: none; }
        }

        /* ────── BOTONES ────── */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            padding: .85rem 1.5rem; border-radius: 8px;
            font-size: .92rem; font-weight: 500;
            border: 1px solid transparent; cursor: pointer;
            transition: all .2s ease;
            white-space: nowrap;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { border-color: var(--border); color: var(--text); background: #fff; }
        .btn-outline:hover { border-color: var(--text); }
        .btn-dark { background: var(--text); color: #fff; }
        .btn-dark:hover { background: var(--primary); }

        /* ────── FOOTER ────── */
        .footer {
            background: var(--text);
            color: rgba(255,255,255,.7);
            padding: 4rem 0 2rem;
            margin-top: 5rem;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        @media (max-width: 768px) {
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }

        .footer-brand {
            font-size: 1.15rem; font-weight: 700; color: #fff; margin-bottom: 1rem;
        }
        .footer p, .footer a, .footer li { font-size: .88rem; }
        .footer h4 {
            font-size: .8rem; font-weight: 600;
            letter-spacing: .08em; text-transform: uppercase;
            color: #fff; margin-bottom: 1.2rem;
        }
        .footer ul { list-style: none; }
        .footer li { margin-bottom: .55rem; }
        .footer a { transition: color .2s; }
        .footer a:hover { color: var(--accent); }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,.1);
            padding-top: 1.5rem;
            text-align: center;
            font-size: .8rem;
            color: rgba(255,255,255,.45);
        }

        /* ────── ALERTAS ────── */
        .alert {
            padding: .8rem 1rem; border-radius: 8px;
            margin-bottom: 1rem; font-size: .9rem;
        }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }

        @yield('styles')
    </style>
</head>
<body>

    <header class="navbar">
        <div class="container nav-inner">
            <a href="{{ route('home') }}" class="nav-brand">
                <span class="dot"></span>
                MA Piscinas
            </a>
            <nav>
                <ul class="nav-links">
                    <li><a href="{{ route('home') }}">Inicio</a></li>
                    <li><a href="{{ route('catalog.index') }}">Catálogo</a></li>
                    <li><a href="{{ route('home') }}#categorias">Categorías</a></li>
                    <li><a href="{{ route('home') }}#contacto">Contacto</a></li>
                </ul>
            </nav>
            <a href="{{ route('login') }}" class="nav-cta">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="8" r="4"/><path d="M4 22c0-4 4-7 8-7s8 3 8 7"/></svg>
                Admin
            </a>
        </div>
    </header>

    @if(session('error'))
        <div class="container" style="margin-top:1rem;">
            <div class="alert alert-error">{{ session('error') }}</div>
        </div>
    @endif

    @yield('content')

    <footer class="footer" id="contacto">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand">MA Piscinas</div>
                    <p style="line-height:1.7;color:rgba(255,255,255,.65);">Calidad, asesoramiento y compromiso para que tu piscina sea un lugar de disfrute todo el año.</p>
                </div>
                <div>
                    <h4>Tienda</h4>
                    <ul>
                        <li><a href="{{ route('catalog.index') }}">Catálogo</a></li>
                        <li><a href="{{ route('home') }}#categorias">Categorías</a></li>
                        <li><a href="{{ route('home') }}#destacados">Destacados</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Empresa</h4>
                    <ul>
                        <li><a href="{{ route('home') }}#categorias">Categorías</a></li>
                        <li><a href="{{ route('login') }}">Panel admin</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Contacto</h4>
                    <ul>
                        <li>info@mapiscinas.com</li>
                        <li>+54 11 5555-1234</li>
                        <li>Buenos Aires, Argentina</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                © {{ date('Y') }} MA Piscinas — Aplicaciones Web · Universidad
            </div>
        </div>
    </footer>

    @yield('scripts')
</body>
</html>
