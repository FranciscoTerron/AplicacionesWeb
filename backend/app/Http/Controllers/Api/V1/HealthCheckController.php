<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\HealthCheckRequest;
use App\Services\HealthCheckService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    /**
     * GET /api/v1/health
     *
     * Delgado: delega toda la logica al servicio.
     * Usa resolve() para garantizar que se toma el binding del contenedor,
     * lo que permite sobreescritura en tests via $this->app->instance().
     */
    public function __invoke(HealthCheckRequest $request): JsonResponse
    {
        try {
            /** @var HealthCheckService $healthCheck */
            $healthCheck = resolve(HealthCheckService::class);

            $status = $healthCheck->getStatus();

            return ApiResponse::success(
                data: $status,
                message: $status['message'],
                status: $status['success'] ? 200 : 503
            );
        } catch (\Throwable $e) {
            report($e);

            return ApiResponse::serverError(
                message: 'Unexpected error while checking health'
            );
        }
    }
}
