<?php
// core/Router.php

class Router
{
    public function __construct(
        private array $config,
        private array $actions,
        private AuthManager $auth,
        private AuditLogger $logger
    ) {}

    public function dispatch(string $actionKey): void
    {
        $actionConfig = $this->actions[$actionKey] ?? $this->actions['public.ecran'];
        $resolvedAction = isset($this->actions[$actionKey]) ? $actionKey : 'public.ecran';

        // 1. Contrôle d'authentification et de rôle
        if (!empty($actionConfig['auth']) && !$this->auth->check()) {
            $_SESSION['_flash']['error'] = 'Veuillez vous connecter pour accéder au back-office.';
            header('Location: ' . $this->config['base_url'] . '/index.php?action=auth.login');
            exit;
        }

        if (!empty($actionConfig['roles']) && !$this->auth->hasRole($actionConfig['roles'])) {
            http_response_code(403);
            exit('Accès refusé : rôle insuffisant pour cette action.');
        }

        // 2. Injection de l'admin courant pour les triggers MySQL
        $adminUser = $this->auth->user();
        $idAdmin = $adminUser['id'] ?? null;
        Database::setCurrentAdmin($idAdmin);

        // 3. Instanciation du contrôleur et exécution de l'action
        $controllerName = $actionConfig['controller'];
        $methodName     = $actionConfig['method'];

        $controllerFile = $this->config['base_path'] . '/controllers/' . $controllerName . '.php';
        if (!is_file($controllerFile)) {
            throw new RuntimeException("Contrôleur introuvable : {$controllerName}");
        }
        require_once $controllerFile;

        /** @var Controller $controller */
        $controller = new $controllerName($this->config, $this->auth);
        $controller->$methodName();

        // 4. Journalisation automatique si activée dans config/actions.php
        if (!empty($actionConfig['log'])) {
            $meta = $controller->getAuditContext();
            $this->logger->record([
                'id_admin'   => $meta['id_admin'] ?? $idAdmin,
                'cni'        => $meta['cni'] ?? null,
                'id_scrutin' => $meta['id_scrutin'] ?? null,
                'action'     => $resolvedAction,
                'table'      => $meta['table'] ?? ($actionConfig['table'] ?? null),
                'detail'     => $meta['detail'] ?? ('Action exécutée : ' . $resolvedAction),
            ]);
        }
    }
}