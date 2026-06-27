<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Discount\ValidateDiscountRequest;
use App\Services\DiscountService;
use App\Services\FirestoreService;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class DiscountApiController extends Controller
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly DiscountService $discounts,
    ) {}

    /**
     * POST /api/v1/discounts/validate
     *
     * Valida un código de descuento.
     */
    #[OA\Post(
        path: '/api/v1/discounts/validate',
        operationId: 'validateDiscount',
        tags: ['Discounts'],
        security: [new OA\Security(name: 'BearerAuth')],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'code', type: 'string', maxLength: 50),
                    new OA\Property(property: 'product_id', type: 'string'),
                    new OA\Property(property: 'category_id', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Código de descuento válido',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            ),
            new OA\Response(
                response: 404,
                description: 'Código de descuento no válido o expirado'
            ),
        ]
    )]
    public function validate(ValidateDiscountRequest $request): JsonResponse
    {
        $code = $request->get('code');
        $productId = $request->get('product_id');
        $categoryId = $request->get('category_id');

        // Buscar descuento por código en Firestore
        $result = $this->firestore->query(
            collection: 'discounts',
            fields: ['code' => $code],
            limit: 1
        );

        if (count($result) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Código de descuento no encontrado.',
            ], 404);
        }

        $discount = $result[0];

        // Verificar que está activo
        if (! ($discount['active'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'El código de descuento no está activo.',
            ], 404);
        }

        // Verificar fechas de validez
        $now = Carbon::now();
        $validFrom = isset($discount['valid_from']) ? Carbon::parse($discount['valid_from']) : null;
        $validTo = isset($discount['valid_to']) ? Carbon::parse($discount['valid_to']) : null;

        if ($validFrom && $now->lt($validFrom)) {
            return response()->json([
                'success' => false,
                'message' => 'El código de descuento aún no es válido.',
            ], 404);
        }

        if ($validTo && $now->gt($validTo)) {
            return response()->json([
                'success' => false,
                'message' => 'El código de descuento ha expirado.',
            ], 404);
        }

        // Verificar límite de usos
        $maxUses = $discount['max_uses'] ?? null;
        $usedCount = $discount['used_count'] ?? 0;

        if ($maxUses !== null && $usedCount >= $maxUses) {
            return response()->json([
                'success' => false,
                'message' => 'El código de descuento ha alcanzado su límite de usos.',
            ], 404);
        }

        // Verificar aplicabilidad (applies_to + applicable_ids)
        $appliesTo = $discount['applies_to'] ?? 'all';
        $applicableIds = $discount['applicable_ids'] ?? [];

        if ($appliesTo !== 'all' && ! empty($applicableIds)) {
            $targetId = $productId ?? $categoryId;

            if ($targetId && ! in_array($targetId, $applicableIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código de descuento no aplica a este producto/categoría.',
                ], 404);
            }

            // Si se proporcionó product_id pero aplica a categoría, verificar coincidencia
            if ($productId && $appliesTo === 'category') {
                if (! $categoryId) {
                    $product = $this->firestore->getDocument('products', $productId);
                    if ($product && isset($product['category_id'])) {
                        $categoryId = $product['category_id'];
                    }
                }
                if ($categoryId && ! in_array($categoryId, $applicableIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El código de descuento no aplica a este producto/categoría.',
                    ], 404);
                }
            }
        }

        // Si hay producto, calcular precio final para que el front lo muestre.
        // Regla: cupón y descuento automático NO se suman, gana el mejor.
        $pricing = null;
        if ($productId) {
            $product = $this->firestore->getDocument('products', $productId);
            if ($product) {
                $base = (float) ($product['price'] ?? 0);
                $couponFinal = DiscountService::applyValue(
                    $base,
                    (string) ($discount['discount_type'] ?? 'percentage'),
                    (float) ($discount['value'] ?? 0),
                );
                $auto = $this->discounts->bestForProduct($product);
                $autoFinal = $auto['final'] ?? $base;
                $final = min($couponFinal, $autoFinal);

                $pricing = [
                    'base' => round($base, 2),
                    'coupon_final' => $couponFinal,
                    'auto_final' => $autoFinal,
                    'final' => $final,
                    'amount' => round($base - $final, 2),
                    'applied' => $couponFinal <= $autoFinal ? 'coupon' : 'auto',
                ];
            }
        }

        // Devolver descuento sin campos internos
        $responseData = [
            'id' => $discount['id'] ?? null,
            'code' => $discount['code'] ?? $code,
            'name' => $discount['name'] ?? null,
            'description' => $discount['description'] ?? null,
            'discount_type' => $discount['discount_type'] ?? null,
            'value' => $discount['value'] ?? null,
            'applies_to' => $appliesTo,
            'pricing' => $pricing,
        ];

        return ApiResponse::success(
            data: $responseData,
            message: 'Código de descuento válido'
        );
    }
}
