<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\FirestoreService;
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
            return View::make('admin.users.unauthorized');
        }

        $page = request()->get('page', 1);
        $startAfter = request()->get('after');
        $search = request()->get('search');
        $roleFilter = request()->get('role');
        $statusFilter = request()->get('status');

        $result = $this->firestore->listDocuments('users', 20, $startAfter);

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
            return View::make('admin.users.unauthorized');
        }

        return redirect()->route('admin.users.index');
    }

    public function store(StoreUserRequest $request)
    {
        // Only admins can create users
        $authUser = Auth::user();
        if (! $authUser || $authUser->role !== 'admin') {
            return back()->withErrors(['email' => 'No tienes permisos para realizar esta acción.'])->withInput();
        }

        $validated = $request->validated();

        // Verificar si el email ya existe
        $existingUsers = $this->firestore->query('users', ['email' => $validated['email']]);
        if (! empty($existingUsers)) {
            return back()->withErrors(['email' => 'El email ya está registrado.'])->withInput();
        }

        $data = [
            'active' => true,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'created_at' => now()->toISOString(),
        ];

        $this->firestore->createDocument('users', $data);

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function show(string $id)
    {
        $user = $this->firestore->getDocument('users', $id);
        $authUser = Auth::user();

        // Allow if admin or viewing own profile
        if (! $authUser || ($authUser->role !== 'admin' && $authUser->getAuthIdentifier() !== ($user['id'] ?? ''))) {
            return View::make('admin.users.unauthorized');
        }

        return redirect()->route('admin.users.index');
    }

    public function edit(string $id)
    {
        $user = $this->firestore->getDocument('users', $id);
        $authUser = Auth::user();

        // Allow if admin or editing own profile
        if (! $authUser || ($authUser->role !== 'admin' && $authUser->getAuthIdentifier() !== ($user['id'] ?? ''))) {
            return View::make('admin.users.unauthorized');
        }

        return redirect()->route('admin.users.index');
    }

    public function update(UpdateUserRequest $request, string $id)
    {
        $user = $this->firestore->getDocument('users', $id);
        $authUser = Auth::user();

        // Allow if admin or updating own profile
        if (! $authUser || ($authUser->role !== 'admin' && $authUser->getAuthIdentifier() !== ($user['id'] ?? ''))) {
            return View::make('admin.users.unauthorized');
        }

        $validated = $request->validated();

        // Verificar si el nuevo email ya está en uso por otro usuario
        $existingUsers = $this->firestore->query('users', ['email' => $validated['email']]);
        foreach ($existingUsers as $existingUser) {
            if ($existingUser['id'] !== $id) {
                return back()->withErrors(['email' => 'El email ya está registrado por otro usuario.'])->withInput();
            }
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = bcrypt($validated['password']);
        }

        $this->firestore->updateDocument('users', $id, $data);

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(string $id)
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
            return View::make('admin.users.unauthorized');
        }

        $this->firestore->updateDocument('users', $id, ['active' => false]);

        return redirect()->route('admin.users.index')->with('success', 'Usuario bloqueado correctamente.');
    }

    public function activate(string $id)
    {
        $authUser = Auth::user();
        $user = $this->firestore->getDocument('users', $id);

        // Only admins can activate users
        if (! $authUser || $authUser->role !== 'admin') {
            return View::make('admin.users.unauthorized');
        }

        $this->firestore->updateDocument('users', $id, ['active' => true]);

        return redirect()->route('admin.users.index')->with('success', 'Usuario desbloqueado correctamente.');
    }
}
