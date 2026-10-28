<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Models\Apartment;
use App\Models\Enrollment;
use App\Models\Lease;
use App\Models\RentPayment;
use App\Models\TrainingSession;

class DashboardController extends AppController
{
    public function index(Request $request): void
    {
        $user = Auth::user();
        $role = Auth::role() ?? 'client';

        $isStaff   = !in_array($role, ['client'], true);
        $isFinance = in_array($role, ['super_admin', 'admin', 'comptable'], true);

        // Vue dédiée gestionnaire_immobilier (comme React realEstateOnly)
        if ($role === 'gestionnaire_immobilier') {
            $pdo          = Database::getInstance();
            $apartments   = $pdo->query("SELECT id, status FROM apartments")->fetchAll();
            $nbAptTotal   = count($apartments);
            $nbAptOccupied = count(array_filter($apartments, fn($a) => $a['status'] === 'occupe'));
            $nbTenants    = (int) $pdo->query("SELECT COUNT(*) FROM leases WHERE status = 'actif'")->fetchColumn();
            $encaisse     = RentPayment::encaisseThisMonth();
            $nbAttente    = (int) $pdo->query("SELECT COUNT(*) FROM rent_payments WHERE status = 'en_attente'")->fetchColumn();

            $this->view('app/dashboard', [
                'pageTitle'     => 'Tableau de bord — CaNDI',
                'isStaff'       => false,
                'isImmoOnly'    => true,
                'nbAptOccupied' => $nbAptOccupied,
                'nbAptTotal'    => $nbAptTotal,
                'nbTenants'     => $nbTenants,
                'encaisse'      => $encaisse,
                'nbAttente'     => $nbAttente,
            ]);
            return;
        }

        if ($isStaff) {
            $pdo = Database::getInstance();

            // KPIs
            $pending   = Enrollment::pendingCount();
            $confirmed = Enrollment::confirmedCount();

            // Dernières inscriptions (10)
            $enrollments = Enrollment::listAll([], 1, 10)['data'];

            // Sessions à venir (5)
            $sessions = TrainingSession::upcoming(5);

            // KPIs financiers (si rôle finance)
            $income = $outcome = 0;
            $receivable = 0.0;
            if ($isFinance) {
                $stmt = $pdo->prepare(
                    "SELECT direction, SUM(amount) AS total
                     FROM transactions
                     WHERE status = 'validee'
                     GROUP BY direction"
                );
                $stmt->execute();
                foreach ($stmt->fetchAll() as $row) {
                    if ($row['direction'] === 'entree') $income  = (float) $row['total'];
                    else                                $outcome = (float) $row['total'];
                }
                $recv = $pdo->query(
                    "SELECT COALESCE(SUM(GREATEST(agreed_amount - paid_amount, 0)), 0)
                     FROM enrollments
                     WHERE status NOT IN ('annulee','refusee','remboursee')"
                )->fetchColumn();
                $receivable = (float) $recv;
            }

            $this->view('app/dashboard', [
                'pageTitle'    => 'Tableau de bord — CaNDI',
                'isStaff'      => true,
                'isFinance'    => $isFinance,
                'pending'      => $pending,
                'confirmed'    => $confirmed,
                'income'       => $income,
                'outcome'      => $outcome,
                'receivable'   => $receivable,
                'enrollments'  => $enrollments,
                'sessions'     => $sessions,
            ]);
            return;
        }

        // Vue client
        $enrollments = Enrollment::forUser($user['id']);
        $totalDue    = array_sum(array_column($enrollments, 'agreed_amount'));
        $totalPaid   = array_sum(array_column($enrollments, 'paid_amount'));

        $this->view('app/dashboard', [
            'pageTitle'   => 'Mon espace — CaNDI',
            'isStaff'     => false,
            'enrollments' => $enrollments,
            'totalDue'    => $totalDue,
            'totalPaid'   => $totalPaid,
        ]);
    }
}
