<?php
// controllers/CategorieController.php

declare(strict_types=1);

class CategorieController extends Controller
{
    private const ALLOWED_ROLES = ['super_admin', 'rh'];

    public function index(): void
    {
        $this->authorizeAdmin();

        $categorieModel = new CategorieModel();
        $scrutins       = $categorieModel->getScrutins();

        $idScrutin = isset($_GET['scrutin_id']) && $_GET['scrutin_id'] !== ''
            ? (int) $_GET['scrutin_id']
            : null;
        $search = trim((string) ($_GET['q'] ?? ''));

        $editId        = (int) ($_GET['id'] ?? 0);
        $categorieEdit = $editId > 0 ? $categorieModel->findById($editId) : null;

        $this->render('admin/categories/index', [
            'categories'    => $categorieModel->getAll($idScrutin, $search),
            'scrutins'      => $scrutins,
            'scrutinId'     => $idScrutin,
            'search'        => $search,
            'categorieEdit' => $categorieEdit,
        ], 'admin');
    }

    public function create(): void
    {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(false);
            return;
        }

        $categorieModel = new CategorieModel();
        $defaultScrutin = (int) ($_GET['scrutin_id'] ?? 0);

        $this->render('admin/categories/create', [
            'categorie'      => null,
            'scrutins'       => $categorieModel->getScrutins(),
            'defaultScrutin' => $defaultScrutin,
        ], 'admin');
    }

    public function edit(): void
    {
        $this->authorizeAdmin();

        $id = (int) ($_GET['id'] ?? $_POST['id_categorie'] ?? 0);
        if ($id <= 0) {
            $this->setFlash('error', 'Identifiant de catégorie manquant.');
            $this->redirect('admin.categories.index');
        }

        $categorieModel = new CategorieModel();
        $categorie      = $categorieModel->findById($id);

        if (!$categorie) {
            $this->setFlash('error', 'Catégorie introuvable (ID #' . $id . ').');
            $this->redirect('admin.categories.index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(true, $categorie);
            return;
        }

        $this->render('admin/categories/edit', [
            'categorie' => $categorie,
            'scrutins'  => $categorieModel->getScrutins(),
        ], 'admin');
    }

    public function store(): void
    {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin.categories.index');
        }

        $id = (int) ($_POST['id_categorie'] ?? 0);
        $categorieModel = new CategorieModel();
        $existing = $id > 0 ? $categorieModel->findById($id) : null;

        $this->handleSave($existing !== null, $existing);
    }

    public function delete(): void
    {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->setFlash('error', 'Requête invalide ou jeton CSRF expiré.');
            $this->redirect('admin.categories.index');
        }

        $id = (int) ($_POST['id_categorie'] ?? 0);
        $scrutinFilter = (int) ($_POST['scrutin_id'] ?? 0);

        if ($id <= 0) {
            $this->setFlash('error', 'Identifiant de catégorie requis pour la suppression.');
            $this->redirect('admin.categories.index', $scrutinFilter > 0 ? ['scrutin_id' => $scrutinFilter] : []);
        }

        try {
            $categorieModel = new CategorieModel();
            $existing       = $categorieModel->findById($id);
            $categorieModel->delete($id);

            $lib = (string) ($existing['LIB'] ?? ('#' . $id));
            $this->setAuditContext([
                'detail' => 'Suppression de la catégorie ' . $lib . ' (ID #' . $id . ')',
            ]);
            $this->setFlash('success', 'Catégorie supprimée avec succès.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('admin.categories.index', $scrutinFilter > 0 ? ['scrutin_id' => $scrutinFilter] : []);
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

    private function handleSave(bool $isUpdate, ?array $existing = null): void
    {
        $id = (int) ($existing['ID_CATEGORIE'] ?? $_POST['id_categorie'] ?? 0);

        if (!$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->setFlash('error', 'Jeton CSRF invalide ou expiré.');
            $this->redirect(
                $isUpdate && $id > 0 ? 'admin.categories.edit' : 'admin.categories.create',
                $isUpdate && $id > 0 ? ['id' => $id] : []
            );
        }

        $idScrutin = (int) ($_POST['id_scrutin'] ?? ($existing['ID_SCRUTIN'] ?? 0));
        $payload = [
            'id_scrutin'  => $idScrutin,
            'lib'         => trim((string) ($_POST['lib'] ?? ($existing['LIB'] ?? ''))),
            'description' => trim((string) ($_POST['description'] ?? ($existing['DESCRIPTION'] ?? ''))),
        ];

        try {
            $categorieModel = new CategorieModel();
            if ($isUpdate && $id > 0) {
                $categorieModel->update($id, $payload);
            } else {
                $id = $categorieModel->create($payload);
            }

            $this->setAuditContext([
                'detail' => ($isUpdate ? 'Modification' : 'Création') . ' de la catégorie « ' . $payload['lib'] . ' » (ID #' . $id . ')',
            ]);
            $this->setFlash('success', $isUpdate ? 'Catégorie mise à jour avec succès.' : 'Catégorie enregistrée avec succès.');
            $this->redirect('admin.categories.index', $idScrutin > 0 ? ['scrutin_id' => $idScrutin] : []);
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
            if ($isUpdate && $id > 0) {
                $this->redirect('admin.categories.edit', ['id' => $id]);
            }
            $this->redirect('admin.categories.create', $idScrutin > 0 ? ['scrutin_id' => $idScrutin] : []);
        }
    }
}