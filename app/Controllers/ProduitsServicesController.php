<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Offering;

class ProduitsServicesController extends Controller
{
    public function index(Request $request): void
    {
        $offerings = Offering::allPublished();

        $services = array_filter($offerings, fn($o) => ($o['kind'] ?? '') !== 'produit');
        $produits = array_filter($offerings, fn($o) => ($o['kind'] ?? '') === 'produit');

        $this->view('produits-services/index', [
            'pageTitle' => 'Nos produits et services — CaNDI',
            'metaDesc'  => 'Découvrez les services de CaNDI (formation professionnelle, accompagnement digital) et nos plateformes de gestion.',
            'services'  => array_values($services),
            'produits'  => array_values($produits),
        ]);
    }
}
