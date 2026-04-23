<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index() { return view('admin.categories.index'); }
    public function create() { return view('admin.categories.create'); }
    public function store(Request $request) {}
    public function show(string $id) { return view('admin.categories.show', compact('id')); }
    public function edit(string $id) { return view('admin.categories.edit', compact('id')); }
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}
}