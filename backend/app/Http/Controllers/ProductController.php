<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index() { return view('admin.products.index'); }
    public function create() { return view('admin.products.create'); }
    public function store(Request $request) {}
    public function show(string $id) { return view('admin.products.show', compact('id')); }
    public function edit(string $id) { return view('admin.products.edit', compact('id')); }
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}
}