# Proyecto E-Commerce MA Piscinas

## Enunciado del Proyecto
Se propone el desarrollo de una aplicación web de tipo e-commerce orientada a la comercialización de piscinas e insumos asociados, tales como productos de mantenimiento, accesorios y repuestos.
El sistema estará compuesto por dos módulos principales: un panel administrativo y una interfaz de usuario para clientes. El panel administrativo permitirá la gestión integral del sistema, incluyendo la administración de categorías, subcategorías, productos, usuarios, pedidos y envíos. Este módulo será desarrollado utilizando PHP con el framework Laravel y vistas renderizadas mediante Blade, aplicando el patrón de arquitectura MVC.
Por otro lado, la interfaz de usuario estará orientada a la experiencia de compra, permitiendo la navegación del catálogo, la selección de productos, la gestión de un carrito de compras y la realización de pedidos. Este módulo será implementado como una aplicación de una sola página (SPA) utilizando React, consumiendo una API REST provista por el backend.
La persistencia de datos será gestionada mediante PostgreSQL, garantizando integridad y consistencia de la información. Para el procesamiento de pagos se integrará la pasarela Mercado Pago, permitiendo la gestión de transacciones de forma segura.
El entorno de desarrollo será contenerizado mediante Docker, facilitando la portabilidad y replicabilidad del sistema. Para el despliegue, la aplicación frontend será publicada en Vercel, mientras que el backend será alojado en un servidor compatible con PHP.
El desarrollo contemplará buenas prácticas en términos de seguridad, validación de datos, accesibilidad, usabilidad y organización del código, con el objetivo de construir una solución escalable, mantenible y alineada con estándares actuales de desarrollo web.

## Stack de tecnologias

### Backend
- Laravel (PHP)
- API REST + MVC (Blade para admin)

### Base de Datos
- PostgreSQL

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
- Frontend: Vercel
- Backend: Servidor con soporte PHP (ej: VPS, Render, Railway)

### Herramientas de desarrollo
- Visual Studio Code
- Git
- Node.js

## Requisitos Funcionales (RF)

### 1. Gestión de Usuarios
- RF1: El sistema deberá permitir el registro de usuarios clientes.
- RF2: El sistema deberá permitir el inicio y cierre de sesión.
- RF3: El sistema deberá permitir a un administrador visualizar, editar y bloquear usuarios.
- RF4: El sistema deberá diferenciar roles de usuario (administrador y cliente).

### 2. Gestión de Categorías y Subcategorías
- RF5: El administrador deberá poder crear, editar y eliminar categorías.
- RF6: El administrador deberá poder crear, editar y eliminar subcategorías.
- RF7: El sistema deberá permitir asociar productos a categorías y subcategorías.

### 3. Gestión de Productos
- RF8: El administrador deberá poder crear, editar y eliminar productos.
- RF9: El sistema deberá permitir definir precio, stock, descripción e imágenes de los productos.
- RF10: El sistema deberá permitir activar o desactivar productos.

### 4. Navegación y Catálogo (Cliente)
- RF11: El usuario deberá poder visualizar el catálogo de productos.
- RF12: El sistema deberá permitir filtrar productos por categoría.
- RF13: El sistema deberá permitir buscar productos por nombre.
- RF14: El usuario deberá poder visualizar el detalle de un producto.

### 5. Carrito de Compras
- RF15: El usuario deberá poder agregar productos al carrito.
- RF16: El usuario deberá poder modificar cantidades en el carrito.
- RF17: El usuario deberá poder eliminar productos del carrito.

### 6. Gestión de Pedidos
- RF18: El sistema deberá permitir generar pedidos a partir del carrito.
- RF19: El sistema deberá registrar los productos incluidos en cada pedido.
- RF20: El administrador deberá poder visualizar los pedidos realizados.
- RF21: El administrador deberá poder actualizar el estado de un pedido.

### 7. Pagos
- RF22: El sistema deberá integrarse con Mercado Pago para procesar pagos.
- RF23: El sistema deberá registrar el estado del pago (pendiente, aprobado, rechazado).
- RF24: El sistema deberá actualizar el estado del pedido en función del resultado del pago.

### 8. Envíos
- RF25: El sistema deberá permitir registrar datos de envío para cada pedido.
- RF26: El administrador deberá poder gestionar el estado de los envíos.

## Requisitos No Funcionales (RNF)

### 1. Rendimiento
- RNF1: El sistema deberá responder a solicitudes en tiempos adecuados (< 3 segundos en operaciones comunes).
- RNF2: El sistema deberá soportar múltiples usuarios concurrentes sin degradación significativa.

### 2. Seguridad
- RNF3: El sistema deberá validar todos los datos ingresados por el usuario.
- RNF4: El sistema deberá protegerse contra ataques comunes (XSS, SQL Injection).
- RNF5: Las contraseñas deberán almacenarse de forma encriptada.
- RNF6: El acceso al panel administrativo deberá estar restringido a usuarios autorizados.

### 3. Usabilidad
- RNF7: La interfaz deberá ser intuitiva y fácil de usar.
- RNF8: El sistema deberá ser responsive (adaptable a dispositivos móviles).

### 4. Disponibilidad
- RNF9: El sistema deberá estar disponible para los usuarios de forma continua, salvo mantenimiento programado.

### 5. Mantenibilidad
- RNF10: El sistema deberá estar estructurado siguiendo el patrón MVC.
- RNF11: El código deberá ser modular y fácilmente extensible.

### 6. Portabilidad
- RNF12: El sistema deberá poder ejecutarse en distintos entornos mediante Docker.

### 7. Compatibilidad
- RNF13: La aplicación deberá ser compatible con navegadores web modernos (Chrome, Firefox, Edge).
