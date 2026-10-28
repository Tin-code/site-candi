<?php
namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\CourseMaterial;
use App\Models\Enrollment;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;

class EspaceApprenantController extends AppController
{
    private const TRAINER_ROLES  = ['super_admin', 'admin', 'responsable_formation', 'formateur'];
    private const ACCESS_STATUSES = ['paiement_partiel', 'confirmee', 'en_cours', 'terminee'];

    public function index(Request $r): void
    {
        $userId     = Auth::id();
        $role       = Auth::role() ?? '';
        $isTrainer  = in_array($role, self::TRAINER_ROLES, true);

        $enrollments = Enrollment::forUser($userId);

        // Course materials for all programs the user is enrolled in
        $programIds = array_unique(array_column($enrollments, 'program_id'));
        $materials  = CourseMaterial::forPrograms($programIds);

        // Group materials by program_id + session_id for easy lookup
        $matsByProgram = [];
        foreach ($materials as $m) {
            $key = $m['program_id'] . '|' . ($m['session_id'] ?? '');
            $matsByProgram[$key][] = $m;
        }

        // Trainer-only data: all materials + catalog for upload form
        $allMaterials = $isTrainer ? CourseMaterial::allAdmin() : [];
        $programs     = $isTrainer ? TrainingProgram::allAdmin() : [];
        $sessions     = $isTrainer ? TrainingSession::allAdmin() : [];

        $this->view('app/espace-apprenant/index', [
            'pageTitle'      => 'Espace apprenant',
            'enrollments'    => $enrollments,
            'matsByProgram'  => $matsByProgram,
            'accessStatuses' => self::ACCESS_STATUSES,
            'isTrainer'      => $isTrainer,
            'allMaterials'   => $allMaterials,
            'programs'       => $programs,
            'sessions'       => $sessions,
        ]);
    }

    public function uploadMaterial(Request $r): void
    {
        if (!in_array(Auth::role(), self::TRAINER_ROLES, true)) {
            $this->redirect('/app/espace-apprenant');
        }
        Csrf::check();

        $programId = $r->input('program_id', '');
        $sessionId = $r->input('session_id', '') ?: null;
        $title     = trim($r->input('title', ''));

        if (!$programId || !$title) {
            Session::flash('error', 'Formation et titre sont obligatoires.');
            $this->redirect('/app/espace-apprenant');
        }
        if (empty($_FILES['material_file']['tmp_name'])) {
            Session::flash('error', 'Aucun fichier sélectionné.');
            $this->redirect('/app/espace-apprenant');
        }

        $file     = $_FILES['material_file'];
        $maxBytes = 50 * 1024 * 1024; // 50 Mo
        if ($file['size'] > $maxBytes) {
            Session::flash('error', 'Le fichier dépasse 50 Mo.');
            $this->redirect('/app/espace-apprenant');
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'mp4', 'png', 'jpg', 'jpeg'];
        if (!in_array($ext, $allowed, true)) {
            Session::flash('error', 'Type de fichier non autorisé.');
            $this->redirect('/app/espace-apprenant');
        }

        $subDir   = rtrim(\App\Core\env('DOCUMENT_ROOT', __DIR__ . '/../../../public'), '/') . '/uploads/course-materials/' . $programId;
        if (!is_dir($subDir)) mkdir($subDir, 0755, true);

        $filename = time() . '-' . substr(md5($file['name']), 0, 8) . '.' . $ext;
        $dest     = $subDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Session::flash('error', 'Échec de l\'upload. Vérifiez les droits du répertoire.');
            $this->redirect('/app/espace-apprenant');
        }

        $base     = rtrim(\App\Core\env('APP_URL', ''), '/');
        $filePath = $base . '/uploads/course-materials/' . $programId . '/' . $filename;

