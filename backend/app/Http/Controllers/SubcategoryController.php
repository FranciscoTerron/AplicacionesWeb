<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SubcategoryController extends Controller
{
    public function index()
    {
        return View::make('admin.subcategories.index');
    }

    public function create()
    {
        return View::make('admin.subcategories.create');
    }

    public function store(Request $request) {}

    public function show(string $id)
    {
        return View::make('admin.subcategories.show', compact('id'));
    }

    public function edit(string $id)
    {
        return View::make('admin.subcategories.edit', compact('id'));
    }

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
