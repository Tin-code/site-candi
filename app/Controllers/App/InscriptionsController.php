<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;

class InscriptionsController extends AppController
{
    private const VALID_STATUSES = [
        'brouillon', 'nouvelle_demande', 'a_contacter', 'contacte',
        'en_attente_documents', 'en_attente_paiement', 'paiement_partiel',
        'confirmee', 'en_cours', 'terminee', 'abandonnee', 'annulee',
        'refusee', 'remboursee',
    ];

    public function index(Request $request): void
    {
        $role    = Auth::role() ?? 'client';
        $isStaff = !in_array($role, ['client'], true);

        if ($isStaff) {
            $search  = $request->get('q', '');
            $status  = $request->get('status', '');
            $page    = max(1, (int) $request->get('page', 1));
            $filters = [];
            if ($search !== '') $filters['search'] = $search;
            if ($status !== '') $filters['status'] = $status;
            $result  = Enrollment::listAll($filters, $page, 20);

            $this->view('app/inscriptions/index', [
                'pageTitle'   => 'Inscriptions — CaNDI',
                'isStaff'     => true,
                'enrollments' => $result['data'],
                'pagination'  => $result,
                'search'      => $search,
                'status'      => $status,
                'statuses'    => self::VALID_STATUSES,
            ]);
            return;
        }

        $enrollments = Enrollment::forUser(Auth::id());
        $this->view('app/inscriptions/index', [
            'pageTitle'   => 'Mes inscriptions — CaNDI',
            'isStaff'     => false,
            'enrollments' => $enrollments,
        ]);
    }

    public function show(Request $request): void
    {
        $id         = $request->param('id', '');
        $enrollment = Enrollment::findWithDetails($id);

        if (!$enrollment) {
            $this->abort(404, 'Inscription introuvable.');
        }

        // Clients only see their own
        $role    = Auth::role() ?? 'client';
        $isStaff = !in_array($role, ['client'], true);
        if (!$isStaff && $enrollment['user_id'] !== Auth::id()) {
            $this->abort(403);
        }

        $this->view('app/inscriptions/show', [
            'pageTitle'  => 'Inscription ' . ($enrollment['enrollment_number'] ?? '') . ' — CaNDI',
            'enrollment' => $enrollment,
            'isStaff'    => $isStaff,
            'statuses'   => self::VALID_STATUSES,
        ]);
    }

    public function create(Request $request): void
    {
        $this->requireStaff();
        $this->view('app/inscriptions/form', [
            'pageTitle' => 'Nouvelle inscription',
            'programs'  => TrainingProgram::allAdmin(),
            'sessions'  => TrainingSession::allAdmin(),
            'old'       => Session::flash('old') ?? [],
            'errors'    => Session::flash('errors') ?? [],
        ]);
    }

    public function store(Request $request): void
    {
        $this->requireStaff();
        Csrf::check();

        $learner = $request->only(['first_name', 'last_name', 'phone', 'email', 'city', 'profession']);
        $enroll  = $request->only(['program_id', 'session_id', 'agreed_amount', 'payment_mode', 'status', 'notes']);

        $errors = [];
        if (empty(trim($learner['first_name'] ?? '')))  $errors['first_name']    = 'Le prénom est obligatoire.';
        if (empty(trim($learner['last_name']  ?? '')))  $errors['last_name']     = 'Le nom est obligatoire.';
        if (empty(trim($learner['phone']      ?? '')))  $errors['phone']         = 'Le téléphone est obligatoire.';
        if (empty($enroll['program_id']))               $errors['program_id']    = 'La formation est obligatoire.';
        if (!is_numeric($enroll['agreed_amount'] ?? '')) $errors['agreed_amount'] = 'Montant invalide.';

        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', array_merge($learner, $enroll));
            $this->redirect('/app/inscriptions/creer');
        }

        $userId = $this->findOrCreateLearner($learner);

        $enroll['user_id']    = $userId;
        $enroll['created_by'] = Auth::id();
        $id = Enrollment::createAdmin($enroll);

        // Notify staff roles about new enrollment
        $created = Enrollment::findWithDetails($id);
        $learnerName = trim(($created['first_name'] ?? '') . ' ' . ($created['last_name'] ?? ''));
        $programName = $created['program_title'] ?? 'formation';
        Notification::notifyRole(
            ['super_admin', 'admin', 'comptable', 'commercial', 'responsable_formation'],
            'Nouvelle inscription : ' . $learnerName,
            $learnerName . ' vient d\'être inscrit(e) à ' . $programName . '.',
            'info', 'inscriptions', '/app/inscriptions/' . $id,
            Auth::id()
        );

