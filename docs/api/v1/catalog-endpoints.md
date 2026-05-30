# OpenAPI Documentation

Este archivo documenta los endpoints públicos del catálogo implementados del proyecto.

## Endpoints Documentados

### 1. GET /api/v1/catalog/products

Lista productos públicos activos con filtros opcionales y paginación.

**Query Parameters:**
- `search` (string, opcional) - Busca por nombre, SKU o descripción
- `category` (string, opcional) - ID de categoría para filtrar
- `featured` (boolean, opcional) - Filtra productos destacados
- `min_price` (number, opcional) - Precio mínimo
- `max_price` (number, opcional) - Precio máximo
- `page` (integer, opcional) - Número de página (default: 1)
- `limit` (integer, opcional) - Cantidad por página (default: 20, max: 100)

**Response 200:**
```json
{
  "success": true,
  "message": "Productos encontrados: 5",
  "data": [
    {
      "id": "prod-123",
      "name": "Cloro Líquido 5L",
      "sku": "CLR-5L",
      "price": 2500.00,
      "description": "Cloro de alta pureza",
      "stock": 50,
      "active": true,
      "featured": false,
      "category_id": "cat-1"
    }
  ],
  "meta": {
    "total": 5,
    "page": 1,
    "last_page": 1,
    "per_page": 20
  }
}
```

---

### 2. GET /api/v1/catalog/products/{id}

Obtiene un producto específico por ID. Solo devuelve productos activos.

**Path Parameters:**
- `id` (string, requerido) - ID del producto

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": "prod-123",
    "name": "Cloro Líquido 5L",
    "sku": "CLR-5L",
    "price": 2500.00,
    "description": "Cloro de alta pureza",
    "stock": 50,
    "active": true,
    "featured": false,
    "category_id": "cat-1"
  }
}
```

**Response 404:**
```json
{
  "success": false,
  "message": "Producto no disponible."
}
```

---

### 3. GET /api/v1/catalog/categories

Lista categorías públicas activas.

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": "cat-1",
      "name": "Químicos",
      "description": "Productos químicos para piscinas",
      "active": true,
      "order": 1
    },
    {
      "id": "cat-2",
      "name": "Equipos",
      "description": "Equipos y accesorios",
      "active": true,
      "order": 2
    }
  ]
}
```

---

## Schemas

### Product
```json
{
  "type": "object",
  "properties": {
    "id": { "type": "string", "description": "Firestore document ID" },
    "name": { "type": "string" },
    "sku": { "type": "string" },
    "price": { "type": "number", "format": "decimal" },
    "description": { "type": "string", "nullable": true },
    "stock": { "type": "integer" },
    "active": { "type": "boolean" },
    "featured": { "type": "boolean" },
    "category_id": { "type": "string" },
    "subcategory_id": { "type": "string", "nullable": true },
    "images": { "type": "array", "items": { "type": "object" } }
  }
}
```

### Category
```json
{
  "type": "object",
  "properties": {
    "id": { "type": "string" },
    "name": { "type": "string" },
    "description": { "type": "string", "nullable": true },
    "active": { "type": "boolean" },
    "order": { "type": "integer" },
    "image": {
      "type": "object",
      "properties": {
        "url": { "type": "string" },
        "public_id": { "type": "string" }
      }
    }
  }
}
```

---

### 4. POST /api/v1/orders

