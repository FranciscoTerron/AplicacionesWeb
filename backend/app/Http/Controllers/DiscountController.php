<?php

namespace App\Http\Controllers;

use App\Http\Requests\Discount\StoreDiscountRequest;
use App\Http\Requests\Discount\UpdateDiscountRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Models\Discount;
use App\Services\FirestoreService;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    use CrudActionsTrait;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    protected function getCollectionName(): string
    {
        return 'discounts';
    }

    protected function getRedirectRoute(): string
    {
        return 'admin.discounts.index';
    }

    protected function getViewFolder(): string
    {
        return 'admin.discounts';
    }

    protected function getStoreRequestClass(): string
    {
        return StoreDiscountRequest::class;
    }

    protected function getUpdateRequestClass(): string
    {
        return UpdateDiscountRequest::class;
    }

    protected function getModelClass(): string
    {
        return Discount::class;
    }

    /**
     * Activate (restore) a soft-deleted discount.
     * Only admins can perform this action.
     */
    public function activate(Request $request, string $id)
    {
        $authUser = auth()->user();

        // Only admins can activate discounts
        if (! $authUser || $authUser->role !== 'admin') {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }

        try {
            // Reactivate the discount
            $data = [
                'active' => true,
                'updated_at' => now()->toISOString(),
                'updated_by' => auth()->id(),
            ];
            $this->firestore->updateDocument($this->getCollectionName(), $id, $data);

            $message = 'Descuento reactivado correctamente.';
            if ($request->ajax()) {
                return response()->json(['success' => $message, 'redirect' => route($this->getRedirectRoute())]);
            }

            return redirect()->route($this->getRedirectRoute())->with('success', $message);
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Error al reactivar el descuento.'], 500);
            }

            return redirect()->route($this->getRedirectRoute())->with('error', 'Error al reactivar el descuento.');
        }
    }
}
