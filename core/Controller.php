<?php
// core/Controller.php

abstract class Controller
{
    protected array $config;
    protected \AuthManager $auth;
    protected array $auditMeta = [];

    public function __construct(array $config, \AuthManager $auth)
    {
        $this->config = $config;
        $this->auth   = $auth;
    }

     protected function buildViewData(array $data = []): array
{
    return array_merge([
        'baseUrl'      => $this->config['base_url'],
        'csrfToken'    => $this->csrfToken(),
        'currentAdmin' => $this->auth->user(),
        'flashSuccess' => $this->pullFlash('success'),
        'flashError'   => $this->pullFlash('error'),
    ], $data);
}

    /**
     * Permet à un contrôleur d'enrichir le journal sans appeler AuditLogger directement.
     */
    protected function setAuditContext(array $meta): void
    {
        $this->auditMeta = array_merge($this->auditMeta, $meta);
    }

    public function getAuditContext(): array
    {
        return $this->auditMeta;
    }

    protected function render(string $view, array $data = [], string $layout = 'admin'): void
    {
        $baseUrl = $this->config['base_url'];
        $csrfToken = $this->csrfToken();
        $currentAdmin = $this->auth->user();
        $flashSuccess = $this->pullFlash('success');
        $flashError   = $this->pullFlash('error');

        $viewFile = $this->config['base_path'] . '/views/' . ltrim($view, '/') . '.php';
        $layoutFile = $this->config['base_path'] . '/views/layouts/' . $layout . '.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException("Vue introuvable : {$view}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if (is_file($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    protected function json(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $action, array $params = []): void
    {
        $query = http_build_query(array_merge(['action' => $action], $params));
        header('Location: ' . $this->config['base_url'] . '/index.php?' . $query);
        exit;
    }

    protected function setFlash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type] = $message;
    }

    protected function pullFlash(string $type): ?string
    {
        if (!isset($_SESSION['_flash'][$type])) {
            return null;
        }
        $msg = $_SESSION['_flash'][$type];
        unset($_SESSION['_flash'][$type]);
        return $msg;
    }

    protected function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    protected function verifyCsrf(?string $token): bool
    {
        if (empty($_SESSION['_csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }


protected function requireAdmin(array $allowedRoles = []): array
{
    $sessionUser = $_SESSION['_admin_auth'] ?? $_SESSION['admin'] ?? null;

    if (!is_array($sessionUser) || empty($sessionUser)) {
        if (method_exists($this, 'setFlash')) {
            $this->setFlash('error', 'Veuillez vous connecter au back-office.');
        }
        $this->redirect('auth.login');
    }

    $role = strtolower(trim((string) ($sessionUser['role'] ?? $sessionUser['ROLE'] ?? '')));

    if (!empty($allowedRoles)) {
        $normalizedRoles = array_map(
            static fn(string $r): string => strtolower(trim($r)),
            $allowedRoles
        );

        if (!in_array($role, $normalizedRoles, true)) {
            http_response_code(403);
            if (method_exists($this, 'setFlash')) {
                $this->setFlash('error', 'Accès refusé : rôle insuffisant pour cette action.');
            }
            $this->redirect('admin.dashboard');
        }
    }

    return $sessionUser;
}
}