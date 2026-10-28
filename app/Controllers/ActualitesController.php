<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Post;

class ActualitesController extends Controller
{
    public function index(Request $request): void
    {
        $page  = max(1, (int) $request->get('page', 1));
        $posts = Post::published($page, 12);

        $this->view('actualites/index', [
            'pageTitle' => 'Actualités et activités de CaNDI',
            'metaDesc'  => 'Suivez les activités de CaNDI : formations, séminaires, partenariats, projets numériques et annonces officielles en Guinée.',
            'posts'     => $posts['data'],
            'pagination' => $posts,
        ]);
    }

    public function show(Request $request): void
    {
        $slug = $request->param('slug', '');
        $post = Post::findBySlug($slug);

        if (!$post || $post['status'] !== 'publie') {
            $this->abort(404, 'Article introuvable.');
        }

        $this->view('actualites/show', [
            'pageTitle' => $post['title'] . ' — CaNDI',
            'metaDesc'  => $post['excerpt'] ?? 'Actualité de CaNDI.',
            'post'      => $post,
        ]);
    }
}
