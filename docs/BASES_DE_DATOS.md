# Base de Datos - MA Piscinas

## Motor de Almacenamiento

**Firestore** (Google Cloud Firestore) - Base de datos NoSQL orientada a documentos.

## Estructura de Colecciones

### 1. Colección: `users`

Documentos de usuarios del sistema (panel administrativo).

**Esquema del documento:**
```javascript
{
  id: string (auto-generado por Firestore)
  name: string
  email: string
  password: string (hashed bcrypt)
  role: enum['admin', 'editor']
  active: boolean
  created_at: timestamp
  updated_at: timestamp
  created_by: string (user ID)
  updated_by: string (user ID)
}
```

**Índices necesarios:**
- `email` (único)
- `role`
- `active` + `role` (compuesto)

**Ejemplo de documento:**
```javascript
{
  id: "abc123",
  name: "Juan Pérez",
  email: "juan@example.com",
  password: "$2y$10$...",
  role: "admin",
  active: true,
  created_at: "2026-04-25T10:00:00Z",
  updated_at: "2026-04-25T10:00:00Z",
  created_by: "system",
  updated_by: "system"
}
```

---

### 2. Colección: `categories`

Categorías principales de productos.

**Esquema del documento:**
```javascript
{
  id: string
  name: string
  description: string
  image: string (URL Cloudinary)
  order: integer
  active: boolean
  created_at: timestamp
  updated_at: timestamp
  created_by: string
  updated_by: string
}
```

**Subcolección:** `categories/{categoryId}/subcategories/`

**Esquema subcategoría:**
```javascript
{
  id: string
  category_id: string (referencia a categories)
  name: string
  description: string
  image: string (URL Cloudinary)
  active: boolean
  created_at: timestamp
  updated_at: timestamp
  created_by: string
  updated_by: string
}
```

**Índices:**
- `categories`: `order` (ASC), `active` (ASC)
- `subcategories`: `category_id` (ASC), `active` (ASC)

---

### 3. Colección: `products`

Catálogo de productos/piscinas.

**Esquema del documento:**
```javascript
{
  id: string
  name: string
  description: string
  category_id: string (referencia)
  subcategory_id: string (referencia, opcional)
  sku: string (único)
  price: number
  cost: number (opcional)
  stock: integer
  min_stock: integer
  main_image: string (URL Cloudinary)
  images: array[string] (URLs Cloudinary, max 10)
  featured: boolean
  active: boolean
  dimensions: {
    weight_kg: number
    length_cm: number
    width_cm: number
    height_cm: number
  }
  created_at: timestamp
  updated_at: timestamp
  created_by: string
  updated_by: string
}
```

**Índices:**
- `sku` (único)
- `category_id` + `active` (compuesto)
- `price` + `active` (compuesto)
- `featured` + `active` (compuesto)

---

### 4. Colección: `discounts`

Códigos de descuento promocionales.

**Esquema del documento:**
```javascript
{
  id: string
  code: string (único, mayúsculas)
  name: string
  description: string
  discount_type: enum['percentage', 'fixed']
  value: number
  max_uses: integer (null = infinito)
  used_count: integer
  valid_from: timestamp
  valid_to: timestamp
  active: boolean
  applies_to: enum['all', 'categories', 'products']
  applicable_ids: array[string] (opcional)
  created_at: timestamp
  updated_at: timestamp
  created_by: string
  updated_by: string
}
```

**Índices:**
- `code` (único)
- `active`
- `valid_from` + `valid_to` (compuesto)

**Campo calculado (no guardar, calcular en app):**
```javascript
is_usable = active && 
            (now >= valid_from) && 
            (now <= valid_to) && 
            (max_uses === null || used_count < max_uses)
```

---

### 5. Colección: `orders`

Pedidos/órdenes de compra realizadas por clientes.

**Esquema del documento:**
```javascript
{
  id: string (auto-generado por Firestore)
  client_id: string (referencia a clients)
  user_id: string (referencia a users - usuario que procesa el pedido)
  status: enum['pending', 'confirmed', 'in_process', 'completed', 'cancelled']
  total_amount: number
  payment_method: enum['cash', 'transfer', 'card', 'mercado_pago']
  payment_status: enum['pending', 'paid', 'overdue']
  shipping_address: string
  tracking_number: string (opcional)
  items: array[{
    product_id: string (referencia a products)
    product_name: string (snapshot del nombre al momento del pedido)
    quantity: integer
    unit_price: number
    subtotal: number
  }]
  notes: string (opcional)
  created_at: timestamp
  updated_at: timestamp
  created_by: string
  updated_by: string
}
```

**Índices:**
- `client_id` (ASC)
- `user_id` (ASC)
- `status` (ASC)
- `payment_status` (ASC)
- `created_at` (DESC) - para ordenar pedidos recientes

---

### 6. Colección: `shipments`

Información de envíos/logística para pedidos (pendiente de implementación).

**Esquema planificado:**
```javascript
{
  id: string (auto-generado por Firestore)
  order_id: string (referencia a orders)
  carrier: string (empresa de transporte)
  tracking_number: string
  status: enum['preparing', 'shipped', 'in_transit', 'delivered', 'returned']
  shipping_date: timestamp
  estimated_delivery: timestamp
  actual_delivery: timestamp (opcional)
  shipping_cost: number
  weight_kg: number
  dimensions: {
    length_cm: number
    width_cm: number
    height_cm: number
  }
  created_at: timestamp
  updated_at: timestamp
  created_by: string
  updated_by: string
}
```

