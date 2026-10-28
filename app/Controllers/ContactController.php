<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

class ContactController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('contact/index', [
            'pageTitle' => 'Contacter CaNDI — Conakry et Kankan',
            'metaDesc'  => "Coordonnées de CaNDI : agences de Conakry et Kankan, téléphone, e-mail.",
        ]);
    }
}
