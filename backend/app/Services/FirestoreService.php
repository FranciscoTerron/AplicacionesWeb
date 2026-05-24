<?php

namespace App\Services;

use Exception;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

class FirestoreService
{
    protected string $projectId;

    protected string $baseUrl;

    protected string $accessToken;

    public function __construct()
    {
        $this->projectId = $this->resolveProjectId();

        if (! $this->projectId) {
            throw new Exception('Firebase project ID not configured. Set FIRESTORE_PROJECT_ID in .env or ensure credentials file has project_id');
        }

        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
        $this->accessToken = $this->fetchAccessToken();
    }

    protected function resolveProjectId(): ?string
    {
        // 1. Try config (mapped from env)
        $projectId = config('app.firestore_project_id');
        if ($projectId) {
            return $projectId;
        }

        // 2. Try config (when config not cached)
        $projectId = config('firebase.projects.app.project_id');
        if ($projectId) {
            return $projectId;
        }

        // 3. Try to read from credentials file
        $path = storage_path('app/private/firebase-service-account.json');
        if (file_exists($path)) {
            $json = json_decode(file_get_contents($path), true);
            if (isset($json['project_id'])) {
                return $json['project_id'];
            }
        }

        return null;
    }

    protected function fetchAccessToken(): string
    {
        $credentialsArray = null;
        $path = null;

        // 1. Try config FIRESTORE_CREDENTIALS_JSON (JSON string) - Vercel
        $firestoreCredentialsJson = config('app.firestore_credentials_json');
        if ($firestoreCredentialsJson) {
            $maybeArray = json_decode($firestoreCredentialsJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($maybeArray)) {
                $credentialsArray = $maybeArray;
            }
        }

        // 2. Try config FIREBASE_CREDENTIALS_JSON (JSON string) - backward compatibility
        if (! $credentialsArray) {
            $firebaseCredentialsJson = config('app.firebase_credentials_json');
            if ($firebaseCredentialsJson) {
                $maybeArray = json_decode($firebaseCredentialsJson, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($maybeArray)) {
                    $credentialsArray = $maybeArray;
                }
            }
        }

        // 3. Try config FIRESTORE_KEY_FILE (path to credentials file)
        if (! $credentialsArray) {
            $keyFile = config('app.firestore_key_file');
            if ($keyFile && file_exists($keyFile)) {
                $path = $keyFile;
            }
        }

        // 4. Try to get credentials from Firebase config (might be JSON string or path)
        if (! $path && ! $credentialsArray) {
            $credentials = config('firebase.projects.app.credentials');
            if ($credentials) {
                if (is_string($credentials)) {
                    // If it's a string, try to parse as JSON
                    $maybeArray = json_decode($credentials, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($maybeArray)) {
                        $credentialsArray = $maybeArray;
                    } elseif (file_exists($credentials)) {
                        // It's a string and points to an existing file
                        $path = $credentials;
                    }
                } elseif (is_array($credentials)) {
                    $credentialsArray = $credentials;
                }
            }
        }

        // 5. Fallback to the old config key (JSON string)
        if (! $path && ! $credentialsArray) {
            $credentialsJson = config('app.firestore_credentials_json');
            if ($credentialsJson) {
                $credentialsArray = json_decode($credentialsJson, true);
            }
        }

        // 6. Fallback to default path
        if (! $path && ! $credentialsArray) {
            $path = storage_path('app/private/firebase-service-account.json');
            if (! file_exists($path)) {
                throw new Exception("Firebase credentials file not found at: {$path}");
            }
        }

        $cred = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/datastore',
            $credentialsArray ?? $path
        );

        $token = $cred->fetchAuthToken();

