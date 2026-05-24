<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="cloudinary-cloud-name" content="{{ config('cloudinary.cloud_name') }}">
    <meta name="cloudinary-upload-preset" content="{{ config('cloudinary.upload_preset') }}">
    <title>@yield('title', 'MA Piscinas - Admin')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS (sin preflight) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { corePlugins: { preflight: false } }
    </script>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #0284c7;
            --primary-dark: #075985;
            --primary-light: #38bdf8;
            --accent: #06b6d4;
            --dark: #0f172a;
            --white: #FFFFFF;
            --bg-light: #f8fafc;
            --bg-sidebar: #1e293b;
            --bg-topbar: #1e293b;
            --shadow-card: 0 1px 2px rgba(15,23,42,0.04), 0 1px 3px rgba(15,23,42,0.06);
            --radius-card: 12px;
            --text: #0f172a;
            --text-soft: #334155;
            --text-muted: #64748b;
            --danger: #dc2626;
            --success: #10b981;
            --warning: #f59e0b;
            --border: #e2e8f0;
            --sidebar-width: 250px;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg-light);
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-sidebar);
            color: var(--white);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            height: 64px;
            display: flex;
            align-items: center;
        }

        .sidebar-logo {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.55rem;
            letter-spacing: -0.01em;
        }

        .sidebar-logo::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary-light);
            box-shadow: 0 0 10px rgba(56,189,248,0.6);
        }

        .sidebar-logo span {
            color: var(--primary-light);
            font-weight: 500;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0;
        }

        .nav-section {
            margin-bottom: 1.25rem;
        }

        .nav-section-title {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.4);
            padding: 0 1.5rem;
            margin-bottom: 0.4rem;
            font-weight: 600;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.55rem 1.5rem 0.55rem calc(1.5rem - 2px);
            margin: 0 0.5rem;
            border-radius: 6px;
            color: rgba(255,255,255,0.72);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.15s;
            border-left: 2px solid transparent;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.06);
            color: var(--white);
        }

        .nav-link.active {
            background: rgba(56,189,248,0.18);
            color: var(--primary-light);
            border-left-color: var(--primary-light);
        }

        .nav-icon {
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.85;
        }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.5rem;
            border-radius: 8px;
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .user-details {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--white);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.5);
        }