Crea una nueva orden desde el frontend. Requiere autenticación con token Bearer.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "items": [
    {
      "product_id": "prod-123",
      "quantity": 2,
      "price": 2500.00
    }
  ],
  "shipping_address": "Calle Falsa 123, Ciudad",
  "payment_method": "cash"
}
```

**Request Fields:**
- `items` (array, requerido) - Lista de productos. Mínimo 1 item.
  - `items[].product_id` (string, requerido) - ID del producto
  - `items[].quantity` (integer, requerido) - Cantidad. Mínimo 1.
  - `items[].price` (number, requerido) - Precio. Mínimo 0.
- `shipping_address` (string, requerido) - Dirección de envío. Máximo 500 caracteres.
- `payment_method` (string, requerido) - Método de pago. Valores: `cash`, `card`, `transfer`.

**Response 201:**
```json
{
  "success": true,
  "message": "Orden creada exitosamente",
  "data": {
    "name": "orders/order-id",
    "fields": {
      "user_id": "user-123",
      "items": [...],
      "shipping_address": "Calle Falsa 123, Ciudad",
      "payment_method": "cash",
      "total_amount": 5000.00,
      "status": "pending",
      "payment_status": "pending",
      "created_at": "2026-05-29T13:00:00.000Z",
      "updated_at": "2026-05-29T13:00:00.000Z"
    }
  },
  "meta": null
}
```

**Response 401:**
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

**Response 422:**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "items": ["The items field is required."],
    "shipping_address": ["The shipping address field is required."]
  }
}
```

---

### 5. GET /api/v1/orders 

Lista órdenes del cliente autenticado con filtros opcionales.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (string, opcional) - Filtrar por estado (ej: `pending`, `processing`, `completed`)
- `date_from` (string, opcional) - Filtrar desde fecha (formato: date, ej: `2026-01-01`)
- `date_to` (string, opcional) - Filtrar hasta fecha (formato: date, ej: `2026-12-31`)

**Response 200:**
```json
{
  "success": true,
  "message": "Órdenes encontradas: 2",
  "data": [
    {
      "user_id": "user-123",
      "items": [
        {
          "product_id": "prod-123",
          "quantity": 2,
          "price": 2500.00
        }
      ],
      "shipping_address": "Calle Falsa 123, Ciudad",
      "payment_method": "cash",
      "total_amount": 5000.00,
      "status": "pending",
      "payment_status": "pending",
      "created_at": "2026-05-29T13:00:00.000Z"
    }
  ]
}
```

**Response 401:**
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

---

### 6. GET /api/v1/orders/{id}

Obtiene el detalle de una orden específica del cliente autenticado.

**Headers:**
```
Authorization: Bearer {token}
```

**Path Parameters:**
- `id` (string, requerido) - ID de la orden (ej: `orders/order-123`)

**Response 200:**
```json
{
  "success": true,
  "message": "Orden encontrada",
  "data": {
    "name": "orders/order-123",
    "fields": {
      "user_id": "user-123",
      "items": [
        {
          "product_id": "prod-123",
          "quantity": 2,
          "price": 2500.00
        }
      ],
      "shipping_address": "Calle Falsa 123, Ciudad",
      "payment_method": "cash",
      "total_amount": 5000.00,
      "status": "pending",
      "payment_status": "pending",
      "created_at": "2026-05-29T13:00:00.000Z"
    }
  }
}
```

**Response 401:**
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

**Response 404:**
```json
{
  "success": false,
  "message": "Orden no encontrada."
}
```

---

### 7. PUT /api/v1/orders/{id}/cancel

Cancela una orden si está en estado `pending` o `confirmed`.

**Headers:**
```
Authorization: Bearer {token}
```

**Path Parameters:**
- `id` (string, requerido) - ID de la orden

**Response 200:**
```json
{
  "success": true,
  "message": "Orden cancelada exitosamente",
  "data": {
    "name": "orders/order-123",
    "fields": {
      "status": "cancelled",
      "updated_at": "2026-05-29T14:00:00.000Z"
    }
  }
}
```

**Response 401:**
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

**Response 400:**
```json
{
  "success": false,
  "message": "La orden no se puede cancelar en su estado actual: processing"
}
```

---

### 8. POST /api/v1/cart

