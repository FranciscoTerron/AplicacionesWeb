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
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--navy);
            color: var(--white);
            overflow-x: hidden;
            cursor: crosshair;
        }

        /* ── RIPPLE al click (efecto agua — disponible en todas las páginas) ── */
        .ripple {
            position: fixed;
            border-radius: 50%;
            transform: scale(0);
            animation: rippleOut .9s linear forwards;
            background: rgba(6,182,212,.12);
            pointer-events: none;
            z-index: 9999;
        }

        @keyframes rippleOut {
            to { transform: scale(5); opacity: 0; }
        }

        /* ── Estilos adicionales por página ── */
        @yield('styles')
    </style>
</head>
<body>

    @yield('content')

    <footer style="text-align:center;padding:3.5rem 2rem;border-top:1px solid rgba(6,182,212,.1);color:rgba(186,230,253,.3);font-size:.78rem;letter-spacing:.15em;">
        <p>© {{ date('Y') }} <strong style="color:var(--cyan);font-weight:400">MA Piscinas</strong> &mdash; Aplicaciones Web &middot; Universidad</p>
    </footer>

    {{-- Efecto agua global --}}
    <script>
        document.addEventListener('click', function (e) {
            const r = document.createElement('div');
            r.classList.add('ripple');
            const size = 220;
            r.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX - size/2}px;top:${e.clientY - size/2}px`;
            document.body.appendChild(r);
            r.addEventListener('animationend', () => r.remove());
        });
    </script>

    @yield('scripts')

</body>
</html>
