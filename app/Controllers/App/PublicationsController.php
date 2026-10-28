<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\Post;

class PublicationsController extends AppController
{
    private const ALLOWED_ROLES = ['super_admin', 'admin', 'comptable', 'responsable_formation', 'formateur', 'commercial'];

    public function index(Request $r): void
    {
        if (!Auth::hasRole(self::ALLOWED_ROLES)) {
            $this->redirect('/app/tableau-de-bord');
        }
        $this->view('app/publications/index', [
            'pageTitle' => 'Publications du site',
            'posts'     => Post::allAdmin(),
        ]);
    }

    public function create(Request $r): void
    {
        if (!Auth::hasRole(self::ALLOWED_ROLES)) {
            $this->redirect('/app/publications');
        }
        $this->view('app/publications/form', [
            'pageTitle' => 'Nouvelle publication',
            'post'      => null,
            'old'       => Session::flash('old') ?? [],
            'errors'    => Session::flash('errors') ?? [],
        ]);
    }

    public function store(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole(self::ALLOWED_ROLES)) {
            $this->redirect('/app/publications');
        }
        $data   = $r->only(['title','slug','category','excerpt','content','video_url','status','published_at']);
        $errors = $this->validatePost($data);
        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect('/app/publications/creer');
        }
        $data['slug']      = Post::slugify(!empty($data['slug']) ? $data['slug'] : $data['title']);
        $data['author_id'] = Auth::id();
        if (($data['status'] ?? '') === 'publie' && empty($data['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        $uploaded = $this->handleImageUpload('cover_image', 'covers');
        if ($uploaded !== null) $data['cover_url'] = $uploaded;
        Post::createAdmin($data);
        Session::flash('success', 'Publication créée.');
        $this->redirect('/app/publications');
    }

    public function edit(Request $r): void
    {
        if (!Auth::hasRole(self::ALLOWED_ROLES)) {
            $this->redirect('/app/publications');
        }
        $post = Post::find($r->param('id', ''));
        if (!$post) $this->abort(404);
        $this->view('app/publications/form', [
            'pageTitle' => 'Modifier la publication',
            'post'      => $post,
            'old'       => Session::flash('old') ?? [],
            'errors'    => Session::flash('errors') ?? [],
        ]);
    }

    public function update(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole(self::ALLOWED_ROLES)) {
            $this->redirect('/app/publications');
        }
        $id   = $r->param('id', '');
        $post = Post::find($id);
        if (!$post) $this->abort(404);
        $data   = $r->only(['title','slug','category','excerpt','content','video_url','status','published_at']);
        $errors = $this->validatePost($data);
        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect('/app/publications/' . $id . '/modifier');
        }
        $data['slug'] = Post::slugify(!empty($data['slug']) ? $data['slug'] : $data['title']);
        if (($data['status'] ?? '') === 'publie' && empty($data['published_at'])) {
            $data['published_at'] = $post['published_at'] ?? date('Y-m-d H:i:s');
        }
        $remove   = (bool) $r->input('cover_image_remove', false);
        $uploaded = $this->handleImageUpload('cover_image', 'covers', $post['cover_url'] ?? null, $remove);
        if ($uploaded !== null) $data['cover_url'] = $uploaded;
        Post::updateAdmin($id, $data);
        Session::flash('success', 'Publication mise à jour.');
        $this->redirect('/app/publications');
    }

    public function destroy(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole(self::ALLOWED_ROLES)) {
            $this->redirect('/app/publications');
        }
        Post::delete($r->param('id', ''));
        Session::flash('success', 'Publication supprimée.');
        $this->redirect('/app/publications');
    }

    public function toggle(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole(self::ALLOWED_ROLES)) {
            $this->redirect('/app/publications');
        }
        $id   = $r->param('id', '');
        $post = Post::find($id);
        if (!$post) $this->abort(404);
        $newStatus = $post['status'] === 'publie' ? 'brouillon' : 'publie';
        $extra = [];
        if ($newStatus === 'publie' && empty($post['published_at'])) {
            $extra['published_at'] = date('Y-m-d H:i:s');
        }
        Post::updateAdmin($id, array_merge(['status' => $newStatus], $extra));
        Session::flash('success', $newStatus === 'publie' ? 'Publication publiée.' : 'Publication dépubliée.');
        $this->redirect('/app/publications');
    }

    // Legacy stub methods
    public function show(Request $r): void       { $this->abort(404); }
    public function initiate(Request $r): void   { $this->redirect('/app/paiements'); }
    public function returnUrl(Request $r): void  { $this->redirect('/app/paiements'); }
    public function updateRole(Request $r): void { $this->redirect('/app/publications'); }
    public function updatePassword(Request $r): void { $this->redirect('/app/profil'); }
    public function markRead(Request $r): void   { $this->redirect('/app/notifications'); }
    public function loyers(Request $r): void     { $this->redirect('/app/publications'); }
    public function monLogement(Request $r): void { $this->redirect('/app/publications'); }

    private function validatePost(array $data): array
    {
        $errors = [];
        if (empty(trim($data['title'] ?? '')))   $errors['title']   = 'Le titre est obligatoire.';
        if (empty(trim($data['content'] ?? ''))) $errors['content'] = 'Le contenu est obligatoire.';
        return $errors;
    }
}
