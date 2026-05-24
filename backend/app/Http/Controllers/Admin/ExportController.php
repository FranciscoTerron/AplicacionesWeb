<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CategoriesExport;
use App\Exports\ClientsExport;
use App\Exports\DiscountsExport;
use App\Exports\OrdersExport;
use App\Exports\ProductsExport;
use App\Exports\ShipmentsExport;
use App\Exports\SubcategoriesExport;
use App\Services\FirestoreService;
use Carbon\Carbon;
use League\Csv\Writer;
use Maatwebsite\Excel\Facades\Excel;

class ExportController
{
    protected FirestoreService $firestore;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    private array $exportableEntities = [
        'categories' => 'Categorías',
        'subcategories' => 'Subcategorías',
        'products' => 'Productos',
        'discounts' => 'Descuentos',
        'clients' => 'Clientes',
        'orders' => 'Órdenes',
        'shipments' => 'Envíos',
    ];

    private array $editorExportableEntities = [
        'categories',
        'subcategories',
        'products',
        'discounts',
    ];

    private static array $exportClasses = [
        'categories' => CategoriesExport::class,
        'subcategories' => SubcategoriesExport::class,
        'products' => ProductsExport::class,
        'discounts' => DiscountsExport::class,
        'clients' => ClientsExport::class,
        'orders' => OrdersExport::class,
        'shipments' => ShipmentsExport::class,
    ];

    public function export(string $entity, string $format = 'csv')
    {
        if (! isset($this->exportableEntities[$entity])) {
            abort(404, 'Entidad no exportable.');
        }

        $this->authorizeExport($entity);

        if ($format === 'excel') {
            return $this->exportExcel($entity);
        }

        return $this->exportCsv($entity);
    }

    protected function exportCsv(string $entity)
    {
        $documents = $this->fetchAllDocuments($entity);
        $csv = $this->generateEntityCsv($entity, $documents);

        $filename = $entity.'_'.now()->format('Y-m-d').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function exportExcel(string $entity)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $this->authorizeExport($entity);

        // Check if entity is valid
        if (! isset($this->exportableEntities[$entity])) {
            abort(404, 'Entidad no exportable.');
        }

        $exportClass = self::$exportClasses[$entity] ?? null;
        if (! $exportClass) {
            abort(404, 'Export no configurado.');
        }

        $export = new $exportClass($this->firestore);

        $filename = $entity.'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download($export, $filename);
    }

