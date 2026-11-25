<?php
// controllers/OrganisationController.php

declare(strict_types=1);

class OrganisationController extends \Controller
{
    public function sitesIndex(): void
    {
        $this->renderSitesIndex();
    }

    public function siteCreate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['_csrf'] ?? null)) {
                $this->setFlash('error', 'Jeton CSRF invalide ou expiré.');
                $this->redirect('admin.sites.index');
            }

            try {
                $siteModel = new \SiteModel();
                $code = $siteModel->create($_POST);
                $this->setAuditContext([
                    'table'  => 'SITE',
                    'detail' => 'Création du site ' . $code . ' (' . ($_POST['lib'] ?? '') . ')',
                ]);
                $this->setFlash('success', 'Site ' . $code . ' créé.');
            } catch (Throwable $e) {
                $this->setFlash('error', $e->getMessage());
            }

            $this->redirect('admin.sites.index');
        }

        $siteModel = new \SiteModel();
        $this->render('admin/sites/create', [
            'admins' => $siteModel->getAdmins(),
        ], 'admin');
    }

    public function siteEdit(): void
    {
        $code = strtoupper(trim((string) ($_GET['code'] ?? $_POST['code_site'] ?? '')));
        if ($code === '') {
            $this->setFlash('error', 'Code du site manquant.');
            $this->redirect('admin.sites.index');
        }

        $siteModel = new \SiteModel();
        $site = $siteModel->findByCode($code);
        if ($site === null) {
            $this->setFlash('error', 'Site introuvable pour le code ' . $code . '.');
            $this->redirect('admin.sites.index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['_csrf'] ?? null)) {
                $this->setFlash('error', 'Jeton CSRF invalide ou expiré.');
                $this->redirect('admin.sites.edit', ['code' => $code]);
            }

            try {
                $siteModel->update($code, $_POST);
                $this->setAuditContext([
                    'table'  => 'SITE',
                    'detail' => 'Mise à jour du site ' . $code . ' (' . ($_POST['lib'] ?? '') . ')',
                ]);
                $this->setFlash('success', 'Site ' . $code . ' mis à jour.');
                $this->redirect('admin.sites.index');
            } catch (Throwable $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('admin.sites.edit', ['code' => $code]);
            }
        }

        $this->renderSitesIndex($site);
    }

    public function siteDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->setFlash('error', 'Requête invalide ou jeton CSRF expiré.');
            $this->redirect('admin.sites.index');
        }

        $code = strtoupper(trim((string) ($_POST['code_site'] ?? '')));
        try {
            (new \SiteModel())->delete($code);
            $this->setAuditContext([
                'table'  => 'SITE',
                'detail' => 'Suppression du site ' . $code,
            ]);
            $this->setFlash('success', 'Site ' . $code . ' supprimé.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('admin.sites.index');
    }

    public function siteStore(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->redirect('admin.sites.index');
        }

        try {
            $code = strtoupper(trim((string) ($_POST['code_site'] ?? '')));
            $siteModel = new \SiteModel();

            if (!empty($_POST['mode_edit']) && $code !== '') {
                $siteModel->update($code, $_POST);
                $this->setAuditContext([
                    'detail' => 'Mise à jour du site ' . $code . ' (' . ($_POST['lib'] ?? '') . ')',
                ]);
                $this->setFlash('success', 'Site ' . $code . ' mis à jour.');
            } else {
                $siteModel->create($_POST);
                $this->setAuditContext([
                    'detail' => 'Création du site ' . $code . ' (' . ($_POST['lib'] ?? '') . ')',
                ]);
                $this->setFlash('success', 'Site ' . $code . ' créé.');
            }
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('admin.sites.index');
    }

    public function adminsIndex(): void
    {
        $adminModel = new \AdminModel();

        $this->render('admin/admins/index', [
            'admins' => $adminModel->getAll(),
        ], 'admin');
    }

    public function adminStore(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->redirect('admin.admins.index');
        }

        try {
            $adminModel = new \AdminModel();
            $idEdit = !empty($_POST['id_admin']) ? (int) $_POST['id_admin'] : 0;

            if ($idEdit > 0) {
                $adminModel->update($idEdit, $_POST);
                $this->setAuditContext([
                    'detail' => 'Modification du compte administrateur #' . $idEdit . ' (' . ($_POST['login'] ?? '') . ')',
                ]);
                $this->setFlash('success', 'Compte administrateur mis à jour.');
            } else {
                $newId = $adminModel->create($_POST);
                $this->setAuditContext([
                    'detail' => 'Création du compte administrateur #' . $newId . ' (' . ($_POST['login'] ?? '') . ')',
                ]);
                $this->setFlash('success', 'Compte administrateur créé.');
            }
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('admin.admins.index');
    }

    public function adminToggle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->redirect('admin.admins.index');
        }

        $idAdmin = (int) ($_POST['id_admin'] ?? 0);
        $current = $this->auth->user();

        if ($idAdmin <= 0 || ($current && (int) $current['id'] === $idAdmin)) {
            $this->setFlash('error', 'Impossible de désactiver votre propre compte actif.');
            $this->redirect('admin.admins.index');
        }

        try {
            $adminModel = new \AdminModel();
            $adminModel->toggleActif($idAdmin);
            $this->setAuditContext([
                'detail' => 'Bascule actif/inactif sur l’administrateur #' . $idAdmin,
            ]);
            $this->setFlash('success', 'Statut du compte administrateur mis à jour.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('admin.admins.index');
    }

    private function renderSitesIndex(?array $siteEdit = null): void
    {
        $siteModel = new \SiteModel();

        $this->render('admin/sites/index', [
            'sites'    => $siteModel->getAll(),
            'admins'   => $siteModel->getAdmins(),
            'siteEdit' => $siteEdit,
        ], 'admin');
    }
}