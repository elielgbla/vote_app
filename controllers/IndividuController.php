<?php
// controllers/IndividuController.php

declare(strict_types=1);

class IndividuController extends \Controller
{
    private const ALLOWED_ROLES = ['super_admin', 'rh'];

    public function index(): void
    {
        $this->authorizeAdmin();

        $cni = strtoupper(trim((string) ($_GET['cni'] ?? '')));
        $individuEdit = null;
        if ($cni !== '') {
            $individuEdit = (new \IndividuModel())->findByCni($cni);
            if ($individuEdit === null) {
                $this->setFlash('error', 'Individu introuvable pour la CNI ' . $cni . '.');
                $this->redirect('admin.individus.index');
            }
        }

        $this->renderIndex($individuEdit);
    }

    public function create(): void
    {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(false);
            return;
        }

        $this->renderIndex();
    }

    public function edit(): void
    {
        $this->authorizeAdmin();

        $cni = strtoupper(trim((string) ($_GET['cni'] ?? $_POST['cni'] ?? '')));
        if ($cni === '') {
            $this->setFlash('error', 'Identifiant CNI manquant.');
            $this->redirect('admin.individus.index');
        }

        $individuModel = new \IndividuModel();
        $individu      = $individuModel->findByCni($cni);

        if (!$individu) {
            $this->setFlash('error', 'Individu introuvable pour la CNI ' . $cni . '.');
            $this->redirect('admin.individus.index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(true, $individu);
            return;
        }

        $this->renderIndex($individu);
    }

    public function store(): void
    {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin.individus.index');
        }

        $cni           = strtoupper(trim((string) ($_POST['cni'] ?? '')));
        $individuModel = new \IndividuModel();
        $existing      = $cni !== '' ? $individuModel->findByCni($cni) : null;

        $this->handleSave($existing !== null, $existing);
    }

    public function delete(): void
    {
        $this->authorizeAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->setFlash('error', 'Requête invalide ou jeton CSRF expiré.');
            $this->redirect('admin.individus.index');
        }

        $cni = strtoupper(trim((string) ($_POST['cni'] ?? '')));
        if ($cni === '') {
            $this->setFlash('error', 'CNI requise pour la suppression.');
            $this->redirect('admin.individus.index');
        }

        try {
            $individuModel = new \IndividuModel();
            $individu      = $individuModel->findByCni($cni);

            if (!$individu) {
                throw new RuntimeException('Individu introuvable.');
            }

            $photoName = (string) ($individu['PHOTO'] ?? '');
            $individuModel->delete($cni);

            if ($photoName !== '') {
                (new \PhotoStorage($this->config))->delete($photoName);
            }

            $this->setAuditContext([
                'cni'    => $cni,
                'detail' => 'Suppression de l’individu CNI ' . $cni,
            ]);
            $this->setFlash('success', 'Individu supprimé avec succès.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('admin.individus.index');
    }

    public function photo(): void
    {
        $file     = basename((string) ($_GET['f'] ?? ''));
        $fullPath = rtrim((string) ($this->config['uploads_dir'] ?? ''), '/\\') . DIRECTORY_SEPARATOR . $file;

        if ($file === '' || !is_file($fullPath)) {
            $fallback = rtrim((string) ($this->config['public_path'] ?? ''), '/\\') . '/assets/img/default-avatar.svg';
            header('Content-Type: image/svg+xml');
            if (is_file($fallback)) {
                readfile($fallback);
            }
            exit;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($fullPath) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    private function authorizeAdmin(): array
    {
        return $this->requireAdmin(self::ALLOWED_ROLES);
    }

    private function handleSave(bool $isUpdate, ?array $existing = null): void
    {
        if (!$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->setFlash('error', 'Jeton CSRF invalide ou expiré.');
            $this->redirect(
                $isUpdate && $existing ? 'admin.individus.edit' : 'admin.individus.create',
                $isUpdate && $existing ? ['cni' => $existing['CNI']] : []
            );
        }

        $cni     = strtoupper(trim((string) ($_POST['cni'] ?? ($existing['CNI'] ?? ''))));
        $payload = $_POST;
        $payload['cni'] = $cni;

        try {
            $photoFile = $existing['PHOTO'] ?? null;
            if (!empty($_FILES['photo']['name']) && (int) ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $photoStorage = new \PhotoStorage($this->config);
                $uploadedName = $photoStorage->upload($_FILES['photo']);
                if ($uploadedName !== null && $uploadedName !== '') {
                    if ($isUpdate && !empty($photoFile) && basename((string) $photoFile) !== basename($uploadedName)) {
                        $oldPath = rtrim((string) ($this->config['uploads_dir'] ?? ''), '/\\') . DIRECTORY_SEPARATOR . basename((string) $photoFile);
                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                    $photoFile = $uploadedName;
                }
            }

            $model = new \IndividuModel();
            if ($isUpdate) {
                $model->update($cni, $payload, $photoFile);
            } else {
                $model->create($payload, $photoFile);
            }

            $this->setAuditContext([
                'cni'    => $cni,
                'detail' => ($isUpdate ? 'Modification' : 'Création') . ' de l’individu CNI ' . $cni,
            ]);
            $this->setFlash('success', $isUpdate ? 'Individu mis à jour avec succès.' : 'Individu enregistré avec succès.');
            $this->redirect('admin.individus.index');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
            if ($isUpdate && $cni !== '') {
                $this->redirect('admin.individus.edit', ['cni' => $cni]);
            }
            $this->redirect('admin.individus.create');
        }
    }

    private function renderIndex(?array $individuEdit = null): void
    {
        $individuModel = new \IndividuModel();
        $siteModel     = new \SiteModel();
        $search        = trim((string) ($_GET['q'] ?? ''));
        $type          = trim((string) ($_GET['type'] ?? ''));

        $this->render('admin/individus/index', [
            'individus'    => $individuModel->getAll($search, $type),
            'sites'        => $siteModel->getAll(),
            'search'       => $search,
            'type'         => $type,
            'individuEdit' => $individuEdit,
        ], 'admin');
    }
}