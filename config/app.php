<?php

use function App\Core\env;

return [
    'name'     => 'CaNDI',
    'env'      => env('APP_ENV', 'production'),
    'debug'    => env('APP_DEBUG', false),
    'url'      => env('APP_URL', 'http://localhost:8888/site-candi'),
    'timezone' => 'Africa/Conakry',
    'locale'   => 'fr_GN',

    'company' => [
        'name'    => 'CaNDI (Conseil et Appui au Numérique pour le Développement des Individus)',
        'rccm'    => 'RCCM/GN-KA/2023/B/3521',
        'phones'  => ['+224 625 55 55 55', '+224 655 55 55 55'],
        'email'   => 'info@candi-group.com',
        'address' => 'Sogbê, Kankan, Guinée',
        'website' => 'https://candi-group.com',
    ],

    'roles' => [
        'super_admin'            => 'Super Administrateur',
        'admin'                  => 'Administrateur',
        'comptable'              => 'Comptable',
        'responsable_formation'  => 'Responsable Formation',
        'formateur'              => 'Formateur',
        'commercial'             => 'Commercial',
        'client'                 => 'Client',
        'gestionnaire_immobilier' => 'Gestionnaire Immobilier',
    ],

    'enrollment_statuses' => [
        'en_attente'  => 'En attente',
        'valide'      => 'Validé',
        'refuse'      => 'Refusé',
        'annule'      => 'Annulé',
        'complete'    => 'Complété',
    ],

    'modalities' => [
        'presentiel'  => 'Présentiel',
        'en_ligne'    => 'En ligne',
        'hybride'     => 'Hybride',
    ],
];