Operaciones de carrito de compras (add, update, remove). Requiere autenticación.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "action": "add",
  "product_id": "prod-123",
  "quantity": 2
}
```

**Request Fields:**
- `action` (string, requerido) - Acción: `add`, `update`, `remove`
- `product_id` (string, requerido) - ID del producto
- `quantity` (integer, opcional) - Cantidad. Default: 1

**Response 200:**
```json
{
  "success": true,
  "message": "Carrito actualizado",
  "data": {
    "name": "carts/cart-id",
    "fields": {
      "user_id": "user-123",
      "items": [
        {"product_id": "prod-123", "quantity": 2}
      ],
      "updated_at": "2026-05-29T14:00:00.000Z"
    }
  }
}
```

**Response 401:**
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

---

### 9. POST /api/v1/discounts/validate

Valida un código de descuento. Verifica existencia, estado activo, fechas de validez y aplicabilidad. Requiere autenticación.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "code": "VERANO20",
  "product_id": "prod-123",
  "category_id": "cat-1"
}
```

**Request Fields:**
- `code` (string, requerido) - Código de descuento. Máximo 50 caracteres.
- `product_id` (string, opcional) - ID del producto para verificar aplicabilidad específica.
- `category_id` (string, opcional) - ID de la categoría para verificar aplicabilidad.

**Response 200:**
```json
{
  "success": true,
  "message": "Código de descuento válido",
  "data": {
    "id": "discount-123",
    "code": "VERANO20",
    "name": "Descuento Verano 20%",
    "description": "20% de descuento en productos seleccionados",
    "discount_type": "percentage",
    "value": 20,
    "applies_to": "product"
  }
}
```

**Response 401:**
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

**Response 404:**
```json
{
  "success": false,
  "message": "Código de descuento no encontrado."
}
```

---

### 10. GET /api/v1/catalog/featured

Lista productos destacados activos.

**Response 200:**
```json
{
  "success": true,
  "message": "Productos destacados encontrados: 3",
  "data": [
    {
      "id": "prod-123",
      "name": "Cloro Líquido 5L",
      "sku": "CLR-5L",
      "price": 2500.00,
      "description": "Cloro de alta pureza",
      "stock": 50,
      "active": true,
      "featured": true,
      "category_id": "cat-1"
    }
  ]
}
```

---

### 11. POST /api/v1/catalog/search

Búsqueda avanzada de productos con filtros múltiples.

**Request Body:**
```json
{
  "query": "cloro",
  "category_id": "cat-1",
  "min_price": 1000,
  "max_price": 5000,
  "min_rating": 4,
  "in_stock": true,
  "sort_by": "price",
  "sort_order": "asc"
}
```

**Request Fields:**
- `query` (string, opcional) - Término de búsqueda full-text
- `category_id` (string, opcional) - ID de categoría
- `min_price` (number, opcional) - Precio mínimo
- `max_price` (number, opcional) - Precio máximo
- `min_rating` (number, opcional) - Rating mínimo (0-5)
- `in_stock` (boolean, opcional) - Solo productos con stock > 0
- `attributes` (object, opcional) - Filtros por atributos
- `sort_by` (string, opcional) - Campo de ordenamiento: `price`, `name`, `rating`, `created_at`
- `sort_order` (string, opcional) - Dirección: `asc`, `desc`

**Response 200:**
```json
{
  "success": true,
  "message": "Productos encontrados: 5",
  "data": [
    {
      "id": "prod-123",
      "name": "Cloro Líquido 5L",
      "sku": "CLR-5L",
      "price": 2500.00,
      "rating": 4.5,
      "stock": 50,
      "active": true,
      "featured": true
    }
  ]
}
```

---

### 12. GET /api/v1/cart

Obtiene el carrito del usuario autenticado.

**Headers:**
```
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Carrito obtenido",
  "data": {
    "name": "carts/cart-id",
    "fields": {
      "user_id": "user-123",
      "items": [
        {"product_id": "prod-123", "quantity": 2}
      ],
      "created_at": "2026-05-29T13:00:00.000Z",
      "updated_at": "2026-05-29T13:00:00.000Z"
    }
  }
}
```

**Response 401:**
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

---

### 13. GET /api/v1/wishlist

Obtiene la lista de deseos del usuario autenticado.

**Headers:**
```
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Wishlist obtenida",
  "data": {
    "name": "wishlists/wishlist-id",
    "fields": {
      "user_id": "user-123",
      "items": ["prod-123", "prod-456"]
    }
  }
}
```

---

### 14. POST /api/v1/wishlist

