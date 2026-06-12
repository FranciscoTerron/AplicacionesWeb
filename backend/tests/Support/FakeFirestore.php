<?php

namespace Tests\Support;

use App\Services\FirestoreService;

/**
 * Doble de prueba in-memory de FirestoreService.
 *
 * Reemplaza las llamadas HTTP reales a Firestore por un store en memoria,
 * replicando la semántica de los métodos públicos que consumen los
 * controllers de la API. Permite escribir tests de integración del
 * happy-path autenticado sin credenciales ni red.
 *
 * Uso:
 *   $fake = new FakeFirestore;
 *   $fake->seed('products', [['id' => 'p1', 'name' => 'Cloro', 'active' => true]]);
 *   $this->app->instance(FirestoreService::class, $fake);
 */
class FakeFirestore extends FirestoreService
{
    /** @var array<string, array<string, array<string, mixed>>> */
    protected array $store = [];

    protected int $counter = 0;

    /**
     * Override del constructor: no inicializa cliente HTTP ni credenciales.
     */
    public function __construct() {}

    public function getDocument(string $collection, string $docId): ?array
    {
        return $this->store[$collection][$docId] ?? null;
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<int, array<string, mixed>>
     */
    public function query(string $collection, array $fields, int $limit = 1): array
    {
        $field = array_key_first($fields);
        $value = $fields[$field];

        $matches = [];
        foreach ($this->store[$collection] ?? [] as $doc) {
            if (($doc[$field] ?? null) === $value) {
                $matches[] = $doc;
                if (count($matches) >= $limit) {
                    break;
                }
            }
        }

        return $matches;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createDocument(string $collection, array $data): array
    {
        $id = (string) ($data['id'] ?? $this->generateId());
        $data['id'] = $id;
        $this->store[$collection][$id] = $data;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createDocumentWithId(string $collection, string $docId, array $data): array
    {
        $data['id'] = $docId;
        $this->store[$collection][$docId] = $data;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateDocument(string $collection, string $docId, array $data): array
    {
        $existing = $this->store[$collection][$docId] ?? ['id' => $docId];
        $merged = array_merge($existing, $data);
        $merged['id'] = $docId;
        $this->store[$collection][$docId] = $merged;

        return $merged;
    }

    public function deleteDocument(string $collection, string $docId): void
    {
        unset($this->store[$collection][$docId]);
    }

    /**
     * @return array{documents: array<int, array<string, mixed>>, hasMore: bool, lastDocumentId: null, nextPageToken: null}
     */
    public function listDocuments(string $collection, int $limit = 20, ?string $startAfter = null, string $orderBy = 'name'): array
    {
        $docs = array_slice(array_values($this->store[$collection] ?? []), 0, $limit);

        return [
            'documents' => $docs,
            'hasMore' => false,
            'lastDocumentId' => null,
            'nextPageToken' => null,
        ];
    }

    public function countDocuments(string $collection): int
    {
        return count($this->store[$collection] ?? []);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countDocumentsWithQuery(string $collection, array $filters = []): int
    {
        if ($filters === []) {
            return $this->countDocuments($collection);
        }

        $field = array_key_first($filters);
        $value = $filters[$field];

        return count(array_filter(
            $this->store[$collection] ?? [],
            fn (array $doc) => ($doc[$field] ?? null) === $value
        ));
    }

    // --- Helpers de test ---

    /**
     * Precarga documentos en una colección. Asigna id si falta.
     *
     * @param  array<int, array<string, mixed>>  $documents
     */
    public function seed(string $collection, array $documents): void
    {
        foreach ($documents as $doc) {
            $id = (string) ($doc['id'] ?? $this->generateId());
            $doc['id'] = $id;
            $this->store[$collection][$id] = $doc;
        }
    }

    /**
     * Devuelve todos los documentos de una colección (para aserciones).
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(string $collection): array
    {
        return array_values($this->store[$collection] ?? []);
    }

    protected function generateId(): string
    {
        $this->counter++;

        return 'fake-'.$this->counter;
    }
}
