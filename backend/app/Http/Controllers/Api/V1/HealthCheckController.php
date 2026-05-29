<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\HealthCheckRequest;
use App\Services\HealthCheckService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class HealthCheckController extends Controller
{
    /**
     * GET /api/v1/health
     *
     * Delgado: delega toda la logica al servicio.
     * Usa resolve() para garantizar que se toma el binding del contenedor,
     * lo que permite sobreescritura en tests via $this->app->instance().
     */
    #[OA\Get(
        path: '/api/v1/health',
        operationId: 'healthCheck',
        tags: ['HealthCheck'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Servicio saludable',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error interno',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'errors', type: 'null'),
                    ]
                )
            ),
        ]
    )]
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
