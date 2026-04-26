<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subcategory\StoreSubcategoryRequest;
use App\Http\Requests\Subcategory\UpdateSubcategoryRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Services\FirestoreService;

class SubcategoryController extends Controller
{
    use CrudActionsTrait;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    protected function getCollectionName(): string
    {
        return 'subcategories';
    }

    protected function getRedirectRoute(): string
    {
        return 'admin.subcategories.index';
    }

    protected function getViewFolder(): string
    {
        return 'admin.subcategories';
    }

    protected function getStoreRequestClass(): string
    {
        return StoreSubcategoryRequest::class;
    }

    protected function getUpdateRequestClass(): string
    {
        return UpdateSubcategoryRequest::class;
    }

    protected function getExtraCreateData(): array
    {
        $result = $this->firestore->listDocuments('categories', 100);
        $categories = collect($result['documents'] ?? []);

        return ['categories' => $categories];
    }

    protected function getExtraEditData(string $id): array
    {
        $result = $this->firestore->listDocuments('categories', 100);
        $categories = collect($result['documents'] ?? []);

        return ['categories' => $categories];
    }
}