        CourseMaterial::createMaterial([
            'program_id' => $programId,
            'session_id' => $sessionId,
            'title'      => $title,
            'file_path'  => $filePath,
            'file_name'  => $file['name'],
            'created_by' => Auth::id(),
        ]);
        Session::flash('success', 'Support déposé avec succès.');
        $this->redirect('/app/espace-apprenant');
    }

    public function destroyMaterial(Request $r): void
    {
        if (!in_array(Auth::role(), self::TRAINER_ROLES, true)) {
            $this->redirect('/app/espace-apprenant');
        }
        Csrf::check();
        CourseMaterial::deleteMaterial($r->param('id', ''));
        Session::flash('success', 'Support supprimé.');
        $this->redirect('/app/espace-apprenant');
    }

    public function icsDownload(Request $r): void
    {
        $enrollId   = $r->param('id', '');
        $enrollment = Enrollment::findWithDetails($enrollId);

        if (!$enrollment || $enrollment['user_id'] !== Auth::id()) {
            $this->abort(403);
        }
        if (!$enrollment['session_start']) {
            Session::flash('error', 'Aucune session programmée pour cette inscription.');
            $this->redirect('/app/espace-apprenant');
        }

        $title = 'Formation CaNDI : ' . ($enrollment['program_title'] ?? '');
        $start = str_replace('-', '', $enrollment['session_start']);
        $end   = str_replace('-', '', $enrollment['session_start']); // end_date if available
        if (!empty($enrollment['session_end'])) {
            $end = str_replace('-', '', $enrollment['session_end']);
        }

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="formation-candi-' . $enrollId . '.ics"');
        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//CaNDI//FR\r\n";
        echo "BEGIN:VEVENT\r\n";
        echo 'UID:' . $enrollId . '@candi.gn' . "\r\n";
        echo 'DTSTART;VALUE=DATE:' . $start . "\r\n";
        echo 'DTEND;VALUE=DATE:' . $end . "\r\n";
        echo 'SUMMARY:' . addslashes($title) . "\r\n";
        if (!empty($enrollment['session_location'])) {
            echo 'LOCATION:' . addslashes($enrollment['session_location']) . "\r\n";
        }
        echo 'DESCRIPTION:Dossier ' . ($enrollment['enrollment_number'] ?? '') . "\r\n";
        echo "END:VEVENT\r\n";
        echo "END:VCALENDAR\r\n";
        exit;
    }

    public function attestation(Request $r): void
    {
        $enrollId   = $r->param('id', '');
        $enrollment = Enrollment::findWithDetails($enrollId);

        if (!$enrollment) {
            $this->abort(404);
        }
        // Allow trainers and the enrolled user themselves
        $role      = Auth::role() ?? '';
        $isTrainer = in_array($role, self::TRAINER_ROLES, true);
        if (!$isTrainer && $enrollment['user_id'] !== Auth::id()) {
            $this->abort(403);
        }
        if ($enrollment['status'] !== 'terminee') {
            Session::flash('error', 'L\'attestation est disponible uniquement pour les formations terminées.');
            $this->redirect('/app/espace-apprenant');
        }

        $this->view('app/espace-apprenant/attestation', [
            'pageTitle'  => 'Attestation de formation',
            'enrollment' => $enrollment,
        ], null);
    }

    // Stubs
    public function show(Request $r): void        { $this->abort(404); }
    public function create(Request $r): void      { $this->redirect('/app/espace-apprenant'); }
    public function store(Request $r): void       { $this->redirect('/app/espace-apprenant'); }
    public function edit(Request $r): void        { $this->redirect('/app/espace-apprenant'); }
    public function update(Request $r): void      { $this->redirect('/app/espace-apprenant'); }
    public function destroy(Request $r): void     { $this->redirect('/app/espace-apprenant'); }
    public function initiate(Request $r): void    { $this->redirect('/app/paiements'); }
    public function returnUrl(Request $r): void   { $this->redirect('/app/espace-apprenant'); }
    public function toggle(Request $r): void      { $this->redirect('/app/utilisateurs'); }
    public function updateRole(Request $r): void  { $this->redirect('/app/utilisateurs'); }
    public function updatePassword(Request $r): void { $this->redirect('/app/profil'); }
    public function markRead(Request $r): void    { $this->redirect('/app/notifications'); }
    public function loyers(Request $r): void      { $this->redirect('/app/paiements-loyers'); }
    public function monLogement(Request $r): void { $this->redirect('/app/mon-logement'); }
}
