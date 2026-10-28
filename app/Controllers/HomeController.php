<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\Post;

class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $programs = TrainingProgram::featured(4);
        $sessions = TrainingSession::upcoming(5);
        $posts    = Post::recent(3);

        $this->view('home/index', [
            'pageTitle' => 'CaNDI — Formations professionnelles et progiciels de gestion en Guinée',
            'metaDesc'  => 'CaNDI forme les professionnels guinéens et déploie des progiciels de gestion. Consultez le catalogue, les prochaines sessions et inscrivez-vous en ligne.',
            'programs'  => $programs,
            'sessions'  => $sessions,
            'posts'     => $posts,
        ]);
    }
}