        Session::flash('success', 'Inscription créée avec succès.');
        $this->redirect('/app/inscriptions/' . $id);
    }

    private function findOrCreateLearner(array $data): string
    {
        $phone = preg_replace('/\D/', '', $data['phone'] ?? '');
        if (strlen($phone) === 9) $phone = '224' . $phone;
        $phoneEmail = $phone . '@tel.candi.gn';

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$phoneEmail]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            // Update profile with new info if provided
            $pdo->prepare(
                'UPDATE profiles SET first_name=?, last_name=?, email=?, city=?, profession=?, updated_at=NOW()
                 WHERE id=?'
            )->execute([
                trim($data['first_name']),
                trim($data['last_name']),
                !empty($data['email']) ? trim($data['email']) : null,
                !empty($data['city'])  ? trim($data['city'])  : null,
                !empty($data['profession']) ? trim($data['profession']) : null,
                $existing,
            ]);
            return $existing;
        }

        // Create new user
        $id   = Auth::uuid();
        $hash = password_hash(bin2hex(random_bytes(12)), PASSWORD_BCRYPT, ['cost' => 12]);

        $pdo->prepare(
            'INSERT INTO users (id, email, password_hash, email_verified, is_active, created_at, updated_at)
             VALUES (?, ?, ?, 0, 1, NOW(), NOW())'
        )->execute([$id, $phoneEmail, $hash]);

        $pdo->prepare(
            'INSERT INTO profiles (id, first_name, last_name, phone, email, city, profession, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        )->execute([
            $id,
            trim($data['first_name']),
            trim($data['last_name']),
            $phone,
            !empty($data['email']) ? trim($data['email']) : null,
            !empty($data['city'])  ? trim($data['city'])  : null,
            !empty($data['profession']) ? trim($data['profession']) : null,
        ]);

        $pdo->prepare(
            'INSERT INTO user_roles (id, user_id, role, created_at) VALUES (?, ?, "client", NOW())'
        )->execute([Auth::uuid(), $id]);

        return $id;
    }

    public function update(Request $request): void
    {
        $this->requireStaff();
        Csrf::check();

        $id = $request->param('id', '');
        if (!Enrollment::findWithDetails($id)) {
            $this->abort(404, 'Inscription introuvable.');
        }

        $status = $request->input('status', '');
        if ($status && !in_array($status, self::VALID_STATUSES, true)) {
            Session::flash('error', 'Statut invalide.');
            $this->redirect('/app/inscriptions/' . $id);
        }

        $data = [];
        if ($status) $data['status'] = $status;

        $agreedRaw = $request->input('agreed_amount', '');
        if ($agreedRaw !== '') $data['agreed_amount'] = (float) $agreedRaw;

        $discountRaw = $request->input('discount', '');
        if ($discountRaw !== '') $data['discount'] = (float) $discountRaw;

        $data['contact_notes']    = $request->input('contact_notes', '');
        $data['notes']            = $request->input('notes', '');
        $data['next_followup_at'] = $request->input('next_followup_at', '');
        if (!empty($data['contact_notes'])) {
            $data['last_contact_at'] = true;
        }

        $enrollBefore = Enrollment::findWithDetails($id);
        Enrollment::updateDetails($id, $data, Auth::id());

        // Notify client on significant status changes
        if ($status && !empty($enrollBefore['user_id'])) {
            $program = $enrollBefore['program_title'] ?? 'votre formation';
            $clientNotifs = [
                'confirmee'          => ['Inscription confirmée !', 'Votre inscription à ' . $program . ' est confirmée.', 'succes'],
                'refusee'            => ['Inscription refusée', 'Votre inscription à ' . $program . ' n\'a pas pu être acceptée. Contactez-nous.', 'alerte'],
                'en_attente_paiement'=> ['Paiement requis', 'Votre inscription à ' . $program . ' est en attente de paiement.', 'warning'],
                'paiement_partiel'   => ['Paiement partiel reçu', 'Un paiement partiel a été enregistré pour ' . $program . '.', 'info'],
                'annulee'            => ['Inscription annulée', 'Votre inscription à ' . $program . ' a été annulée.', 'alerte'],
                'terminee'           => ['Formation terminée', 'Félicitations ! Vous avez terminé ' . $program . '.', 'succes'],
            ];
            if (isset($clientNotifs[$status])) {
                [$title, $body, $level] = $clientNotifs[$status];
                Notification::notifyUser(
                    $enrollBefore['user_id'], $title, $body, $level,
                    'inscriptions', '/app/inscriptions/' . $id
                );
            }
        }

        Session::flash('success', 'Dossier mis à jour.');
        $this->redirect('/app/inscriptions/' . $id);
    }

    public function confirm(Request $request): void
    {
        $this->requireStaff();
        Csrf::check();

        $id = $request->param('id', '');
        $enr = Enrollment::findWithDetails($id);
        Enrollment::updateStatus($id, 'confirmee', Auth::id());

        if ($enr && !empty($enr['user_id'])) {
            $program = $enr['program_title'] ?? 'votre formation';
            Notification::notifyUser(
                $enr['user_id'],
                'Inscription confirmée !',
                'Votre inscription à ' . $program . ' est officielle. Bienvenue !',
                'succes', 'inscriptions', '/app/inscriptions/' . $id
            );
        }
        $this->withSuccess('Inscription confirmée.')->redirect('/app/inscriptions/' . $id);
    }

    public function refuse(Request $request): void
    {
        $this->requireStaff();
        Csrf::check();

        $id = $request->param('id', '');
        $enr = Enrollment::findWithDetails($id);
        Enrollment::updateStatus($id, 'refusee', Auth::id());

        if ($enr && !empty($enr['user_id'])) {
            $program = $enr['program_title'] ?? 'votre formation';
            Notification::notifyUser(
                $enr['user_id'],
                'Demande d\'inscription non retenue',
                'Votre demande d\'inscription à ' . $program . ' n\'a pas pu être acceptée. Contactez-nous pour plus d\'informations.',
                'alerte', 'inscriptions', '/app/inscriptions/' . $id
            );
        }
        $this->withSuccess('Inscription refusée.')->redirect('/app/inscriptions/' . $id);
    }

    // -----------------------------------------------------------------------

    private function requireStaff(): void
    {
        $role = Auth::role() ?? 'client';
        if (in_array($role, ['client'], true)) {
            $this->abort(403, 'Accès réservé au personnel.');
        }
    }
}
