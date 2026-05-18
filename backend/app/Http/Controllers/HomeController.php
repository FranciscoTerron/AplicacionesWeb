<?php

namespace App\Http\Controllers;

use App\Services\FirestoreService;

class HomeController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    public function index()
    {
        $integrantes = [
            ['iniciales' => 'FT', 'nombre' => 'Francisco Terrón'],
            ['iniciales' => 'MS', 'nombre' => 'Mauro San Pedro'],
        ];

        $productsResult = $this->firestore->listDocuments('products', 50);
        $allProducts = collect($productsResult['documents'] ?? [])->where('active', true);

        $featured = $allProducts->where('featured', true)->take(4)->values();
        if ($featured->isEmpty()) {
            $featured = $allProducts->take(4)->values();
        }

        $categoriesResult = $this->firestore->listDocuments('categories', 20);
        $categories = collect($categoriesResult['documents'] ?? [])
            ->where('active', true)
            ->take(4)
            ->values();

        return view('pages.home', [
            'integrantes' => $integrantes,
            'featured' => $featured,
            'categories' => $categories,
        ]);
    }
}
