<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Log;
use Throwable;

class CloudinaryService
{
    protected ?Cloudinary $client = null;

    public function __construct()
    {
        $cloudName = config('cloudinary.cloud_name');
        $apiKey = config('cloudinary.api_key');
        $apiSecret = config('cloudinary.api_secret');

        if ($cloudName && $apiKey && $apiSecret) {
            $this->client = new Cloudinary([
                'cloud' => [
                    'cloud_name' => $cloudName,
                    'api_key' => $apiKey,
                    'api_secret' => $apiSecret,
                ],
            ]);
        }
    }

    /**
     * Borra un asset de Cloudinary por su public_id.
     * No-op si Cloudinary no está configurado (útil en tests).
     */
    public function deleteAsset(string $publicId): void
    {
        if (! $this->client || $publicId === '') {
            return;
        }

        try {
            $this->client->adminApi()->deleteAssets([$publicId]);
        } catch (Throwable $e) {
            Log::warning('Cloudinary deleteAsset failed', [
                'public_id' => $publicId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Borra varios assets en una sola llamada.
     *
     * @param  array<int,string>  $publicIds
     */
    public function deleteAssets(array $publicIds): void
    {
        $publicIds = array_values(array_filter($publicIds, fn (string $id) => $id !== ''));
        if (! $this->client || $publicIds === []) {
            return;
        }

        try {
            // Cloudinary Admin API recomienda batches de hasta 100 public_ids.
            foreach (array_chunk($publicIds, 100) as $chunk) {
                $this->client->adminApi()->deleteAssets($chunk);
            }
        } catch (Throwable $e) {
            Log::warning('Cloudinary deleteAssets failed', [
                'count' => count($publicIds),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
