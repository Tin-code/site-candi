<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\Branch;
use App\Models\TrainingCategory;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;

class FormationsAppController extends AppController
{
    private const STAFF_ROLES = ['super_admin', 'admin', 'responsable_formation', 'formateur', 'comptable'];

    public function index(Request $r): void
    {
        if (!Auth::hasRole(self::STAFF_ROLES)) {
            $this->redirect('/app/tableau-de-bord');
        }
        $programs = TrainingProgram::allAdmin();
        $sessions = TrainingSession::allAdmin();
        $sessionsByProgram = [];
        foreach ($sessions as $s) {
            $sessionsByProgram[$s['program_id']][] = $s;
        }
        $this->view('app/catalogue/index', [
            'pageTitle'          => 'Catalogue de formations',
            'programs'           => $programs,
            'sessionsByProgram'  => $sessionsByProgram,
        ]);
    }

    public function create(Request $r): void
    {
        if (!Auth::hasRole(self::STAFF_ROLES)) {
            $this->redirect('/app/catalogue');
        }
        $this->view('app/catalogue/program-form', [
            'pageTitle'  => 'Nouveau programme',
            'program'    => null,
            'categories' => TrainingCategory::allActive(),
            'old'        => Session::flash('old') ?? [],
            'errors'     => Session::flash('errors') ?? [],
        ]);
    }

