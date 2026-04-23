<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    // Muestra la landing page principal
    public function index()
    {
        $integrantes = [
            ['iniciales' => 'FT', 'nombre' => 'Francisco Terrón'],
            ['iniciales' => 'MS', 'nombre' => 'Mauro San Pedro'],
        ];

        return view('pages.home', compact('integrantes'));
    }
}
