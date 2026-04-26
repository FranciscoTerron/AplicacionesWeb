<?php

namespace App\Http\Controllers;

use App\Http\Requests\Discount\StoreDiscountRequest;
use App\Http\Requests\Discount\UpdateDiscountRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Services\FirestoreService;

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
}
