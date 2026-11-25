<?php
// controllers/SiteController.php

declare(strict_types=1);

class SiteController extends Controller
{
    private const ALLOWED_ROLES = ['super_admin'];

    public function index(): void
    {
        $this->authorizeAdmin();

        $siteModel = new SiteModel();
        $editCode  = strtoupper(trim((string) ($_GET['code'] ?? '')));
        $siteEdit  = $editCode !== '' ? $siteModel->findByCode($editCode) : null;

        $this->render('admin/sites/index', [
            'sites'    => $siteModel->getAll(),
            'admins'   => $siteModel->getAdmins(),
            'siteEdit' => $siteEdit,
        ], 'admin');
    }

    public function create(): void
    {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(false);
            return;
        }

        $siteModel = new SiteModel();

        $this->render('admin/sites/create', [
            'site'   => null,
            'admins' => $siteModel->getAdmins(),
        ], 'admin');
    }

    public function edit(): void
    {
        $this->authorizeAdmin();

        $code = strtoupper(trim((string) ($_GET['code'] ?? $_POST['code_site'] ?? '')));
        if ($code === '') {
            $this->setFlash('error', 'Code du site manquant.');
            $this->redirect('admin.sites.index');
        }

        $siteModel = new SiteModel();
        $site      = $siteModel->findByCode($code);

        if (!$site) {
            $this->setFlash('error', 'Site introuvable pour le code ' . $code . '.');
            $this->redirect('admin.sites.index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(true, $site);
            return;
        }

        $this->render('admin/sites/edit', [
            'site'   => $site,
            'admins' => $siteModel->getAdmins(),
        ], 'admin');
    }

    public function store(): void
    {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin.sites.index');
        }

        $code      = strtoupper(trim((string) ($_POST['code_site'] ?? '')));
        $siteModel = new SiteModel();
        $existing  = $code !== '' ? $siteModel->findByCode($code) : null;

        $this->handleSave($existing !== null, $existing);
    }

    public function delete(): void
    {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->setFlash('error', 'Requête invalide ou jeton CSRF expiré.');
            $this->redirect('admin.sites.index');
        }

        $code = strtoupper(trim((string) ($_POST['code_site'] ?? '')));
        if ($code === '') {
            $this->setFlash('error', 'Code du site requis pour la suppression.');
            $this->redirect('admin.sites.index');
        }

        try {
            $siteModel = new SiteModel();
            $siteModel->delete($code);

            $this->setAuditContext([
                'detail' => 'Suppression du site ' . $code,
            ]);
            $this->setFlash('success', 'Site supprimé avec succès.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('admin.sites.index');
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
        if (!$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->setFlash('error', 'Jeton CSRF invalide ou expiré.');
            $this->redirect(
                $isUpdate && $existing ? 'admin.sites.edit' : 'admin.sites.create',
                $isUpdate && $existing ? ['code' => $existing['CODE_SITE']] : []
            );
        }

        $sessionUser = $_SESSION['_admin_auth'] ?? $_SESSION['admin'] ?? [];
        $defaultAdminId = (int) ($sessionUser['id'] ?? $sessionUser['ID_ADMIN'] ?? ($existing['ID_ADMIN'] ?? 0));

        $code    = strtoupper(trim((string) ($_POST['code_site'] ?? ($existing['CODE_SITE'] ?? ''))));
        $idAdmin = (int) ($_POST['id_admin'] ?? $defaultAdminId);

        $payload = [
            'code_site' => $code,
            'id_admin'  => $idAdmin,
            'lib'       => trim((string) ($_POST['lib'] ?? ($existing['LIB'] ?? ''))),
            'ville'     => trim((string) ($_POST['ville'] ?? ($existing['VILLE'] ?? ''))),
        ];

        try {
            $siteModel = new SiteModel();
            if ($isUpdate) {
                $siteModel->update($code, $payload);
            } else {
                $siteModel->create($payload);
            }

            $this->setAuditContext([
                'detail' => ($isUpdate ? 'Modification' : 'Création') . ' du site ' . $code . ' (' . $payload['lib'] . ')',
            ]);
            $this->setFlash('success', $isUpdate ? 'Site mis à jour avec succès.' : 'Site enregistré avec succès.');
            $this->redirect('admin.sites.index');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
            if ($isUpdate && $code !== '') {
                $this->redirect('admin.sites.edit', ['code' => $code]);
            }
            $this->redirect('admin.sites.create');
        }
    }
}