# Proyecto E-Commerce MA Piscinas

## Integrantes

| Nombre | Rol |
|--------|-----|
| Francisco Terron | Desarrollador Full Stack |
| Mauro San Pedro | Desarrollador Full Stack |

## Empresa Ficticia

**MA Piscinas** — E-commerce de venta de piscinas e insumos de mantenimiento.

## Enunciado del Proyecto 
Se propone el desarrollo de una aplicación web de tipo e-commerce orientada a la comercialización de piscinas e insumos asociados, tales como productos de mantenimiento, accesorios y repuestos.
El sistema estará compuesto por dos módulos principales: un panel administrativo y una interfaz de usuario para clientes. El panel administrativo permitirá la gestión integral del sistema, incluyendo la administración de categorías, subcategorías, productos, descuentos, usuarios, pedidos, envíos y dashboard con reportes (Panel estadístico con gráficos de ventas, productos más vendidos, análisis de negocio). Este módulo será desarrollado utilizando PHP con el framework Laravel y vistas renderizadas mediante Blade, aplicando el patrón de arquitectura MVC.
Por otro lado, la interfaz de usuario estará orientada a la experiencia de compra, permitiendo la navegación del catálogo, la selección de productos, la gestión de un carrito de compras y la realización de pedidos. Este módulo será implementado como una aplicación de una sola página (SPA) utilizando React, consumiendo una API REST provista por el backend.
La persistencia de datos será gestionada mediante Firestore, garantizando integridad y consistencia de la información. Para el procesamiento de pagos se integrará la pasarela Mercado Pago, permitiendo la gestión de transacciones de forma segura.
El entorno de desarrollo será contenerizado mediante Docker, facilitando la portabilidad y replicabilidad del sistema. Para el despliegue, la aplicación frontend será publicada en Vercel, mientras que el backend será alojado en un servidor compatible con PHP.
El desarrollo contemplará buenas prácticas en términos de seguridad, validación de datos, accesibilidad, usabilidad y organización del código, con el objetivo de construir una solución escalable, mantenible y alineada con estándares actuales de desarrollo web.

## Stack de tecnologias

### Backend
- Laravel (PHP)
- API REST + MVC (Blade para administrativos)

### Base de Datos
- Firestore

### Frontend Administrativo
- Blade (Laravel)
- Tailwind CSS

### Frontend Cliente (E-commerce)
- React
- Tailwind CSS

### Pagos
- Mercado Pago

### Infraestructura (Desarrollo)
- Docker

### Deploy
- Vercel

### Herramientas de desarrollo
- Visual Studio Code
- Git
- Node.js

---

## Estado del Proyecto y Avances

### Primer Entrega (PR) — 20 de Abril 2025
**Módulo**: Backend Laravel (Deploy inicial + Frontpage)

| Item | Estado | Observaciones |
|------|--------|---------------|
| Repositorio GitHub | ✅ | `FranciscoTerron/AplicacionesWeb` |
| Docker configurado | ✅ | Contenedores backend y nginx |
| Laravel 13 instalado | ✅ | Estructura base funcional |
| Landing page con datos | ✅ | Empresa "MA Piscinas" + integrantes |
| PR creado hacia `main` | ✅ | PR Aprobado |
| Deploy en Vercel | ✅ | Correcto |

**Conclusión**: Se cumplió con lo mínimo requerido para el 20/04.

---

### Segunda Entrega (PR) — 11 de Mayo 2026
**Módulo**: Backend Laravel (CRUDs parciales + Panel Admin + Firestore)

| Item | Estado | Observaciones |
|------|--------|---------------|
| **CRUD Categorías** | ✅ | **Completo y funcional** |
| **CRUD Subcategorías** | ✅ | **Completo y funcional** |
| **CRUD Productos** | ✅ | **Completo y funcional**|
| **CRUD Descuentos** | ✅ | **Completo y funcional**|
| **CRUD Usuarios** | ✅ | **Completo y funcional** |
| **CRUD Clientes** | ✅ | **Completo y funcional** |
| Configuración Firestore | ✅ | FirestoreService implementado, reglas y desplegados en Firebase |
| Login Administrativos | ✅ | AuthController con Firebase implementado; FirestoreUserProvider bloquea usuarios inactivos |
| Panel Administrativo | ⚠️ | Estructura base con sidebar, ruteo y vistas Blade |
| Dashboard con estadísticas | ✅ | **Completo y funcional** |
| Ruteo configurado | ✅ | Web routes definidas para admin y auth |

**Conclusión**: CRUD Usuarios 100% funcional con UX optimizada (modal único, AJAX inline) y código robusto (tests + PHPStan).

---

## Cronograma de Entregas (PRs Obligatorios)

| Fecha Límite | Hito | Estado |
|--------------|------|--------|
| 20 de Abril | Deploy en Vercel (frontpage mínimo) | ✅ Completo PR |
| 11 de Mayo | Avance Laravel (CRUDs parciales) | ✅ En proceso |
| 25 de Mayo | Avance Laravel (funcionalidades adicionales) | ⏳ Pendiente |
| 7 de Junio | PR Final — Laravel + API Completa | ⏳ Pendiente |

*Las fechas corresponden al cronograma establecido por la catedra.*
