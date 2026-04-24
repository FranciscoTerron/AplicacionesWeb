<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
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
        $users = $this->firestore->listDocuments('users');

        return View::make('admin.users.index', compact('users'));
    }

    public function create()
    {
        return redirect()->route('admin.users.index');
    }

    public function store(StoreUserRequest $request)
    {
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
        // Only allow if admin or viewing own profile
        if (! (Auth::check() && (Auth::user()->role === 'admin' || Auth::user()->getAuthIdentifier() == $id))) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No tienes permisos para realizar esta acción.');
        }

        // Redirect to index since we're using modals for showing user details
        return redirect()->route('admin.users.index');
    }

    public function edit(string $id)
    {
        // Only allow if admin or editing own profile
        if (! (Auth::check() && (Auth::user()->role === 'admin' || Auth::user()->getAuthIdentifier() == $id))) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No tienes permisos para realizar esta acción.');
        }

        // Redirect to index since we're using modals for editing
        return redirect()->route('admin.users.index');
    }

    public function update(UpdateUserRequest $request, string $id)
    {
        // Only allow if admin or updating own profile
        if (! (Auth::check() && (Auth::user()->role === 'admin' || Auth::user()->getAuthIdentifier() == $id))) {
            return back()->withErrors(['email' => 'No tienes permisos para realizar esta acción.'])->withInput();
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
        // Prevent users from deleting themselves
        if (Auth::check() && Auth::user() && Auth::user()->getAuthIdentifier() == $id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No puedes eliminarte a ti mismo.');
        }

        // Only admins can deactivate other users
        if (! (Auth::check() && Auth::user() && Auth::user()->role === 'admin') &&
            ! (Auth::check() && Auth::user() && Auth::user()->getAuthIdentifier() != $id)) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $user = $this->firestore->getDocument('users', $id);
        if ($user) {
            $this->firestore->updateDocument('users', $id, ['active' => false]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Usuario desactivado correctamente.');
    }

    public function activate(string $id)
    {
        // Only admins can activate users
        if (! (Auth::check() && Auth::user()->role === 'admin')) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $user = $this->firestore->getDocument('users', $id);
        if ($user) {
            $this->firestore->updateDocument('users', $id, ['active' => true]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Usuario activado correctamente.');
    }
}
