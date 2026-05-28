<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ApiResponseResource
 *
 * Formatea de forma estandar todas las respuestas JSON de la API.
 * Se puede usar en cualquier endpoint futuro: Producto, Carrito, Pedido, etc.
 *
 * Estructura output:
 * {
 *   "success": true,
 *   "message": "...",
 *   "data": { ... }  ← objeto parseado por ->only('campo1','campo2') o un array de otro的资源
 * }
 */
class ApiResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
