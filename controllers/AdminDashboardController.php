<?php
// controllers/AdminDashboardController.php

declare(strict_types=1);

class AdminDashboardController extends Controller
{
    public function index(): void
    {
        $scrutinModel  = new ScrutinModel();
        $resultatModel = new ResultatModel();

        $scrutins = $scrutinModel->getAll();
        $selectedId = isset($_GET['scrutin']) ? (int) $_GET['scrutin'] : 0;

        $scrutinCourant = null;
        if ($selectedId > 0) {
            $scrutinCourant = $scrutinModel->findById($selectedId);
        }
        if (!$scrutinCourant) {
            $scrutinCourant = $scrutinModel->getScrutinActif() ?: ($scrutins[0] ?? null);
        }

        $kpis = [
            'total_electeurs'  => 0,
            'emargements'      => 0,
            'taux_participation' => 0.0,
            'total_codes'      => 0,
            'codes_generes'    => 0,
            'codes_distribues' => 0,
            'codes_utilises'   => 0,
            'codes_annules'    => 0,
        ];
        $resultatsGroupes = [];

        if ($scrutinCourant) {
            $idScrutin = (int) $scrutinCourant['ID_SCRUTIN'];
            $kpis = $resultatModel->getKpis($idScrutin);
            $resultatsGroupes = $resultatModel->getByScrutin($idScrutin);
        }

        $this->render('admin/dashboard', [
            'scrutins'         => $scrutins,
            'scrutinCourant'   => $scrutinCourant,
            'kpis'             => $kpis,
            'resultatsGroupes' => $resultatsGroupes,
        ], 'admin');
    }
}