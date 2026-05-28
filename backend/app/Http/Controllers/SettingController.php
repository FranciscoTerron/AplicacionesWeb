<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Services\FirestoreService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\View as ViewFacade;

class SettingController extends Controller
{
    protected FirestoreService $firestore;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function index(): View
    {
        $settings = ViewFacade::shared('settings', []);

        return ViewFacade::make('admin.settings.index', [
            'settings' => $settings,
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $now = now()->toISOString();
        $userId = auth()->id();

        $settings = $this->firestore->getDocument('settings', 'store');

        if (! $settings) {
            $data = array_merge($validated, [
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $this->firestore->createDocumentWithId('settings', 'store', $data);
            $message = 'Configuración creada correctamente.';
        } else {
            $data = array_merge($validated, [
                'updated_at' => $now,
                'updated_by' => $userId,
            ]);
            $this->firestore->updateDocument('settings', 'store', $data);
            $message = 'Configuración actualizada correctamente.';
        }

        if ($request->ajax()) {
            return response()->json(['success' => $message]);
        }

        return redirect()->route('admin.settings')->with('success', $message);
    }
}
