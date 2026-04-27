<?php

namespace App\Http\Controllers;

use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Models\Client;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\View;

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

    protected function getModelClass(): string
    {
        return Client::class;
    }

    public function index()
    {
        $this->authorizeRequest('viewAny', $this->getModelClass());

        $items = $this->firestore->listDocuments($this->getCollectionName(), 100);

        return View::make("{$this->getViewFolder()}.index", [
            'clients' => $items['documents'] ?? [],
            'search' => request('search', ''),
            'statusFilter' => request('statusFilter', ''),
        ]);
    }
}
