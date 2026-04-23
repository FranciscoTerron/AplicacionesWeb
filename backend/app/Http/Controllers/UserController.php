<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class UserController extends Controller
{
    public function index()
    {
        return View::make('admin.users.index');
    }

    public function create()
    {
        return View::make('admin.users.create');
    }

    public function store(Request $request) {}

    public function show(string $id)
    {
        return View::make('admin.users.show', compact('id'));
    }

    public function edit(string $id)
    {
        return View::make('admin.users.edit', compact('id'));
    }

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