**Índices planificados:**
- `order_id` (ASC)
- `status` (ASC)
- `shipping_date` (DESC)

---

## Reglas de Firestore

```rules
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    // Función auxiliar: verifica si el usuario está autenticado
    function isAuthenticated() {
      return request.auth != null;
    }

    // Función: verifica si el usuario es admin
    function isAdmin() {
      return isAuthenticated() && get(/databases/$(database)/documents/users/$(request.auth.uid)).data.role == 'admin';
    }

    // Colección: Usuarios
    match /users/{userId} {
      allow read: if isAuthenticated();
      allow create: if isAuthenticated() && request.auth.uid == userId;
      allow update, delete: if isAdmin();
    }

    // Colección: Categorías (lectura pública para e-commerce)
    match /categories/{categoryId} {
      allow read: if true;
      allow write: if isAdmin();
    }

    // Colección: Subcategorías (lectura pública para e-commerce)
    match /subcategories/{subcategoryId} {
      allow read: if true;
      allow write: if isAdmin();
    }

    // Colección: Productos (lectura pública para e-commerce)
    match /products/{productId} {
      allow read: if true;
      allow write: if isAdmin();
    }

    // Colección: Descuentos (lectura pública para e-commerce)
    match /discounts/{discountId} {
      allow read: if true;
      allow write: if isAdmin();
    }

    // Colección: Pedidos (solo usuarios autenticados)
    match /orders/{orderId} {
      allow read: if isAuthenticated() && (request.auth.uid == resource.data.userId || isAdmin());
      allow create: if isAuthenticated() && request.auth.uid == request.resource.data.userId;
      allow update, delete: if isAdmin();
    }

    // Colección: Envíos (solo usuarios autenticados)
    match /shipments/{shipmentId} {
      allow read: if isAuthenticated();
      allow write: if isAdmin();
    }
  }
}
```

*Nota: Las reglas permiten lecturas públicas para datos del e-commerce (categorías, subcategoria, productos, descuentos, clientes, pedidos, envios) mientras que los datos sensibles (usuarios) requieren autenticación. La administración requiere permisos de admin.*

---

## Operaciones Comunes

### Consultar productos con filtros

```php
// Filtrar por categoría y estado activo
$products = $firestore->query('products', [
    'category_id' => $categoryId,
    'active' => true
]);

// Ordenar por precio
$products = $firestore->query('products', [
    'orderBy' => ['field' => 'price', 'direction' => 'ASC'],
    'limit' => 20
]);
```

### Verificar stock

```php
$product = $firestore->getDocument('products', $productId);
if ($product['stock'] <= $product['min_stock']) {
    // Alerta de stock bajo
}
```

### Aplicar descuento

```php
$discount = $firestore->getDocument('discounts', $code);
if ($discount && $discount['active'] && $discount['used_count'] < $discount['max_uses']) {
    // Calcular precio final
    if ($discount['discount_type'] === 'percentage') {
        $finalPrice = $originalPrice * (1 - $discount['value'] / 100);
    } else {
        $finalPrice = $originalPrice - $discount['value'];
    }
}
```

---

## Transacciones

Para operaciones que requieren consistencia (ej: decrementar stock):

```php
use Google\Cloud\Firestore\FirestoreClient;

$firestore = new FirestoreClient([
    'projectId' => env('FIRESTORE_PROJECT_ID'),
]);

$firestore->runTransaction(function ($transaction) use ($productId) {
    $productRef = $firestore->collection('products')->document($productId);
    $snapshot = $transaction->snapshot($productRef);
    
    if ($snapshot['stock'] > 0) {
        $transaction->update($productRef, [
            ['path' => 'stock', 'value' => $snapshot['stock'] - 1]
        ]);
        return true;
    }
    return false;
});
```

---

## Backup y Restauración

### Exportar colección

```bash
gcloud firestore export gs://[BUCKET_NAME]/backup-$(date +%Y%m%d)
```

### Importar colección

```bash
gcloud firestore import gs://[BUCKET_NAME]/backup-$(date +%Y%m%d)
```

---

## Consideraciones de Rendimiento

1. **Evitar operaciones costosas**: Firestore cobra por lectura de documentos
2. **Usar índices compuestos**: Para queries con múltiples condiciones
3. **Limitar arrays**: Los arrays pueden crecer, pero limitar a un tamaño razonable
4. **Batch writes**: Agrupar operaciones cuando sea posible
5. **Cachear consultas frecuentes**: Usar Redis para queries repetitivas

---

## Migración desde SQL

**Diferencias clave:**
- ❌ No hay JOINs (hacer múltiples queries o duplicar datos)
- ❌ No hay transacciones multi-colección (solo dentro de misma colección)
- ✅ Escalabilidad automática
- ✅ Baja latencia global
- ✅ Schema flexible

**Estrategia:**
- Duplicar datos cuando sea necesario para evitar múltiples lecturas
- Usar referencias por ID para relaciones 1:N
- Planear consultas antes de diseñar el schema

---

*Última actualización: 2026-04-25*