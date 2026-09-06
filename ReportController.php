<?php

namespace App\Controllers;

use App\Models\Report;
use App\Models\Vehicle;

/**
 * RODAX — Controller do Sistema de Denúncias
 */
class ReportController
{
    private Report $reportModel;
    private Vehicle $vehicleModel;

    public function __construct()
    {
        $this->reportModel  = new Report();
        $this->vehicleModel = new Vehicle();
    }

    /**
     * Processa a criação de uma denúncia enviada pelo formulário POST
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/');
        }

        // Validação de Token CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            $_SESSION['flash_error'] = "Sessão inválida. Por favor, tente novamente.";
            redirect('/');
        }

        $vehicleId   = (int)($_POST['vehicle_id'] ?? 0);
        $reason      = trim($_POST['reason'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $reporterId  = $_SESSION['user_id'] ?? null;

        $vehicle = $this->vehicleModel->findById($vehicleId);

        if (!$vehicle) {
            $_SESSION['flash_error'] = "Veículo não encontrado.";
            redirect('/veiculos');
        }

        $redirectUrl = '/veiculo/' . $vehicle['slug'];

        // Impedir que o proprietário denuncie o próprio anúncio
        if ($reporterId && (int)$vehicle['user_id'] === (int)$reporterId) {
            $_SESSION['flash_error'] = "Você não pode denunciar o seu próprio anúncio.";
            redirect($redirectUrl);
        }

        // Validar motivo
        if (empty($reason) || !in_array($reason, Report::$allowedReasons)) {
            $_SESSION['flash_error'] = "Por favor, selecione um motivo válido para a denúncia.";
            redirect($redirectUrl);
        }

        // Verificar se já denunciou
        if ($reporterId && $this->reportModel->hasUserReportedVehicle($reporterId, $vehicleId)) {
            $_SESSION['flash_warning'] = "Você já enviou uma denúncia para este veículo. Ela está sob análise.";
            redirect($redirectUrl);
        }

        // Salvar a denúncia
        $this->reportModel->create([
            'reporter_id' => $reporterId,
            'vehicle_id'  => $vehicleId,
            'reason'      => $reason,
            'description' => $description
        ]);

        $_SESSION['flash_success'] = "Sua denúncia foi recebida com sucesso e será analisada pela equipe do RODAX.";
        redirect($redirectUrl);
    }
}
