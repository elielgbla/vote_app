<?php
// controllers/ScrutinController.php

declare(strict_types=1);

class ScrutinController extends Controller
{
    private const ALLOWED_ROLES = ['super_admin', 'rh'];

    public function index(): void
    {
        $this->authorizeAdmin();

        $scrutinModel  = new ScrutinModel();
        $individuModel = new IndividuModel();

        $search = trim((string) ($_GET['q'] ?? ''));
        $statut = strtolower(trim((string) ($_GET['statut'] ?? '')));

        $scrutins = $scrutinModel->getAll($search, $statut);
        $selectedId = isset($_GET['scrutin']) && $_GET['scrutin'] !== ''
            ? (int) $_GET['scrutin']
            : (int) ($scrutins[0]['ID_SCRUTIN'] ?? 0);

        $scrutinDetail = $selectedId > 0 ? $scrutinModel->findById($selectedId) : null;
        $editId        = (int) ($_GET['edit'] ?? 0);
        $scrutinEdit   = $editId > 0 ? $scrutinModel->findById($editId) : null;
        $categories    = $scrutinDetail ? $scrutinModel->getCategoriesAvecCandidats($selectedId) : [];
        $historique    = $scrutinDetail ? $scrutinModel->getHistoriqueStatut($selectedId) : [];

        $this->render('admin/scrutins/index', [
            'scrutins'      => $scrutins,
            'scrutinDetail' => $scrutinDetail,
            'scrutinEdit'   => $scrutinEdit,
            'categories'    => $categories,
            'historique'    => $historique,
            'individus'     => $individuModel->getAll(),
            'search'        => $search,
            'statutFilter'  => $statut,
        ], 'admin');
    }

    public function create(): void
    {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['_csrf'] ?? null)) {
                $this->setFlash('error', 'Jeton CSRF invalide ou expiré.');
                $this->redirect('admin.scrutins.index');
            }

            try {
                $model = new ScrutinModel();
                $id    = $model->createScrutin($_POST);

                $this->setAuditContext([
                    'id_scrutin' => $id,
                    'detail'     => 'Création du scrutin #' . $id . ' (' . trim((string) ($_POST['titre'] ?? '')) . ')',
                ]);
                $this->setFlash('success', 'Scrutin créé en statut brouillon.');
                $this->redirect('admin.scrutins.index', ['scrutin' => $id]);
            } catch (Throwable $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('admin.scrutins.index');
            }
        }

        $this->redirect('admin.scrutins.index');
    }

    public function edit(): void
    {
        $this->authorizeAdmin();

        $idScrutin = (int) ($_GET['id'] ?? $_GET['scrutin'] ?? $_POST['id_scrutin'] ?? 0);
        if ($idScrutin <= 0) {
            $this->setFlash('error', 'Identifiant de scrutin manquant.');
            $this->redirect('admin.scrutins.index');
        }

        $scrutinModel = new ScrutinModel();
        $scrutin      = $scrutinModel->findById($idScrutin);

        if (!$scrutin) {
            $this->setFlash('error', 'Scrutin introuvable (ID #' . $idScrutin . ').');
            $this->redirect('admin.scrutins.index');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin.scrutins.index', ['scrutin' => $idScrutin, 'edit' => $idScrutin]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['_csrf'] ?? null)) {
                $this->setFlash('error', 'Jeton CSRF invalide ou expiré.');
                $this->redirect('admin.scrutins.index', ['scrutin' => $idScrutin, 'edit' => $idScrutin]);
            }

            try {
                $scrutinModel->updateScrutin($idScrutin, $_POST);

                $this->setAuditContext([
                    'id_scrutin' => $idScrutin,
                    'detail'     => 'Modification du scrutin #' . $idScrutin . ' (' . trim((string) ($_POST['titre'] ?? '')) . ')',
                ]);
                $this->setFlash('success', 'Scrutin mis à jour avec succès.');
                $this->redirect('admin.scrutins.index', ['scrutin' => $idScrutin]);
            } catch (Throwable $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('admin.scrutins.index', ['scrutin' => $idScrutin, 'edit' => $idScrutin]);
            }
        }
    }

    public function delete(): void
    {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->setFlash('error', 'Requête invalide ou jeton CSRF expiré.');
            $this->redirect('admin.scrutins.index');
        }

        $idScrutin = (int) ($_POST['id_scrutin'] ?? $_POST['id'] ?? 0);
        if ($idScrutin <= 0) {
            $this->setFlash('error', 'Identifiant de scrutin requis pour la suppression.');
            $this->redirect('admin.scrutins.index');
        }

        try {
            $model    = new ScrutinModel();
            $scrutin  = $model->findById($idScrutin);
            $titre    = (string) ($scrutin['TITRE'] ?? ('#' . $idScrutin));

            $model->delete($idScrutin);

            $this->setAuditContext([
                'id_scrutin' => $idScrutin,
                'detail'     => 'Suppression du scrutin #' . $idScrutin . ' (' . $titre . ')',
            ]);
            $this->setFlash('success', 'Scrutin supprimé avec succès.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('admin.scrutins.index');
    }

    public function storeScrutin(): void
    {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->redirect('admin.scrutins.index');
        }

        try {
            $model = new ScrutinModel();
            $id    = $model->createScrutin($_POST);
            $this->setAuditContext([
                'id_scrutin' => $id,
                'detail'     => 'Création du scrutin #' . $id . ' (' . ($_POST['titre'] ?? '') . ')',
            ]);
            $this->setFlash('success', 'Scrutin créé en statut brouillon.');
            $this->redirect('admin.scrutins.index', ['scrutin' => $id]);
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('admin.scrutins.index');
        }
    }

    public function changeStatut(): void
    {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->redirect('admin.scrutins.index');
        }

        $idScrutin     = (int) ($_POST['id_scrutin'] ?? 0);
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
        $this->authorizeAdmin();

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
        $this->authorizeAdmin();

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

    private function authorizeAdmin(): array
    {
        if (method_exists($this, 'requireAdmin')) {
            return $this->requireAdmin(self::ALLOWED_ROLES);
        }

        $sessionUser = $_SESSION['_admin_auth'] ?? $_SESSION['admin'] ?? null;
        if (!is_array($sessionUser) || empty($sessionUser)) {
            $this->setFlash('error', 'Veuillez vous connecter au back-office.');
            $this->redirect('auth.login');
        }

        $role = strtolower(trim((string) ($sessionUser['role'] ?? $sessionUser['ROLE'] ?? '')));
        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            http_response_code(403);
            $this->setFlash('error', 'Accès refusé : rôle insuffisant pour cette action.');
            $this->redirect('admin.dashboard');
        }

        return $sessionUser;
    }
}