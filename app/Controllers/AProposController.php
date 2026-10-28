<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\AppSetting;

class AProposController extends Controller
{
    private const DEFAULT_ABOUT = [
        'heroTitle' => "CaNDI, l'excellence guinéenne au service des compétences",
        'heroIntro' => "Bâtir les compétences, propulser l'excellence : c'est la promesse que nous tenons chaque jour auprès des professionnels, des entreprises et des familles de Guinée.",
        'stats' => [
            ['value' => '2023',    'label' => 'Année de fondation'],
            ['value' => '3',       'label' => "Pôles d'activité"],
            ['value' => '2',       'label' => 'Implantations : Conakry & Kankan'],
            ['value' => 'Trophée', 'label' => 'Meilleure entreprise de formation'],
        ],
        'history' => [
            "CaNDI a été fondée en 2023 par Mohamed SOUARE, aujourd'hui Directeur Général de l'entreprise, avec une conviction simple : la Guinée dispose de tous les talents nécessaires à son développement, à condition de leur donner la formation, les outils et l'accompagnement qu'ils méritent.",
            "De ce constat est né un centre de formation professionnelle exigeant, rapidement reconnu par la profession et distingué du trophée de meilleure entreprise de formation. Puis l'entreprise a élargi son champ d'action : édition de progiciels de gestion pour les organisations, services numériques, et enfin gestion immobilière moderne pour les propriétaires et les locataires.",
            "Aujourd'hui, CaNDI est présente à Conakry (Kaloum) et à Kankan (Sogbè), et accompagne aussi bien des professionnels en reconversion que des entreprises en pleine transformation numérique.",
        ],
        'quote' => "Nous avons créé CaNDI pour que chaque Guinéen ambitieux trouve, ici, chez lui, une formation de niveau international et des outils modernes pour réussir. Former, outiller, accompagner : c'est notre façon de construire le pays.",
        'directorName'  => 'Mohamed SOUARE',
        'directorTitle' => 'Fondateur & Directeur Général de CaNDI',
        'pillars' => [
            ['title' => 'Formation professionnelle',     'text' => "Des parcours certifiants conçus avec les entreprises, animés par des formateurs de terrain, à Conakry et à Kankan."],
            ['title' => 'Progiciels & services numériques', 'text' => "Des logiciels de gestion et des solutions numériques qui simplifient le quotidien des organisations guinéennes."],
            ['title' => 'Gestion immobilière',           'text' => "Une gestion locative transparente pour les propriétaires et un espace en ligne pour les locataires."],
        ],
        'values' => [
            ['title' => 'Exigence',     'text' => "Un niveau de qualité qui se mesure aux résultats de nos apprenants et de nos clients."],
            ['title' => 'Proximité',    'text' => "Une équipe locale, joignable, qui connaît la réalité des organisations guinéennes."],
            ['title' => 'Transparence', 'text' => "Des comptes clairs, des documents officiels et un suivi accessible à tout moment."],
            ['title' => 'Innovation',   'text' => "Le numérique mis au service des compétences, de la gestion et du patrimoine."],
        ],
        'distinction' => "CaNDI a reçu le trophée de meilleure entreprise de formation, une reconnaissance du travail de toute l'équipe auprès des apprenants et des entreprises partenaires.",
        'locations' => "Siège : Kaloum, Conakry.\nAgence : Sogbè, Kankan.",
        'official'  => "CaNDI délivre des attestations, conventions de formation et factures officielles pour ses apprenants et ses clients.",
        'images'    => [],
    ];

    public function index(Request $request): void
    {
        // Lit le contenu éditable depuis la DB, merge avec les défauts
        $saved = AppSetting::get('about_page', []);
        $about = is_array($saved)
            ? array_merge(self::DEFAULT_ABOUT, $saved)
            : self::DEFAULT_ABOUT;

        $this->view('a-propos/index', [
            'pageTitle' => 'À propos de CaNDI — Notre histoire, notre vision, notre équipe',
            'metaDesc'  => 'Fondée en 2023 par Mohamed SOUARE, CaNDI réunit formation professionnelle, progiciels de gestion et gestion immobilière en Guinée.',
            'about'     => $about,
        ]);
    }
}
