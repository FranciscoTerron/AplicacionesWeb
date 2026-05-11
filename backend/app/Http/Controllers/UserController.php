<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class UserController extends Controller
{
    protected FirestoreService $firestore;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function index()
    {
        // Manual authorization: only admins can view user list
        // Note: Blocked users (active=false) are handled by FirestoreUserProvider
        // They cannot log in (retrieveByCredentials returns null)
        // If already logged in, they get logged out automatically (retrieveById returns null)
        $authUser = Auth::user();

        if (! $authUser || $authUser->role !== 'admin') {
            return $this->unauthorizedView();
        }

        $page = request()->get('page', 1);
        $startAfter = request()->get('after');
        $search = request()->get('search');
        $roleFilter = request()->get('role');
        $statusFilter = request()->get('status');

        $result = $this->firestore->listDocuments('users', 10, $startAfter);

        // Apply filters in PHP (since Firestore has limited query capabilities)
        $users = collect($result['documents']);

        if ($search) {
            $users = $users->filter(function ($user) use ($search) {
                return stripos($user['name'] ?? '', $search) !== false ||
                       stripos($user['email'] ?? '', $search) !== false;
            });
        }

        if ($roleFilter) {
            $users = $users->where('role', $roleFilter);
        }

        if ($statusFilter !== null) {
            $statusBool = $statusFilter === 'active';
            $users = $users->filter(function ($user) use ($statusBool) {
                return ($user['active'] ?? true) == $statusBool;
            });
        }

        return View::make('admin.users.index', [
            'users' => $users->values()->all(),
            'hasMore' => $result['hasMore'],
            'lastDocumentId' => $result['lastDocumentId'],
            'page' => $page,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function create()
    {
        // Only admins can create users
        $authUser = Auth::user();
        if (! $authUser || $authUser->role !== 'admin') {
            return $this->unauthorizedView();
        }

        return redirect()->route('admin.users.index');
    }

    public function store(StoreUserRequest $request)
    {
        // Only admins can create users
        $authUser = Auth::user();
        if (! $authUser || $authUser->role !== 'admin') {
            $error = 'No tienes permisos para realizar esta acción.';

            return $request->ajax()
                ? response()->json(['errors' => ['email' => [$error]]], 422)
                : back()->withErrors(['email' => $error])->withInput();
        }

        $validated = $request->validated();

        // Verificar si el email ya existe
        $existingUsers = $this->firestore->query('users', ['email' => $validated['email']]);
        if (! empty($existingUsers)) {
            $error = 'El email ya está registrado.';

            return $request->ajax()
                ? response()->json(['errors' => ['email' => [$error]]], 422)
                : back()->withErrors(['email' => $error])->withInput();
        }

        $data = [
            'active' => true,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'created_at' => now()->toISOString(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];

        $this->firestore->createDocument('users', $data);

        $success = 'Usuario creado correctamente.';

        return $request->ajax()
            ? response()->json(['success' => $success, 'redirect' => route('admin.users.index')])
            : redirect()->route('admin.users.index')->with('success', $success);
    }

    public function edit(string $id)
    {
        $user = $this->firestore->getDocument('users', $id);
        $authUser = Auth::user();

        // Allow if admin or editing own profile
        if (! $authUser || ($authUser->role !== 'admin' && $authUser->getAuthIdentifier() !== ($user['id'] ?? ''))) {
            return $this->unauthorizedView();
        }

        return redirect()->route('admin.users.index');
    }

    public function update(UpdateUserRequest $request, string $id)
    {
        $user = $this->firestore->getDocument('users', $id);
        $authUser = Auth::user();

        // Allow if admin or updating own profile
        if (! $authUser || ($authUser->role !== 'admin' && $authUser->getAuthIdentifier() !== ($user['id'] ?? ''))) {
            return $this->unauthorizedView();
        }

        $validated = $request->validated();

        // Verificar si el nuevo email ya está en uso por otro usuario
        $existingUsers = $this->firestore->query('users', ['email' => $validated['email']]);
        foreach ($existingUsers as $existingUser) {
            if ($existingUser['id'] !== $id) {
                $error = 'El email ya está registrado por otro usuario.';

                return $request->ajax()
                    ? response()->json(['errors' => ['email' => [$error]]], 422)
                    : back()->withErrors(['email' => $error])->withInput();
            }
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'updated_by' => Auth::id(),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = bcrypt($validated['password']);
        }

        $this->firestore->updateDocument('users', $id, $data);

        $success = 'Usuario actualizado correctamente.';

        return $request->ajax()
            ? response()->json(['success' => $success, 'redirect' => route('admin.users.index')])
            : redirect()->route('admin.users.index')->with('success', $success);
    }

    public function destroy(Request $request, string $id)
    {
        $authUser = Auth::user();
        $user = $this->firestore->getDocument('users', $id);

        // Admin can delete anyone except themselves
        if ($authUser && $authUser->role === 'admin') {
            if ($authUser->getAuthIdentifier() === $id) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'No puedes bloquearte a ti mismo.');
            }
        } elseif (! $authUser || $authUser->getAuthIdentifier() !== $id) {
            return $this->unauthorizedView();
        }

        $this->firestore->updateDocument('users', $id, ['active' => false, 'updated_by' => Auth::id()]);

        $success = 'Usuario bloqueado correctamente.';

        return $request->ajax()
            ? response()->json(['success' => $success, 'redirect' => route('admin.users.index')])
            : redirect()->route('admin.users.index')->with('success', $success);
    }

    public function activate(Request $request, string $id)
    {
        $authUser = Auth::user();
        $user = $this->firestore->getDocument('users', $id);

        // Only admins can activate users
        if (! $authUser || $authUser->role !== 'admin') {
            return $this->unauthorizedView();
        }

        $this->firestore->updateDocument('users', $id, ['active' => true, 'updated_by' => Auth::id()]);

        $success = 'Usuario desbloqueado correctamente.';

        return $request->ajax()
            ? response()->json(['success' => $success, 'redirect' => route('admin.users.index')])
            : redirect()->route('admin.users.index')->with('success', $success);
    }

    private function unauthorizedView()
    {
        return View::make('components.unauthorized', [
            'title' => 'Acceso Denegado',
            'subtitle' => 'No tienes permisos para acceder a esta sección',
            'message' => 'Tu cuenta no tiene los permisos necesarios para acceder a la gestión de usuarios.',
            'contactMessage' => 'Por favor, contacta a un administrador del sistema para solicitar permisos de acceso.',
        ]);
    }
}