        return $token['access_token'] ?? '';
    }

    public function getDocument(string $collection, string $docId): ?array
    {
        $url = "{$this->baseUrl}/{$collection}/{$docId}";
        $response = Http::withToken($this->accessToken)->get($url);
        if ($response->status() === 404) {
            return null;
        }

        return $this->parseDocument($response->json());
    }

    public function query(string $collection, array $fields, int $limit = 1): array
    {
        $url = "{$this->baseUrl}/:runQuery";

        $fieldName = array_keys($fields)[0];
        $fieldValue = reset($fields);

        $structured = [
            'structuredQuery' => [
                'from' => [['collectionId' => $collection]],
                'where' => [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => $fieldName],
                        'op' => 'EQUAL',
                        'value' => $this->encodeValue($fieldValue),
                    ],
                ],
                'limit' => $limit,
            ],
        ];

        $response = Http::withToken($this->accessToken)->post($url, $structured);

        if ($response->failed()) {
            throw new Exception('Firestore query failed: '.$response->body());
        }

        $results = $response->json();
        $documents = [];
        foreach ($results as $result) {
            if (isset($result['document'])) {
                $documents[] = $this->parseDocument($result['document']);
            }
        }

        return $documents;
    }

    public function createDocument(string $collection, array $data): array
    {
        $url = "{$this->baseUrl}/{$collection}";
        $body = ['fields' => $this->encodeFields($data)];
        $response = Http::withToken($this->accessToken)->post($url, $body);

        if ($response->failed()) {
            throw new Exception('Firestore create failed: '.$response->body());
        }

        $result = $response->json();
        $docName = $result['name'] ?? '';
        $parts = explode('/', $docName);
        $id = end($parts);

        return $this->getDocument($collection, $id);
    }

    public function updateDocument(string $collection, string $docId, array $data): array
    {
        $queryParams = [];
        foreach (array_keys($data) as $field) {
            $queryParams[] = 'updateMask.fieldPaths='.urlencode($field);
        }
        $url = "{$this->baseUrl}/{$collection}/{$docId}?".implode('&', $queryParams);
        $body = ['fields' => $this->encodeFields($data)];
        $response = Http::withToken($this->accessToken)->patch($url, $body);

        if ($response->failed()) {
            throw new Exception('Firestore update failed: '.$response->body());
        }

        return $this->getDocument($collection, $docId);
    }

    public function deleteDocument(string $collection, string $docId): void
    {
        $url = "{$this->baseUrl}/{$collection}/{$docId}";
        Http::withToken($this->accessToken)->delete($url);
    }

    /**
     * Count the total number of documents in a collection.
     */
    public function countDocuments(string $collection): int
    {
        $url = "{$this->baseUrl}/{$collection}:runAggregationQuery";

        $body = [
            'structuredAggregationQuery' => [
                'aggregations' => [
                    ['count' => new \stdClass],
                ],
                'structuredQuery' => [
                    'from' => [['collectionId' => $collection]],
                ],
            ],
        ];

        $response = Http::withToken($this->accessToken)->post($url, $body);

        if ($response->failed()) {
            throw new Exception('Firestore count query failed: '.$response->body());
        }

        $result = $response->json();
        $count = $result['aggregationResults'][0]['aggregateProperties']['integerValue'] ?? '0';

        return (int) $count;
    }

    /**
     * Count documents that would match a structured query.
     */
    public function countDocumentsWithQuery(string $collection, array $filters = []): int
    {
        $url = "{$this->baseUrl}/:runAggregationQuery";

        $structuredQuery = [
            'from' => [['collectionId' => $collection]],
        ];

        if ($filters) {
            $structuredQuery['where'] = [
                'compositeFilter' => [
                    'op' => 'AND',
                    'filters' => [],
                ],
            ];
            foreach ($filters as $field => $value) {
                $structuredQuery['where']['compositeFilter']['filters'][] = [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => $field],
                        'op' => 'EQUAL',
                        'value' => $this->encodeValue($value),
                    ],
                ];
            }
            /** @phpstan-ignore-next-line filters initialised as [] so can be empty at runtime */
            if (empty($structuredQuery['where']['compositeFilter']['filters'])) {
                unset($structuredQuery['where']);
            }
        }

        $body = [
            'structuredAggregationQuery' => [
                'aggregations' => [
                    ['count' => new \stdClass],
                ],
                'structuredQuery' => $structuredQuery,
            ],
        ];

        $response = Http::withToken($this->accessToken)->post($url, $body);

        if ($response->failed()) {
            throw new Exception('Firestore count query failed: '.$response->body());
        }

        $result = $response->json();
        $count = $result['aggregationResults'][0]['aggregateProperties']['integerValue'] ?? '0';

        return (int) $count;
    }

    /**
     * Returns documents with pagination info suitable for server-side filtering.
     * Fetches enough documents so that after client-side filtering there are enough
     * results to fill the requested page. Uses listDocuments internally in a loop.
     */
    public function fetchForPage(
        string $collection,
        int $perPage,
        ?string $startAfter = null,
        string $orderBy = 'name',
        int $maxBatches = 6,
    ): array {
        $fetchLimit = $perPage * $maxBatches;
        $allDocuments = [];
        $cursor = $startAfter;
        $hasMore = false;
        $batch = 0;

        do {
            $limit = min($fetchLimit - count($allDocuments), $perPage * 2);
            if ($limit <= 0) {
                break;
            }

            $result = $this->listDocuments($collection, $limit, $cursor, $orderBy);
            $allDocuments = array_merge($allDocuments, $result['documents']);
            $hasMore = $result['hasMore'];

            // Advance cursor using the ORDER BY field value, not the document ID
            // Firestore requires startAfter value to match the orderBy type/field
            if ($result['hasMore'] && ! empty($result['documents'])) {
                $lastDoc = end($result['documents']);
                $cursor = $lastDoc[$orderBy] ?? $lastDoc['id'] ?? null;
            }

            $batch++;
        } while ($hasMore && $batch < $maxBatches && count($allDocuments) < $fetchLimit);

        return [
            'documents' => $allDocuments,
            'hasMore' => $hasMore,
            'lastDocumentId' => $cursor, // cursor for NEXT fetchForPage call (= last doc in batch, to be excluded)
        ];
    }

    public function listDocuments(string $collection, int $limit = 20, ?string $startAfter = null, string $orderBy = 'name'): array
    {
        // Use structured query for cursor-based pagination
        $url = "{$this->baseUrl}/:runQuery";

        $structuredQuery = [
            'structuredQuery' => [
                'from' => [['collectionId' => $collection]],
                'limit' => $limit + 1, // Fetch one extra to check if there are more pages
                'orderBy' => [
                    ['field' => ['fieldPath' => $orderBy], 'direction' => 'ASCENDING'],
                ],
            ],
        ];

        // Add startAfter cursor if provided (excludes the cursor document)
        if ($startAfter) {
            $structuredQuery['structuredQuery']['startAfter'] = [
                'values' => [$this->encodeValue($startAfter)],
            ];
        }

        \Log::info('Firestore listDocuments called', [
            'collection' => $collection,
            'limit' => $limit,
            'startAfter' => $startAfter,
            'orderBy' => $orderBy,
            'body' => $structuredQuery,
        ]);

        $response = Http::withToken($this->accessToken)->asJson()->post($url, $structuredQuery);

        if ($response->failed()) {
            \Log::error('Firestore listDocuments FAILED', [
                'collection' => $collection,
                'limit' => $limit,
                'startAfter' => $startAfter,
                'status' => $response->status(),
                'body' => $response->body(),
                'query_payload' => $structuredQuery,
            ]);
            throw new Exception('Firestore list documents failed: '.$response->body());
        }

        $results = $response->json();
        $documents = [];
        foreach ($results as $result) {
            if (isset($result['document'])) {
                $documents[] = $this->parseDocument($result['document']);
            }
        }

        // Check if there are more results
        $hasMore = count($documents) > $limit;
        if ($hasMore) {
            array_pop($documents); // Remove the extra document
        }

        // Get the last document's ID for next page cursor
        $lastDocumentId = null;
        if (! empty($documents) && $hasMore) {
            $lastDocumentId = end($documents)['id'] ?? null;
        }

        return [
            'documents' => $documents,
            'hasMore' => $hasMore,
            'lastDocumentId' => $lastDocumentId,
        ];
    }

    protected function encodeFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[$key] = $this->encodeValue($value);
        }

        return $fields;
    }

    protected function encodeValue($value): array
    {
        if (is_int($value)) {
            return ['integerValue' => $value];
        }
        if (is_float($value)) {
            return ['doubleValue' => $value];
        }
        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }
        if (is_null($value)) {
            return ['nullValue' => null];
        }
        if (is_array($value)) {
            // Distinguir arrays indexados (list) de asociativos (map): Firestore no acepta arrays anidados,
            // pero sí arrays-de-maps. Antes mandábamos todo como arrayValue, lo que producía
            // [{"productId":...}] → array de array → "Nested arrays are not allowed".
            if (array_is_list($value)) {
                $arrayValues = [];
                foreach ($value as $v) {
                    $arrayValues[] = $this->encodeValue($v);
                }

                return ['arrayValue' => ['values' => $arrayValues]];
            }

            $fields = [];
            foreach ($value as $k => $v) {
                $fields[(string) $k] = $this->encodeValue($v);
            }

            return ['mapValue' => ['fields' => $fields]];
        }

        return ['stringValue' => (string) $value];
    }

    protected function parseDocument(array $doc): array
    {
        $data = $doc['fields'] ?? [];
        $result = [];
        foreach ($data as $key => $field) {
            $result[$key] = $this->decodeValue($field);
        }
        $pathParts = explode('/', $doc['name'] ?? '');
        $result['id'] = end($pathParts);

        return $result;
    }

    protected function decodeValue(array $field)
    {
        if (isset($field['stringValue'])) {
            return $field['stringValue'];
        }
        if (isset($field['integerValue'])) {
            return (int) $field['integerValue'];
        }
        if (isset($field['doubleValue'])) {
            return (float) $field['doubleValue'];
        }
        if (isset($field['booleanValue'])) {
            return (bool) $field['booleanValue'];
        }
        if (isset($field['nullValue'])) {
            return null;
        }
        if (isset($field['arrayValue'])) {
            $values = $field['arrayValue']['values'] ?? [];

            return array_map([$this, 'decodeValue'], $values);
        }
        if (isset($field['mapValue'])) {
            return $this->decodeValue($field['mapValue']);
        }
        if (isset($field['timestampValue'])) {
            return $field['timestampValue'];
        }

        return null;
    }
}
