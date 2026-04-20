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

### Última Entrega (PR) — 20 de Abril 2025
**Módulo**: Backend Laravel (Deploy inicial + Frontpage)

| Item | Estado | Observaciones |
|------|--------|---------------|
| Repositorio GitHub | ✅ | `FranciscoTerron/AplicacionesWeb` |
| Docker configurado | ✅ | Contenedores backend y nginx |
| Laravel 13 instalado | ✅ | Estructura base funcional |
| Landing page con datos | ✅ | Empresa "MA Piscinas" + integrantes |
| PR creado hacia `main` | ✅ | PR en espera de aprobación de docentes |
| Deploy en Vercel | ⏳ | Pendiente de aprobación del PR |

**Conclusión**: Se cumplió con lo mínimo requerido para el 20/04. Se espera aprobación del PR y configuración de Vercel una vez aprobado.

---

## Cronograma de Entregas (PRs Obligatorios)

| Fecha Límite | Hito | Estado |
|--------------|------|--------|
| 20 de Abril | Deploy en Vercel (frontpage mínimo) | ⚠️ Pendiente PR |
| 11 de Mayo | Avance Laravel (CRUDs parciales) | ⏳ Pendiente |
| 25 de Mayo | Avance Laravel (funcionalidades adicionales) | ⏳ Pendiente |
| 7 de Junio | PR Final — Laravel + API Completa | ⏳ Pendiente |

*Las fechas corresponden al cronograma establecido por la catedra.*