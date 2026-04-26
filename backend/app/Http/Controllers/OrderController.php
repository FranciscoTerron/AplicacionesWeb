<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Services\FirestoreService;

class OrderController extends Controller
{
    use CrudActionsTrait;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    protected function getCollectionName(): string
    {
        return 'orders';
    }

    protected function getRedirectRoute(): string
    {
        return 'admin.orders.index';
    }

    protected function getViewFolder(): string
    {
        return 'admin.orders';
    }

    protected function getStoreRequestClass(): string
    {
        return StoreOrderRequest::class;
    }

    protected function getUpdateRequestClass(): string
    {
        return UpdateOrderRequest::class;
    }

    protected function getExtraCreateData(): array
    {
        $result = $this->firestore->listDocuments('clients', 100);
        $clients = collect($result['documents'] ?? []);

        $productsResult = $this->firestore->listDocuments('products', 100);
        $products = collect($productsResult['documents'] ?? []);

        return [
            'clients' => $clients,
            'products' => $products,
        ];
    }

    protected function getExtraEditData(string $id): array
    {
        return $this->getExtraCreateData();
    }
}
