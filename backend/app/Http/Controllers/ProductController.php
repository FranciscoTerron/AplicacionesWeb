<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Services\FirestoreService;

class ProductController extends Controller
{
    use CrudActionsTrait;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    protected function getCollectionName(): string
    {
        return 'products';
    }

    protected function getRedirectRoute(): string
    {
        return 'admin.products.index';
    }

    protected function getViewFolder(): string
    {
        return 'admin.products';
    }

    protected function getStoreRequestClass(): string
    {
        return StoreProductRequest::class;
    }

    protected function getUpdateRequestClass(): string
    {
        return UpdateProductRequest::class;
    }
}