    protected function authorizeExport(string $entity): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'No autenticado.');
        }

        if ($user->role === 'admin') {
            return;
        }

        if ($user->role === 'editor' && in_array($entity, $this->editorExportableEntities)) {
            return;
        }

        abort(403, 'No tienes permiso para exportar esta entidad.');
    }

    protected function fetchAllDocuments(string $collection): array
    {
        $allDocuments = [];
        $cursor = null;
        $maxIterations = 100;
        $iteration = 0;

        do {
            $result = $this->firestore->fetchForPage($collection, 500, $cursor, 'name', 20);
            $documents = $result['documents'] ?? [];

            // Filter duplicates
            foreach ($documents as $doc) {
                $exists = false;
                foreach ($allDocuments as $existing) {
                    if (($existing['id'] ?? null) === ($doc['id'] ?? null)) {
                        $exists = true;
                        break;
                    }
                }
                if (! $exists) {
                    $allDocuments[] = $doc;
                }
            }

            $cursor = $result['lastDocumentId'] ?? null;
            $iteration++;
        } while ($result['hasMore'] && $iteration < $maxIterations);

        return $allDocuments;
    }

    protected function generateEntityCsv(string $entity, array $documents): string
    {
        $csv = Writer::createFromFileObject(new \SplTempFileObject);

        $headers = $this->getCsvHeaders($entity);
        $csv->insertOne($headers);

        foreach ($documents as $doc) {
            $row = $this->formatDocumentForCsv($entity, $doc);
            $csv->insertOne($row);
        }

        $output = $csv->getContent();

        return "\xEF\xBB\xBF".$output;
    }

    protected function getCsvHeaders(string $entity): array
    {
        return match ($entity) {
            'categories' => ['ID', 'Nombre', 'Slug', 'Descripción', 'Estado', 'Orden', 'Creado', 'Actualizado'],
            'subcategories' => ['ID', 'Nombre', 'Slug', 'Categoría ID', 'Descripción', 'Estado', 'Orden', 'Creado', 'Actualizado'],
            'products' => ['ID', 'Nombre', 'Slug', 'SKU', 'Descripción', 'Precio', 'Stock', 'Estado', 'Categoría ID', 'Subcategoría ID', 'Creado', 'Actualizado'],
            'discounts' => ['ID', 'Nombre', 'Código', 'Tipo', 'Valor', 'Estado', 'Válido Desde', 'Válido Hasta', 'Creado', 'Actualizado'],
            'clients' => ['ID', 'Nombre', 'Email', 'Teléfono', 'Estado', 'Creado', 'Actualizado'],
            'orders' => ['ID', 'Número', 'Cliente ID', 'Total', 'Estado', 'Forma de Pago', 'Creado', 'Actualizado'],
            'shipments' => ['ID', 'Orden ID', 'Número de Seguimiento', 'Estado', 'Transportista', 'Creado', 'Actualizado'],
            default => ['ID'],
        };
    }

    protected function formatDocumentForCsv(string $entity, array $doc): array
    {
        $formatBool = fn ($value) => $value ? 'Activo' : 'Inactivo';
        $formatDate = fn ($value) => $value ? Carbon::parse($value)->format('d/m/Y H:i:s') : '';

        return match ($entity) {
            'categories' => [
                $doc['id'] ?? '',
                $doc['name'] ?? '',
                $doc['slug'] ?? '',
                $doc['description'] ?? '',
                $formatBool($doc['active'] ?? true),
                $doc['order'] ?? '',
                $formatDate($doc['created_at'] ?? null),
                $formatDate($doc['updated_at'] ?? null),
            ],
            'subcategories' => [
                $doc['id'] ?? '',
                $doc['name'] ?? '',
                $doc['slug'] ?? '',
                $doc['category_id'] ?? '',
                $doc['description'] ?? '',
                $formatBool($doc['active'] ?? true),
                $doc['order'] ?? '',
                $formatDate($doc['created_at'] ?? null),
                $formatDate($doc['updated_at'] ?? null),
            ],
            'products' => [
                $doc['id'] ?? '',
                $doc['name'] ?? '',
                $doc['slug'] ?? '',
                $doc['sku'] ?? '',
                $doc['description'] ?? '',
                $doc['price'] ?? '',
                $doc['stock'] ?? '',
                $formatBool($doc['active'] ?? true),
                $doc['category_id'] ?? '',
                $doc['subcategory_id'] ?? '',
                $formatDate($doc['created_at'] ?? null),
                $formatDate($doc['updated_at'] ?? null),
            ],
            'discounts' => [
                $doc['id'] ?? '',
                $doc['name'] ?? '',
                $doc['code'] ?? '',
                $doc['discount_type'] ?? '',
                $doc['value'] ?? '',
                $formatBool($doc['active'] ?? true),
                $formatDate($doc['valid_from'] ?? null),
                $formatDate($doc['valid_to'] ?? null),
                $formatDate($doc['created_at'] ?? null),
                $formatDate($doc['updated_at'] ?? null),
            ],
            'clients' => [
                $doc['id'] ?? '',
                $doc['name'] ?? '',
                $doc['email'] ?? '',
                $doc['phone'] ?? '',
                $formatBool($doc['active'] ?? true),
                $formatDate($doc['created_at'] ?? null),
                $formatDate($doc['updated_at'] ?? null),
            ],
            'orders' => [
                $doc['id'] ?? '',
                $doc['number'] ?? '',
                $doc['client_id'] ?? '',
                $doc['total'] ?? '',
                $doc['status'] ?? '',
                $doc['payment_method'] ?? '',
                $formatDate($doc['created_at'] ?? null),
                $formatDate($doc['updated_at'] ?? null),
            ],
            'shipments' => [
                $doc['id'] ?? '',
                $doc['order_id'] ?? '',
                $doc['tracking_number'] ?? '',
                $doc['status'] ?? '',
                $doc['carrier'] ?? '',
                $formatDate($doc['created_at'] ?? null),
                $formatDate($doc['updated_at'] ?? null),
            ],
            default => [$doc['id'] ?? ''],
        };
    }
}