.logout-btn {
             background: none;
             border: none;
             color: rgba(255,255,255,0.5);
             cursor: pointer;
             padding: 0.5rem;
             border-radius: 6px;
             transition: all 0.15s;
         }

         .logout-btn:hover {
             color: var(--white);
             background: rgba(255,255,255,0.06);
         }

         /* Main Content */
         .main-content {
             flex: 1;
             margin-left: var(--sidebar-width);
             min-height: 100vh;
         }

         .top-bar {
             background: var(--bg-topbar);
             color: var(--white);
             padding: 1rem 2rem;
             border-bottom: 1px solid rgba(255,255,255,0.08);
             display: flex;
             justify-content: space-between;
             align-items: center;
             position: sticky;
             top: 0;
             z-index: 50;
             min-height: 64px;
         }

         .page-title {
             font-size: 1.25rem;
             font-weight: 600;
             color: var(--white);
             letter-spacing: -0.01em;
         }

         .page-subtitle {
             font-size: 0.82rem;
             color: rgba(255,255,255,0.55);
             margin-top: 0.15rem;
         }

         /* Breadcrumbs */
         .breadcrumb {
             margin-bottom: 0;
         }

         .breadcrumb-item + .breadcrumb-item::before {
             content: '/';
             color: #64748b;
         }

         .breadcrumb-item a:hover {
             text-decoration: underline;
         }

         .top-bar .mobile-toggle {
             color: var(--white);
         }

         .content {
             padding: 2rem;
         }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block;
            }
        }

        @media (min-width: 769px) {
            .mobile-toggle {
                display: none;
            }
        }

        /* ============================================
           COMPONENTES CSS REUTILIZABLES
           Estilos complejos que no pueden expresarse
           fácilmente con Tailwind utility classes
           ============================================ */
        
        /* --------------------------------------------
           STEP PROGRESS - Formularios multi-paso
           Usado en: discounts/index.blade.php
           -------------------------------------------- */
        .step-progress {
            margin-bottom: 1.5rem;
        }
        
        .step-progress .progress {
            height: 6px;
            background-color: #e9ecef;
            border-radius: 3px;
        }
        
        .step-progress .progress-bar {
            background: linear-gradient(90deg, #0d6efd, #6610f2);
            border-radius: 3px;
        }
        
        .step-indicator {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .step-indicator:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            width: calc(100% - 30px);
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .step-indicator.completed:not(:last-child)::after {
            background: #198754;
        }
        
        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            position: relative;
            z-index: 2;
        }
        
        .step-indicator.active .step-circle {
            background: #0d6efd;
            color: white;
        }
        
        .step-indicator.completed .step-circle {
            background: #198754;
            color: white;
        }
        
        .step-label {
            font-size: 0.75rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .step-indicator.active .step-label {
            color: #0d6efd;
            font-weight: 600;
        }
        
        .step-indicator.completed .step-label {
            color: #198754;
        }
        
        /* --------------------------------------------
           DISCOUNT DETAILS - Detalles de descuento
           Usado en: discounts/index.blade.php
           -------------------------------------------- */
        .discount-details h6 {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .discount-details .row {
            margin-bottom: 1rem;
        }
        
        .discount-details .progress {
            background-color: #e9ecef;
        }
        
        .discount-details code {
            background-color: #f8f9fa;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-size: 0.85em;
        }
        
        .discount-details .text-muted {
            font-size: 0.85em;
        }
        
        /* --------------------------------------------
           SUMMARY CARD - Tarjeta de resumen
           Usado en: discounts/index.blade.php
           -------------------------------------------- */
        .summary-card {
            background: #f8f9fa;
            border-color: #dee2e6 !important;
        }
        
        .summary-card h6 {
            color: #495057;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 0.25rem;
        }
        
        /* --------------------------------------------
           APPLIES-TO DETAILS - Sección de aplicación
           Usado en: discounts/index.blade.php
           -------------------------------------------- */
        .applies-to-details {
            transition: all 0.3s ease;
        }
        
        /* --------------------------------------------
           MODAL CONFIRMATION STYLES
           Usado en: discounts/index.blade.php
           -------------------------------------------- */
        /* Deactivation Modal */
        .deactivation-modal .discount-summary,
        .activation-modal .discount-summary {
            border-left: 4px solid #0d6efd !important;
        }
        
        .deactivation-modal .impact-alert {
            border-left: 4px solid #dc3545;
        }
        
        .activation-modal .impact-alert {
            border-left: 4px solid #198754;
        }
        
        .deactivation-modal .confirmation-section,
        .activation-modal .confirmation-section {
            border-left: 4px solid currentColor !important;
        }
        
        .deactivation-modal .confirmation-section {
            border-color: #dc3545 !important;
        }
        
        .activation-modal .confirmation-section {
            border-color: #198754 !important;
        }
        
        .discount-summary h6 {
            color: #495057;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 0.25rem;
        }
        
        .impact-alert ul li {
            margin-bottom: 0.25rem;
        }
        
        .impact-alert ul li:last-child {
            margin-bottom: 0;
        }
        
        /* --------------------------------------------
           EMPTY STATE - Estado vacío (unificado)
           Usado en: discounts, users, clients, etc.
           -------------------------------------------- */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        
        /* --------------------------------------------
           MODAL BACKDROP - Fondo del modal
           Usado en: discounts, users, clients
           -------------------------------------------- */
        .modal-backdrop.show {
            opacity: 0.5;
        }    </style>
</head>
<body>
    <div class="layout">
        @include('admin.partials.sidebar')
        
        <div class="main-content">
            <header class="top-bar">
                @include('admin.components.page-header')
                <button class="mobile-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')" style="background:none;border:none;cursor:pointer;padding:0.5rem;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12h18M3 6h18M3 18h18"/>
                    </svg>
                </button>
            </header>
            
            <main class="content">
                @include('admin.partials._breadcrumbs')

                @yield('content')
            </main>
            {{-- Toast container (Bootstrap 5) --}}
            <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
                @if(session('success'))
                    <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000" data-auto-show="1">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                        </div>
                    </div>
                @endif
                @if(session('error'))
                    <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false" data-auto-show="1">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

  <!-- Lightbox global de imágenes (compartido por products/categories/subcategories) -->
  <div class="modal fade" id="imageLightboxModal" tabindex="-1" aria-hidden="true" aria-labelledby="imageLightboxTitle">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content" style="background:#0f172a;border:none;">
        <div class="modal-header border-0" style="padding:.75rem 1rem;">
          <h5 class="modal-title text-white" id="imageLightboxTitle" style="font-size:.95rem;">Imagen</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body p-0 text-center">
          <img id="imageLightboxImg" src="" alt="" style="max-width:100%;max-height:80vh;display:block;margin:0 auto;">
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://upload-widget.cloudinary.com/global/all.js" type="text/javascript"></script>
  @include('admin.components._cloudinary_js')
  <script>
  // Auto-show server-side toasts (flash session)
  document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.toast[data-auto-show="1"]').forEach(function (el) {
          try { new bootstrap.Toast(el).show(); } catch (e) { /* noop */ }
      });
  });

  // Helper global para mostrar toasts desde JS (e.g. después de un AJAX en la misma página).
  window.showToast = function (message, type) {
      type = type || 'success';
      const container = document.querySelector('.toast-container') || (function () {
          const c = document.createElement('div');
          c.className = 'toast-container position-fixed top-0 end-0 p-3';
          c.style.zIndex = 1100;
          document.body.appendChild(c);
          return c;
      })();
      const bgClass = type === 'error' || type === 'danger' ? 'bg-danger' : (type === 'warning' ? 'bg-warning' : 'bg-success');
      const icon = type === 'error' || type === 'danger' ? 'bi-exclamation-triangle-fill' : (type === 'warning' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill');
      const autohide = type === 'error' || type === 'danger' ? 'data-bs-autohide="false"' : 'data-bs-delay="4000"';
      const wrapper = document.createElement('div');
      wrapper.className = 'toast align-items-center text-white border-0 ' + bgClass;
      wrapper.setAttribute('role', 'alert');
      wrapper.setAttribute('aria-live', 'assertive');
      wrapper.setAttribute('aria-atomic', 'true');
      wrapper.innerHTML = `<div class="d-flex"><div class="toast-body"><i class="bi ${icon} me-2"></i>${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button></div>`;
      wrapper.setAttribute(autohide.split('=')[0], autohide.split('=')[1].replace(/"/g, ''));
      container.appendChild(wrapper);
      try { new bootstrap.Toast(wrapper).show(); } catch (e) { /* noop */ }
  };

  // Lightbox global: muestra imagen grande al click en thumb
  window.showImageLightbox = function (url, title) {
      if (!url) return;
      const img = document.getElementById('imageLightboxImg');
      const titleEl = document.getElementById('imageLightboxTitle');
      const modalEl = document.getElementById('imageLightboxModal');
      if (!img || !modalEl) return;
      // Usar tamaño grande pero limitado por Cloudinary para no bajar 9MB
      const bigUrl = url.replace('/upload/', '/upload/c_limit,w_1400,h_1400,q_auto,f_auto/');
      img.src = bigUrl;
      img.alt = title || 'Imagen';
      if (titleEl) titleEl.textContent = title || 'Imagen';
      try { bootstrap.Modal.getOrCreateInstance(modalEl).show(); } catch (e) { /* noop */ }
  };

  // Pagination per_page selector
  document.querySelectorAll('.per-page-select').forEach(function (select) {
      select.addEventListener('change', function () {
          var route = this.getAttribute('data-route');
          var perPage = this.value;
          var url = new URL(window.location.href);
          url.searchParams.set('per_page', perPage);
          url.searchParams.set('page', '1'); // reset to page 1 on size change
          window.location.href = url.toString();
      });
  });
  </script>
  @yield('scripts')
</body>
</html>