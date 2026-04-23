<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class DiscountController extends Controller
{
    public function index()
    {
        return View::make('admin.discounts.index');
    }

    public function create()
    {
        return View::make('admin.discounts.create');
    }

    public function store(Request $request) {}

    public function show(string $id)
    {
        return View::make('admin.discounts.show', compact('id'));
    }

    public function edit(string $id)
    {
        return View::make('admin.discounts.edit', compact('id'));
    }

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
