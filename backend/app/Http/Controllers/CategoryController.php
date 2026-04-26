<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\FirestoreService;
use App\Http\Traits\CrudActionsTrait;

class CategoryController extends Controller
{
    use CrudActionsTrait;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    protected function getCollectionName(): string
    {
        return 'categories';
    }

    protected function getRedirectRoute(): string
    {
        return 'admin.categories.index';
    }

    protected function getViewFolder(): string
    {
        return 'admin.categories';
    }

    protected function getStoreRequestClass(): string
    {
        return StoreCategoryRequest::class;
    }

    protected function getUpdateRequestClass(): string
    {
        return UpdateCategoryRequest::class;
    }

    protected function getModelClass(): string
    {
        return Category::class;
    }
}
