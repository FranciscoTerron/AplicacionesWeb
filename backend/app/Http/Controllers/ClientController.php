<?php

namespace App\Http\Controllers;

use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Services\FirestoreService;

class ClientController extends Controller
{
    use CrudActionsTrait;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    protected function getCollectionName(): string
    {
        return 'clients';
    }

    protected function getRedirectRoute(): string
    {
        return 'admin.clients.index';
    }

    protected function getViewFolder(): string
    {
        return 'admin.clients';
    }

    protected function getStoreRequestClass(): string
    {
        return StoreClientRequest::class;
    }

    protected function getUpdateRequestClass(): string
    {
        return UpdateClientRequest::class;
    }
}
