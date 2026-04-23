<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    public function index()
    {
        return view('admin.subcategories.index');
    }

    public function create()
    {
        return view('admin.subcategories.create');
    }

    public function store(Request $request) {}

    public function show(string $id)
    {
        return view('admin.subcategories.show', compact('id'));
    }

    public function edit(string $id)
    {
        return view('admin.subcategories.edit', compact('id'));
    }

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