Agrega un producto a la lista de deseos del usuario.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "product_id": "prod-123"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Producto agregado a wishlist",
  "data": {
    "name": "wishlists/wishlist-id",
    "fields": {
      "user_id": "user-123",
      "items": ["prod-123"]
    }
  }
}
```

---

### 15. DELETE /api/v1/wishlist/{id}

Elimina un producto de la lista de deseos del usuario.

**Headers:**
```
Authorization: Bearer {token}
```

**Path Parameters:**
- `id` (string, requerido) - ID del producto a eliminar

**Response 200:**
```json
{
  "success": true,
  "message": "Producto eliminado de wishlist"
}
```

---

### 16. POST /api/v1/payments/webhook

Webhook para Mercado Pago que actualiza el estado de pago de órdenes. Este endpoint NO requiere autenticación y está excluido del rate limiting.

**Headers:**
```
x-signature-sha256: {hmac_signature}
Content-Type: application/json
```

**Request Body (Mercado Pago):**
```json
{
  "type": "payment",
  "data": {
    "id": "payment-id-123"
  }
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Pago actualizado: approved"
}
```

**Response 400:**
```json
{
  "success": false,
  "message": "Firma inválida"
}
```

---

## Rate Limiting

Todos los endpoints públicos están protegidos con rate limiting: **60 requests por minuto** por IP.

Los endpoints autenticados heredan este límite. El webhook de Mercado Pago está excluido.

---

## Testing

Para probar los endpoints:

```bash
# Iniciar servidor
php artisan serve

# Probar endpoints con curl
curl http://localhost:8000/api/v1/catalog/products
curl "http://localhost:8000/api/v1/catalog/products?featured=true&min_price=1000&max_price=5000"
curl http://localhost:8000/api/v1/catalog/products/prod-123
curl http://localhost:8000/api/v1/catalog/categories

# Crear orden (requiere token)
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [{"product_id": "prod-123", "quantity": 1, "price": 2500}],
    "shipping_address": "Calle 123",
    "payment_method": "cash"
  }'

# Listar órdenes (requiere token)
curl http://localhost:8000/api/v1/orders \
  -H "Authorization: Bearer $TOKEN"

# Filtrar órdenes (requiere token)
curl "http://localhost:8000/api/v1/orders?status=pending&date_from=2026-01-01" \
  -H "Authorization: Bearer $TOKEN"

# Obtener orden por ID (requiere token)
curl http://localhost:8000/api/v1/orders/order-id \
  -H "Authorization: Bearer $TOKEN"

# Cancelar orden (requiere token)
curl -X PUT http://localhost:8000/api/v1/orders/order-id/cancel \
  -H "Authorization: Bearer $TOKEN"

# Operaciones de carrito (requiere token)
curl -X POST http://localhost:8000/api/v1/cart \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"action": "add", "product_id": "prod-123", "quantity": 2}'

# Validar descuento (requiere token)
curl -X POST http://localhost:8000/api/v1/discounts/validate \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"code": "VERANO20"}'

# Validar descuento con producto específico
curl -X POST http://localhost:8000/api/v1/discounts/validate \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"code": "VERANO20", "product_id": "prod-123"}'

# Productos destacados
curl http://localhost:8000/api/v1/catalog/featured

# Búsqueda avanzada
curl -X POST http://localhost:8000/api/v1/catalog/search \
  -H "Content-Type: application/json" \
  -d '{"query": "cloro", "min_price": 1000}'

# Obtener carrito
curl http://localhost:8000/api/v1/cart \
  -H "Authorization: Bearer $TOKEN"

# Wishlist
curl http://localhost:8000/api/v1/wishlist \
  -H "Authorization: Bearer $TOKEN"

curl -X POST http://localhost:8000/api/v1/wishlist \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"product_id": "prod-123"}'

curl -X DELETE http://localhost:8000/api/v1/wishlist/prod-123 \
  -H "Authorization: Bearer $TOKEN"
```

# Acceder a documentación interactiva
```bash
open http://localhost:8000/docs/api
```
