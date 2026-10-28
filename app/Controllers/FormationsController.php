<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\TrainingCategory;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;

class FormationsController extends Controller
{
    public function index(Request $request): void
    {
        $search   = $request->get('q', '');
        $category = $request->get('categorie', 'all');
        $level    = $request->get('niveau', 'all');

        $filters = [];
        if ($search !== '')      $filters['search']   = $search;
        if ($category !== 'all') $filters['category'] = $category;
        if ($level !== 'all')    $filters['level']    = $level;

        $programs   = TrainingProgram::catalogue($filters);
        $categories = TrainingCategory::allActive();
        $sessions   = TrainingSession::upcoming(50);

        $sessionsByProgram = [];
        foreach ($sessions as $session) {
            $pid = $session['program_id'] ?? null;
            if ($pid !== null) {
                $sessionsByProgram[$pid][] = $session;
            }
        }

        $this->view('formations/index', [
            'pageTitle'         => 'Catalogue des formations CaNDI — Conakry et Kankan',
            'metaDesc'          => 'Découvrez toutes les formations CaNDI : bureautique, intelligence artificielle, comptabilité, anglais.',
            'programs'          => $programs,
            'categories'        => $categories,
            'sessionsByProgram' => $sessionsByProgram,
            'search'            => $search,
            'category'          => $category,
            'level'             => $level,
            'user'              => Auth::user(),
        ]);
    }

    public function enroll(Request $request): void
    {
        if (!Auth::check()) {
            $appUrl = rtrim(\App\Core\env('APP_URL', ''), '/');
            header('Location: ' . $appUrl . '/auth?mode=signup');
            exit;
        }

        Csrf::check();

        $programId = $request->param('id', '');
        $sessionId = $request->post('session_id') ?: null;

        if ($programId === '') {
            $this->abort(404, 'Formation introuvable.');
        }

        $program = TrainingProgram::find($programId);
        if (!$program) {
            $this->abort(404, 'Formation introuvable.');
        }

        $userId = Auth::id();
        $pdo    = \App\Core\Database::getInstance();
        $stmt   = $pdo->prepare(
            'SELECT id FROM enrollments
             WHERE user_id = ? AND program_id = ?
               AND status NOT IN ("annulee","refusee","abandonnee","remboursee")
             LIMIT 1'
        );
        $stmt->execute([$userId, $programId]);
        if ($stmt->fetch()) {
            $this->withError('Vous avez déjà une inscription en cours pour cette formation.')->redirect('/formations');
        }

        $enrollId = \App\Core\Auth::uuid();
        $number   = 'INS-' . strtoupper(substr($enrollId, 0, 8));
        $pdo->prepare(
            'INSERT INTO enrollments
                (id, enrollment_number, user_id, program_id, session_id,
                 status, agreed_amount, paid_amount, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, "nouvelle_demande", 0, 0, NOW(), NOW())'
        )->execute([$enrollId, $number, $userId, $programId, $sessionId]);

        $this->withSuccess('Demande envoyée. Le service comptable vous contactera.')->redirect('/app/inscriptions');
    }

    public function show(Request $request): void
    {
        $this->abort(404);
    }
}
