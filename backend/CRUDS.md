# CRUDs del Proyecto

## Resumen

| Recurso | Errors | Requests | Controller | Vistas | Tests |
|---------|--------|----------|-------------|--------|-------|
| Categories | ✅ | ✅ | ✅ | ✅ | ✅ |
| Products | ✅ | ✅ | ✅ | ✅ | ✅ |
| Discounts | ✅ | ✅ | ✅ | ✅ | ✅ |
| Subcategories | ✅ | ✅ | ✅ | ✅ | ✅ |
| Clients | ✅ | ✅ | ✅ | ✅ | ✅ |
| Orders | ✅ | ✅ | ✅ | ✅ | ✅ |
| Users | ❌ | ✅ | ✅ | ✅ | ✅ |

---

## Estructura de Archivos

### Domain Errors
```
app/Domain/Errors/
├── DomainError.php        # Clase base
├── CategoryErrors.php
├── ProductErrors.php
├── DiscountErrors.php
├── SubcategoryErrors.php
├── ClientErrors.php
└── OrderErrors.php
```

### Form Requests
```
app/Http/Requests/
├── Category/
│   ├── StoreCategoryRequest.php
│   └── UpdateCategoryRequest.php
├── Product/
│   ├── StoreProductRequest.php
│   └── UpdateProductRequest.php
├── Discount/
│   ├── StoreDiscountRequest.php
│   └── UpdateDiscountRequest.php
├── Subcategory/
│   ├── StoreSubcategoryRequest.php
│   └── UpdateSubcategoryRequest.php
├── Client/
│   ├── StoreClientRequest.php
│   └── UpdateClientRequest.php
├── Order/
│   ├── StoreOrderRequest.php
│   └── UpdateOrderRequest.php
├── StoreUserRequest.php
└── UpdateUserRequest.php
```

### Controllers
```php
// Todos usan CrudActionsTrait
CategoryController.php
ProductController.php
DiscountController.php
SubcategoryController.php
ClientController.php
OrderController.php
```

### Vistas Blade
```
resources/views/admin/
├── categories/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── show.blade.php
│   └── _form_fields.blade.php
├── products/        # mismo patrón
├── discounts/      # mismo patrón
├── subcategories/  # mismo patrón
├── clients/       # mismo patrón
└── orders/        # mismo patrón
```

---

## Patrón CRUD Reutilizable

### Trait: CrudActionsTrait
```php
use App\Http\Traits\CrudActionsTrait;

class MiController extends Controller
{
    use CrudActionsTrait;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    protected function getCollectionName(): string
    {
        return 'mi_coleccion';
    }

    protected function getRedirectRoute(): string
    {
        return 'admin.mi_coleccion.index';
    }

    protected function getViewFolder(): string
    {
        return 'admin.mi_coleccion';
    }

    protected function getStoreRequestClass(): string
    {
        return StoreMiRequest::class;
    }

    protected function getUpdateRequestClass(): string
    {
        return UpdateMiRequest::class;
    }

    // Opcional: datos extra para create/edit
    protected function getExtraCreateData(): array
    {
        return ['categorias' => $this->firestore->listDocuments('categories', 100)];
    }
}
```

---

## Tests

### Estructura de Test
```php
use Tests\TestCase;

class MiControllerTest extends TestCase
{
    protected FirestoreService $firestoreMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->firestoreMock = $this->createMock(FirestoreService::class);
        $this->app->instance(FirestoreService::class, $this->firestoreMock);
    }

    protected function mockAuthUser(string $role): void
    {
        $authUser = \Mockery::mock();
        $authUser->role = $role;
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('check')->andReturn(true);
    }
}
```

### Tests Obligatorios
- `test_index_returns_200()`
- `test_create_returns_view()`
- `test_store_creates_with_valid_data()`
- `test_store_fails_with_invalid_data()`
- `test_show_returns_details()`
- `test_edit_returns_view()`
- `test_update_modifies()`
- `test_destroy_deletes()`

---

## Rutas

```php
Route::resource('categories', CategoryController::class)->names('admin.categories');
```

Genera automáticamente:
| Método | Ruta | Acción |
|--------|------|--------|
| GET | /admin/categories | index |
| GET | /admin/categories/create | create |
| POST | /admin/categories | store |
| GET | /admin/categories/{id} | show |
| GET | /admin/categories/{id}/edit | edit |
| PUT | /admin/categories/{id} | update |
| DELETE | /admin/categories/{id} | destroy |

---

## Buenas Prácticas

1. **Domain Errors** — errores específicos por recurso
2. **Form Requests** — validación enRequest separado
3. **CRUD Trait** — reutilizar lógica común
4. **_form_fields** — campos reutilizables en create/edit
5. **Tests** — uno por acción del CRUD
6. **Commits separados** — cada recurso en commit propio