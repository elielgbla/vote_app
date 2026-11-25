<?php
// controllers/ScrutinController.php

declare(strict_types=1);

class ScrutinController extends Controller
{
    public function index(): void
    {
        $scrutinModel  = new ScrutinModel();
        $individuModel = new IndividuModel();

        $scrutins = $scrutinModel->getAll();
        $selectedId = isset($_GET['scrutin']) ? (int) $_GET['scrutin'] : (int) ($scrutins[0]['ID_SCRUTIN'] ?? 0);

        $scrutinDetail = $selectedId > 0 ? $scrutinModel->findById($selectedId) : null;
        $categories    = $scrutinDetail ? $scrutinModel->getCategoriesAvecCandidats($selectedId) : [];
        $historique    = $scrutinDetail ? $scrutinModel->getHistoriqueStatut($selectedId) : [];

        $this->render('admin/scrutins/index', [
            'scrutins'      => $scrutins,
            'scrutinDetail' => $scrutinDetail,
            'categories'    => $categories,
            'historique'    => $historique,
            'individus'     => $individuModel->getAll(),
        ], 'admin');
    }

    public function storeScrutin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->redirect('admin.scrutins.index');
        }

        try {
            $model = new ScrutinModel();
            $id = $model->createScrutin($_POST);
            $this->setAuditContext([
                'id_scrutin' => $id,
                'detail'     => 'Création du scrutin #' . $id . ' (' . ($_POST['titre'] ?? '') . ')',
            ]);
            $this->setFlash('success', 'Scrutin créé en statut brouillon.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('admin.scrutins.index');
    }

    public function changeStatut(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->redirect('admin.scrutins.index');
        }

        $idScrutin = (int) ($_POST['id_scrutin'] ?? 0);
        $nouveauStatut = (string) ($_POST['statut'] ?? '');

        try {
            $model = new ScrutinModel();
            $model->changeStatut($idScrutin, $nouveauStatut);
            $this->setAuditContext([
                'id_scrutin' => $idScrutin,
                'detail'     => 'Changement de statut vers "' . $nouveauStatut . '"',
            ]);
            $this->setFlash('success', 'Statut mis à jour vers "' . $nouveauStatut . '".');
        } catch (Throwable $e) {
            $this->setFlash('error', 'Transition refusée : ' . $e->getMessage());
        }

        $this->redirect('admin.scrutins.index', ['scrutin' => $idScrutin]);
    }

    public function storeCategorie(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->redirect('admin.scrutins.index');
        }

        $idScrutin = (int) ($_POST['id_scrutin'] ?? 0);
        try {
            $model = new ScrutinModel();
            $model->createCategorie($idScrutin, (string) ($_POST['lib'] ?? ''), $_POST['description'] ?? null);
            $this->setAuditContext([
                'id_scrutin' => $idScrutin,
                'table'      => 'CATEGORIE',
                'detail'     => 'Ajout de la catégorie "' . ($_POST['lib'] ?? '') . '"',
            ]);
            $this->setFlash('success', 'Catégorie ajoutée.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('admin.scrutins.index', ['scrutin' => $idScrutin]);
    }

    public function storeCandidature(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->redirect('admin.scrutins.index');
        }

        $idScrutin   = (int) ($_POST['id_scrutin'] ?? 0);
        $idCategorie = (int) ($_POST['id_categorie'] ?? 0);
        $cni         = strtoupper(trim((string) ($_POST['cni'] ?? '')));

        try {
            $model = new ScrutinModel();
            $model->addCandidature($idCategorie, $cni);
            $this->setAuditContext([
                'id_scrutin' => $idScrutin,
                'cni'        => $cni,
                'detail'     => 'Inscription du candidat CNI ' . $cni . ' dans la catégorie #' . $idCategorie,
            ]);
            $this->setFlash('success', 'Candidature enregistrée.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('admin.scrutins.index', ['scrutin' => $idScrutin]);
    }
}