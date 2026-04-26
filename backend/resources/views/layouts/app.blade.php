<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MA Piscinas')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;600;700&family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:      #050e1d;
            --deep-blue: #0c1f3d;
            --pool:      #0369a1;
            --cyan:      #06b6d4;
            --aqua:      #22d3ee;
            --light:     #bae6fd;
            --white:     #f0f9ff;
            /* Colores para login */
            --primary: #0077B6;
            --primary-light: #00B4D8;
            --accent: #00ADB5;
            --dark: #023E8A;
            --text: #212529;
            --danger: #DC3545;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--navy);
            color: var(--white);
            overflow-x: hidden;
            cursor: crosshair;
        }

        /* ── Estilos adicionales por página ── */
        @yield('styles')
    </style>
</head>
<body>

    @yield('content')

    <footer style="text-align:center;padding:3.5rem 2rem;border-top:1px solid rgba(6,182,212,.1);color:rgba(186,230,253,.3);font-size:.78rem;letter-spacing:.15em;">
        <p>© {{ date('Y') }} <strong style="color:var(--cyan);font-weight:400">MA Piscinas</strong> &mdash; Aplicaciones Web &middot; Universidad</p>
        <div style="margin-top:1.5rem;">
            <a href="{{ route('login') }}" style="display:inline-block;padding:.6rem 1.8rem;border:1px solid var(--cyan);color:var(--cyan);text-decoration:none;border-radius:2rem;font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;transition:all .3s;">
                Panel Admin
            </a>
        </div>
    </footer>

    @yield('scripts')

</body>
</html>
