<?php
// controllers/PublicEcranController.php

declare(strict_types=1);

class PublicEcranController extends Controller
{
    public function index(): void
    {
        $scrutinModel  = new ScrutinModel();
        $resultatModel = new ResultatModel();

        $scrutin    = $scrutinModel->getScrutinPublic();
        $categories = $scrutin ? $scrutinModel->getCategoriesAvecCandidats((int) $scrutin['ID_SCRUTIN']) : [];
        $kpis       = $scrutin ? $resultatModel->getKpis((int) $scrutin['ID_SCRUTIN']) : [];

        $this->render('public/accueil_ecran', [
            'scrutin'    => $scrutin,
            'categories' => $categories,
            'kpis'       => $kpis,
        ], 'public');
    }

    public function resultats(): void
    {
        $scrutinModel  = new ScrutinModel();
        $resultatModel = new ResultatModel();

        $scrutin = $scrutinModel->getScrutinPublic();
        $peutAfficherResultats = $scrutin && in_array($scrutin['STATUT'], ['cloture', 'publie'], true);

        $groupes = [];
        $kpis    = [];

        if ($scrutin) {
            $idScrutin = (int) $scrutin['ID_SCRUTIN'];
            $kpis = $resultatModel->getKpis($idScrutin);
            if ($peutAfficherResultats) {
                $groupes = $resultatModel->getByScrutin($idScrutin);
            }
        }

        $this->render('public/resultats_ecran', [
            'scrutin'               => $scrutin,
            'peutAfficherResultats' => $peutAfficherResultats,
            'groupes'               => $groupes,
            'kpis'                  => $kpis,
        ], 'public');
    }
}