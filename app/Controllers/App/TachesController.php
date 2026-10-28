<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\Task;

class TachesController extends AppController
{
    private const VALID_STATUSES   = ['a_faire', 'en_cours', 'en_attente', 'terminee', 'annulee'];
    private const VALID_PRIORITIES = ['basse', 'normale', 'haute', 'urgente'];
    private const VALID_MODULES    = ['Formations', 'Inscriptions', 'Finances', 'Clients', 'Administration'];

    private function requireStaff(): void
    {
        if (Auth::role() === 'client') $this->abort(403, 'Accès réservé au personnel.');
    }

    public function index(Request $r): void
    {
        $this->requireStaff();

        $userId      = Auth::id();
        $statusFilter = $r->get('status', '');
        $mineOnly    = (bool) $r->get('mine', 0);

        $tasks    = Task::listAll();
        $staffList = Task::staffUsers();
        $today    = date('Y-m-d');

        $stats = [
            'ouvertes' => 0,
            'urgentes' => 0,
            'retard'   => 0,
            'miennes'  => 0,
        ];
        foreach ($tasks as $t) {
            $open = !in_array($t['status'], ['terminee', 'annulee'], true);
            if ($open) $stats['ouvertes']++;
            if ($open && $t['priority'] === 'urgente') $stats['urgentes']++;
            if ($open && $t['due_date'] && substr($t['due_date'], 0, 10) < $today) $stats['retard']++;
            if ($t['assigned_to'] === $userId && $t['status'] !== 'terminee') $stats['miennes']++;
        }

        // Filter rows for display
        $rows = array_filter($tasks, function ($t) use ($statusFilter, $mineOnly, $userId) {
            if ($mineOnly && $t['assigned_to'] !== $userId) return false;
            if ($statusFilter === 'ouvertes') return !in_array($t['status'], ['terminee', 'annulee'], true);
            if ($statusFilter === 'toutes' || $statusFilter === '') return true;
            return $t['status'] === $statusFilter;
        });

        $this->view('app/taches/index', [
            'pageTitle'    => 'Tâches internes — CaNDI',
            'tasks'        => array_values($rows),
            'allTasks'     => $tasks,
            'stats'        => $stats,
            'staffList'    => $staffList,
            'statusFilter' => $statusFilter ?: 'ouvertes',
            'mineOnly'     => $mineOnly,
            'userId'       => $userId,
            'success'      => Session::flash('success'),
            'error'        => Session::flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->requireStaff();
        Csrf::check();

        $title = trim($r->input('title', ''));
        if (!$title) {
            Session::flash('error', 'Le titre de la tâche est obligatoire.');
            $this->redirect('/app/taches');
        }

        $status   = $r->input('status', 'a_faire');
        $priority = $r->input('priority', 'normale');
        $module   = $r->input('module', '');
        $due      = $r->input('due_date', '');
        $assigned = $r->input('assigned_to', '');

        Task::createTask([
            'title'       => $title,
            'description' => trim($r->input('description', '')) ?: null,
            'status'      => in_array($status, self::VALID_STATUSES, true) ? $status : 'a_faire',
            'priority'    => in_array($priority, self::VALID_PRIORITIES, true) ? $priority : 'normale',
            'module'      => in_array($module, self::VALID_MODULES, true) ? $module : null,
            'due_date'    => $due ?: null,
            'assigned_to' => $assigned ?: null,
            'created_by'  => Auth::id(),
        ]);

        Session::flash('success', 'Tâche créée.');
        $this->redirect('/app/taches');
    }

    public function update(Request $r): void
    {
        $this->requireStaff();
        Csrf::check();

        $id   = $r->param('id', '');
        $task = Task::findById($id);
        if (!$task) $this->abort(404);

        $data = [];

        $title = trim($r->input('title', ''));
        if ($title) $data['title'] = $title;

        $desc = $r->input('description', null);
        if ($desc !== null) $data['description'] = trim($desc) ?: null;

        $status = $r->input('status', '');
        if ($status && in_array($status, self::VALID_STATUSES, true)) {
            $data['status'] = $status;
            if ($status === 'terminee') $data['completed_at'] = date('Y-m-d H:i:s');
            elseif (in_array($status, ['a_faire','en_cours','en_attente'], true)) $data['completed_at'] = null;
        }

        $priority = $r->input('priority', '');
        if ($priority && in_array($priority, self::VALID_PRIORITIES, true)) $data['priority'] = $priority;

        $module = $r->input('module', '');
        if ($module !== '') $data['module'] = in_array($module, self::VALID_MODULES, true) ? $module : null;

        $due = $r->input('due_date', null);
        if ($due !== null) $data['due_date'] = $due ?: null;

        $assigned = $r->input('assigned_to', null);
        if ($assigned !== null) $data['assigned_to'] = $assigned ?: null;

        if (!empty($data)) Task::updateTask($id, $data);

        Session::flash('success', 'Tâche mise à jour.');
        $this->redirect('/app/taches');
    }

    public function destroy(Request $r): void
    {
        $this->requireStaff();
        Csrf::check();

        $id = $r->param('id', '');
        Task::deleteTask($id);
        Session::flash('success', 'Tâche supprimée.');
        $this->redirect('/app/taches');
    }
}
