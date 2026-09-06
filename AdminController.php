<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Report;
use App\Models\AdminLog;

/**
 * RODAX — Controller do Painel Administrativo
 */
class AdminController
{
    private User $userModel;
    private Vehicle $vehicleModel;
    private Report $reportModel;
    private AdminLog $adminLogModel;

    public function __construct()
    {
        $this->userModel     = new User();
        $this->vehicleModel  = new Vehicle();
        $this->reportModel   = new Report();
        $this->adminLogModel = new AdminLog();
    }

    /**
     * Dashboard do Painel Administrativo
     */
    public function index(): void
    {
        $totalUsers    = $this->userModel->countAll();
        $totalActive   = $this->vehicleModel->countActive();
        $pendingReportList = $this->reportModel->getAll('pending');
        $totalPendingReports = count($pendingReportList);
        $recentLogs    = $this->adminLogModel->getAll(10);

        view('admin.index', [
            'pageTitle'           => 'Painel Administrativo — RODAX',
            'totalUsers'          => $totalUsers,
            'totalActive'         => $totalActive,
            'totalPendingReports' => $totalPendingReports,
            'recentLogs'          => $recentLogs,
            'pendingReports'      => array_slice($pendingReportList, 0, 5)
        ]);
    }

    /**
     * Gestão de Usuários
     */
    public function users(): void
    {
        $search = $_GET['q'] ?? '';
        $users  = $this->userModel->getAllUsers($search);

        view('admin.users', [
            'pageTitle' => 'Gestão de Usuários — Admin RODAX',
            'users'     => $users,
            'search'    => $search
        ]);
    }

    /**
     * Bloquear / Desbloquear Usuário (POST)
     */
    public function toggleBlockUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/usuarios');
        }

        $userId  = (int)($_POST['user_id'] ?? 0);
        $adminId = (int)($_SESSION['user_id'] ?? 0);

        if ($userId === $adminId) {
            $_SESSION['flash_error'] = "Você não pode bloquear a sua própria conta de administrador.";
            redirect('/admin/usuarios');
        }

        $targetUser = $this->userModel->findById($userId);
        if (!$targetUser) {
            $_SESSION['flash_error'] = "Usuário não encontrado.";
            redirect('/admin/usuarios');
        }

        $this->userModel->toggleBlock($userId);
        $newBlockedState = empty($targetUser['is_blocked']) ? 1 : 0;
        $action = $newBlockedState ? 'block_user' : 'unblock_user';

        $this->adminLogModel->log(
            $adminId,
            $action,
            'user',
            $userId,
            "Usuário " . ($newBlockedState ? "bloqueado" : "desbloqueado") . ": {$targetUser['email']}"
        );

        $_SESSION['flash_success'] = "Status do usuário '{$targetUser['name']}' atualizado com sucesso.";
        redirect('/admin/usuarios');
    }

    /**
     * Gestão de Anúncios
     */
    public function vehicles(): void
    {
        $status = $_GET['status'] ?? '';
        $search = $_GET['q'] ?? '';

        $vehicles = $this->vehicleModel->adminGetAll([
            'status' => $status,
            'search' => $search
        ]);

        view('admin.vehicles', [
            'pageTitle' => 'Gestão de Anúncios — Admin RODAX',
            'vehicles'  => $vehicles,
            'status'    => $status,
            'search'    => $search
        ]);
    }

    /**
     * Alterar Status do Anúncio pelo Admin (POST)
     */
    public function updateVehicleStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/anuncios');
        }

        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $adminId   = (int)($_SESSION['user_id'] ?? 0);

        $vehicle = $this->vehicleModel->findById($vehicleId);
        if (!$vehicle) {
            $_SESSION['flash_error'] = "Anúncio não encontrado.";
            redirect('/admin/anuncios');
        }

        if ($this->vehicleModel->adminUpdateStatus($vehicleId, $newStatus)) {
            $this->adminLogModel->log(
                $adminId,
                'update_vehicle_status',
                'vehicle',
                $vehicleId,
                "Status alterado de '{$vehicle['status']}' para '{$newStatus}' (Título: {$vehicle['title']})"
            );

            $_SESSION['flash_success'] = "Status do anúncio #{$vehicleId} alterado para '{$newStatus}'.";
        } else {
            $_SESSION['flash_error'] = "Status inválido fornecido.";
        }

        redirect('/admin/anuncios');
    }

    /**
     * Gestão de Denúncias
     */
    public function reports(): void
    {
        $statusFilter = $_GET['status'] ?? '';
        $reports      = $this->reportModel->getAll($statusFilter);

        view('admin.reports', [
            'pageTitle'    => 'Gestão de Denúncias — Admin RODAX',
            'reports'      => $reports,
            'statusFilter' => $statusFilter
        ]);
    }

    /**
     * Atualizar Status da Denúncia (POST)
     */
    public function updateReportStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/denuncias');
        }

        $reportId  = (int)($_POST['report_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $adminId   = (int)($_SESSION['user_id'] ?? 0);

        $report = $this->reportModel->findById($reportId);
        if (!$report) {
            $_SESSION['flash_error'] = "Denúncia não encontrada.";
            redirect('/admin/denuncias');
        }

        if ($this->reportModel->updateStatus($reportId, $newStatus, $adminId)) {
            $this->adminLogModel->log(
                $adminId,
                'resolve_report',
                'report',
                $reportId,
                "Denúncia #{$reportId} para o veículo #{$report['vehicle_id']} marcada como '{$newStatus}'"
            );

            $_SESSION['flash_success'] = "Denúncia #{$reportId} atualizada para '{$newStatus}'.";
        } else {
            $_SESSION['flash_error'] = "Falha ao atualizar denúncia.";
        }

        redirect('/admin/denuncias');
    }

    /**
     * Histórico de Logs Administrativos (Audit Logs)
     */
    public function logs(): void
    {
        $logs = $this->adminLogModel->getAll(100);

        view('admin.logs', [
            'pageTitle' => 'Audit Logs — Admin RODAX',
            'logs'      => $logs
        ]);
    }
}
