<?php

namespace App\Http\Controllers\Admin;

use App\Services\FirestoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use League\Csv\Reader as CsvReader;

class ImportController
{
    protected FirestoreService $firestore;

    private array $importableEntities = [
        'categories' => 'Categorías',
        'subcategories' => 'Subcategorías',
        'products' => 'Productos',
    ];

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    protected function authorizeAdmin(): void
    {
        $user = auth()->user();
        if (! $user || $user->role !== 'admin') {
            abort(403, 'Solo administradores pueden importar.');
        }
    }

    public function create(string $entity): View|RedirectResponse
    {
        $this->authorizeAdmin();

        if (! isset($this->importableEntities[$entity])) {
            abort(404, 'Entidad no importable.');
        }

        return view('admin.import.create', [
            'entity' => $entity,
            'entityLabel' => $this->importableEntities[$entity],
        ]);
    }

    public function store(Request $request, string $entity): RedirectResponse
    {
        $this->authorizeAdmin();

        if (! isset($this->importableEntities[$entity])) {
            abort(404, 'Entidad no importable.');
        }

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $csv = CsvReader::createFromPath($file->getRealPath(), 'r');
        $csv->setHeaderOffset(0);

        $results = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($csv as $row) {
            $results['total']++;
            $error = $this->importRow($entity, $row);
            if ($error) {
                $results['failed']++;
                $results['errors'][] = $error;
            } else {
                $results['success']++;
            }
        }

        return redirect()->route('admin.import.create', $entity)
            ->with('importResults', $results);
    }

    protected function importRow(string $entity, array $row): ?string
    {
        switch ($entity) {
            case 'categories':
                return $this->importCategory($row);
            case 'subcategories':
                return $this->importSubcategory($row);
            case 'products':
                return $this->importProduct($row);
            default:
                return 'Entidad desconocida';
        }
    }

    protected function importCategory(array $row): ?string
    {
        $validator = Validator::make($row, [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            $name = $row['name'] ?? 'sin nombre';

            return "Categoría '{$name}': ".$validator->errors()->first();
        }

        $existing = $this->firestore->query('categories', ['name' => $row['name']]);
        if (! empty($existing)) {
            return "Categoría '{$row['name']}' ya existe.";
        }

        $this->firestore->createDocument('categories', [
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'active' => $this->parseBoolean($row['active'] ?? true),
            'order' => (int) ($row['order'] ?? 0),
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return null;
    }

    protected function importSubcategory(array $row): ?string
    {
        $validator = Validator::make($row, [
            'name' => 'required|string|max:255',
            'category_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            $name = $row['name'] ?? 'sin nombre';

            return "Subcategoría '{$name}': ".$validator->errors()->first();
        }

        $category = $this->firestore->getDocument('categories', $row['category_id']);
        if (! $category || ! ($category['active'] ?? false)) {
            return "Subcategoría '{$row['name']}': Categoría ID '{$row['category_id']}' no existe o está inactiva.";
        }

        $this->firestore->createDocument('subcategories', [
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'category_id' => $row['category_id'],
            'active' => $this->parseBoolean($row['active'] ?? true),
            'order' => (int) ($row['order'] ?? 0),
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return null;
    }

    protected function importProduct(array $row): ?string
    {
        $validator = Validator::make($row, [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            $name = $row['name'] ?? 'sin nombre';

            return "Producto '{$name}': ".$validator->errors()->first();
        }

        $existing = $this->firestore->query('products', ['sku' => $row['sku']]);
        if (! empty($existing)) {
            return "Producto con SKU '{$row['sku']}' ya existe.";
        }

        $category = $this->firestore->getDocument('categories', $row['category_id']);
        if (! $category || ! ($category['active'] ?? false)) {
            return "Producto '{$row['name']}': Categoría ID '{$row['category_id']}' no existe o está inactiva.";
        }

        $subcategoryId = $row['subcategory_id'] ?? null;
        if ($subcategoryId) {
            $subcategory = $this->firestore->getDocument('subcategories', $subcategoryId);
            if (! $subcategory || ! ($subcategory['active'] ?? false)) {
                return "Producto '{$row['name']}': Subcategoría ID '{$subcategoryId}' no existe o está inactiva.";
            }
        }

        $this->firestore->createDocument('products', [
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'sku' => $row['sku'],
            'price' => (float) $row['price'],
            'stock' => (int) ($row['stock'] ?? 0),
            'active' => $this->parseBoolean($row['active'] ?? true),
            'category_id' => $row['category_id'],
            'subcategory_id' => $subcategoryId,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return null;
    }

    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['1', 'true', 'sí', 'si', 'yes', 'activo'])) {
                return true;
            }
            if (in_array($lower, ['0', 'false', 'no', 'inactivo'])) {
                return false;
            }
        }

        return (bool) $value;
    }
}
