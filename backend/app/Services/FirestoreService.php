<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Exception;

class FirestoreService
{
    protected string $projectId;
    protected string $baseUrl;
    protected string $accessToken;
    
    public function __construct()
    {
        $this->projectId = $this->resolveProjectId();
        
        if (!$this->projectId) {
            throw new Exception('Firebase project ID not configured. Set FIRESTORE_PROJECT_ID in .env or ensure credentials file has project_id');
        }
        
        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
        $this->accessToken = $this->fetchAccessToken();
    }
    
    protected function resolveProjectId(): ?string
    {
        // 1. Try environment variable directly
        $projectId = $_ENV['FIRESTORE_PROJECT_ID'] ?? null;
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
        $credentialsJson = env('FIRESTORE_CREDENTIALS_JSON');
        $credentialsArray = null;
        
        if ($credentialsJson) {
            $credentialsArray = json_decode($credentialsJson, true);
        } else {
            $path = storage_path('app/private/firebase-service-account.json');
            if (!file_exists($path)) {
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
                        'value' => $this->encodeValue($fieldValue)
                    ]
                ],
                'limit' => $limit
            ]
        ];
        
        $response = Http::withToken($this->accessToken)->post($url, $structured);
        
        if ($response->failed()) {
            throw new Exception('Firestore query failed: ' . $response->body());
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
            throw new Exception('Firestore create failed: ' . $response->body());
        }
        
        $result = $response->json();
        $docName = $result['name'] ?? '';
        $parts = explode('/', $docName);
        $id = end($parts);
        
        return $this->getDocument($collection, $id);
    }
    
    public function updateDocument(string $collection, string $docId, array $data): array
    {
        $url = "{$this->baseUrl}/{$collection}/{$docId}?updateMask.fieldPaths=" . implode(',', array_keys($data));
        $body = ['fields' => $this->encodeFields($data)];
        $response = Http::withToken($this->accessToken)->patch($url, $body);
        
        if ($response->failed()) {
            throw new Exception('Firestore update failed: ' . $response->body());
        }
        
        return $this->getDocument($collection, $docId);
    }
    
    public function deleteDocument(string $collection, string $docId): void
    {
        $url = "{$this->baseUrl}/{$collection}/{$docId}";
        Http::withToken($this->accessToken)->delete($url);
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
            $arrayValues = [];
            foreach ($value as $v) {
                $arrayValues[] = $this->encodeValue($v);
            }
            return ['arrayValue' => ['values' => $arrayValues]];
        }
        return ['stringValue' => (string)$value];
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
        if (isset($field['stringValue'])) return $field['stringValue'];
        if (isset($field['integerValue'])) return (int)$field['integerValue'];
        if (isset($field['doubleValue'])) return (float)$field['doubleValue'];
        if (isset($field['booleanValue'])) return (bool)$field['booleanValue'];
        if (isset($field['nullValue'])) return null;
        if (isset($field['arrayValue'])) {
            $values = $field['arrayValue']['values'] ?? [];
            return array_map([$this, 'decodeValue'], $values);
        }
        if (isset($field['mapValue'])) {
            return $this->decodeValue($field['mapValue']);
        }
        if (isset($field['timestampValue'])) return $field['timestampValue'];
        return null;
    }
}