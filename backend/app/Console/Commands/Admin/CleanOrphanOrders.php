<?php

namespace App\Console\Commands\Admin;

use App\Services\FirestoreService;
use Illuminate\Console\Command;

/**
 * Borra físicamente las órdenes vacías (sin clientId y sin items)
 * que quedaron creadas mientras CrudActionsTrait::store() inyectaba
 * un FormRequest base sin reglas. Modo dry-run por defecto: usar
 * --force para borrar de verdad.
 */
class CleanOrphanOrders extends Command
{
    protected $signature = 'orders:clean-orphans {--force : Borra realmente; sin la flag solo lista}';

    protected $description = 'Lista (o borra con --force) las órdenes vacías sin cliente ni items.';

    public function handle(FirestoreService $firestore): int
    {
        $force = (bool) $this->option('force');

        $result = $firestore->listDocuments('orders', 500);
        $orders = $result['documents'] ?? [];

        $orphans = array_filter($orders, fn ($o) => empty($o['clientId'])
            && empty($o['client_id'])
            && empty($o['items']));

        if (count($orphans) === 0) {
            $this->info('No hay órdenes huérfanas.');

            return self::SUCCESS;
        }

        $this->warn(sprintf('%d órdenes huérfanas encontradas:', count($orphans)));
        foreach ($orphans as $o) {
            $this->line(sprintf(' - %s (creada %s)', $o['id'] ?? '?', $o['created_at'] ?? '?'));
        }

        if (! $force) {
            $this->newLine();
            $this->comment('Modo dry-run. Para borrarlas: php artisan orders:clean-orphans --force');

            return self::SUCCESS;
        }

        if (! $this->confirm(sprintf('Borrar %d órdenes definitivamente?', count($orphans)))) {
            $this->info('Cancelado.');

            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($orphans as $o) {
            $id = $o['id'] ?? null;
            if (! $id) {
                continue;
            }
            try {
                $firestore->deleteDocument('orders', $id);
                $deleted++;
            } catch (\Throwable $e) {
                $this->error(sprintf('Falló %s: %s', $id, $e->getMessage()));
            }
        }

        $this->info(sprintf('Borradas: %d.', $deleted));

        return self::SUCCESS;
    }
}