    public function store(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole(self::STAFF_ROLES)) {
            $this->redirect('/app/catalogue');
        }
        $data   = $r->only(['code','title','summary','level','price','promo_price','duration_label','category_id','is_published','allow_installments','certificate_available']);
        $errors = $this->validateProgram($data);
        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect('/app/catalogue/creer');
        }
        $uploaded = $this->handleImageUpload('program_image', 'programs');
        if ($uploaded !== null) $data['image_url'] = $uploaded;
        TrainingProgram::createAdmin($data, Auth::id());
        Session::flash('success', 'Programme créé avec succès.');
        $this->redirect('/app/catalogue');
    }

    public function edit(Request $r): void
    {
        if (!Auth::hasRole(self::STAFF_ROLES)) {
            $this->redirect('/app/catalogue');
        }
        $program = TrainingProgram::find($r->param('id', ''));
        if (!$program) $this->abort(404);
        $this->view('app/catalogue/program-form', [
            'pageTitle'  => 'Modifier le programme',
            'program'    => $program,
            'categories' => TrainingCategory::allActive(),
            'old'        => Session::flash('old') ?? [],
            'errors'     => Session::flash('errors') ?? [],
        ]);
    }

    public function update(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole(self::STAFF_ROLES)) {
            $this->redirect('/app/catalogue');
        }
        $id      = $r->param('id', '');
        $program = TrainingProgram::find($id);
        if (!$program) $this->abort(404);
        $data   = $r->only(['code','title','summary','level','price','promo_price','duration_label','category_id','is_published','allow_installments','certificate_available']);
        $errors = $this->validateProgram($data);
        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect('/app/catalogue/' . $id . '/modifier');
        }
        $remove   = (bool) $r->input('program_image_remove', false);
        $uploaded = $this->handleImageUpload('program_image', 'programs', $program['image_url'] ?? null, $remove);
        if ($uploaded !== null) $data['image_url'] = $uploaded;
        TrainingProgram::updateAdmin($id, $data);
        Session::flash('success', 'Programme mis à jour.');
        $this->redirect('/app/catalogue');
    }

    public function destroy(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole(['super_admin', 'admin'])) {
            $this->redirect('/app/catalogue');
        }
        $id = $r->param('id', '');
        TrainingProgram::archiveAdmin($id);
        Session::flash('success', 'Programme archivé.');
        $this->redirect('/app/catalogue');
    }

    // ---- Sessions ----

    public function createSession(Request $r): void
    {
        if (!Auth::hasRole(self::STAFF_ROLES)) {
            $this->redirect('/app/catalogue');
        }
        $program = TrainingProgram::find($r->param('programId', ''));
        if (!$program) $this->abort(404);
        $this->view('app/catalogue/session-form', [
            'pageTitle' => 'Nouvelle session — ' . $program['title'],
            'program'   => $program,
            'session'   => null,
            'branches'  => Branch::allActive(),
            'old'       => Session::flash('old') ?? [],
            'errors'    => Session::flash('errors') ?? [],
        ]);
    }

    public function storeSession(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole(self::STAFF_ROLES)) {
            $this->redirect('/app/catalogue');
        }
        $programId = $r->param('programId', '');
        $program   = TrainingProgram::find($programId);
        if (!$program) $this->abort(404);
        $data   = $r->only(['code','start_date','end_date','location','capacity','modality','branch_id','status','is_published']);
        $errors = $this->validateSession($data);
        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect('/app/catalogue/' . $programId . '/sessions/creer');
        }
        $data['program_id'] = $programId;
        TrainingSession::createAdmin($data);
        Session::flash('success', 'Session planifiée.');
        $this->redirect('/app/catalogue');
    }

    public function editSession(Request $r): void
    {
        if (!Auth::hasRole(self::STAFF_ROLES)) {
            $this->redirect('/app/catalogue');
        }
        $session = TrainingSession::find($r->param('id', ''));
        if (!$session) $this->abort(404);
        $program = TrainingProgram::find($session['program_id']);
        $this->view('app/catalogue/session-form', [
            'pageTitle' => 'Modifier la session',
            'program'   => $program,
            'session'   => $session,
            'branches'  => Branch::allActive(),
            'old'       => Session::flash('old') ?? [],
            'errors'    => Session::flash('errors') ?? [],
        ]);
    }

    public function updateSession(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole(self::STAFF_ROLES)) {
            $this->redirect('/app/catalogue');
        }
        $id      = $r->param('id', '');
        $session = TrainingSession::find($id);
        if (!$session) $this->abort(404);
        $data   = $r->only(['code','start_date','end_date','location','capacity','modality','branch_id','status','is_published']);
        $errors = $this->validateSession($data);
        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect('/app/catalogue/sessions/' . $id . '/modifier');
        }
        TrainingSession::updateAdmin($id, $data);
        Session::flash('success', 'Session mise à jour.');
        $this->redirect('/app/catalogue');
    }

    public function destroySession(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole(self::STAFF_ROLES)) {
            $this->redirect('/app/catalogue');
        }
        TrainingSession::archiveAdmin($r->param('id', ''));
        Session::flash('success', 'Session supprimée.');
        $this->redirect('/app/catalogue');
    }

    // ---- Validation helpers ----

    private function validateProgram(array $data): array
    {
        $errors = [];
        if (empty(trim($data['code'] ?? '')))  $errors['code']  = 'Le code est obligatoire.';
        if (empty(trim($data['title'] ?? ''))) $errors['title'] = 'Le titre est obligatoire.';
        return $errors;
    }

    private function validateSession(array $data): array
    {
        $errors = [];
        if (empty(trim($data['code'] ?? '')))       $errors['code']       = 'Le code est obligatoire.';
        if (empty($data['start_date'] ?? ''))        $errors['start_date'] = 'La date de début est obligatoire.';
        if (empty($data['end_date'] ?? ''))          $errors['end_date']   = 'La date de fin est obligatoire.';
        if (!empty($data['start_date']) && !empty($data['end_date']) && $data['end_date'] < $data['start_date']) {
            $errors['end_date'] = 'La date de fin doit être après la date de début.';
        }
        return $errors;
    }

    // Legacy stub methods required by routes
    public function show(Request $r): void       { $this->abort(404); }
    public function initiate(Request $r): void   { $this->redirect('/app/paiements'); }
    public function returnUrl(Request $r): void  { $this->redirect('/app/paiements'); }
    public function toggle(Request $r): void     { $this->redirect('/app/catalogue'); }
    public function updateRole(Request $r): void { $this->redirect('/app/catalogue'); }
    public function updatePassword(Request $r): void { $this->redirect('/app/profil'); }
    public function markRead(Request $r): void   { $this->redirect('/app/notifications'); }
    public function loyers(Request $r): void     { $this->redirect('/app/catalogue'); }
    public function monLogement(Request $r): void { $this->redirect('/app/catalogue'); }
}
