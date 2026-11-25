<?php
// controllers/AuthController.php

declare(strict_types=1);

class AuthController extends Controller
{
    public function login(): void
    {
        if ($this->auth->check()) {
            $this->redirect('admin.dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['_csrf'] ?? null)) {
                $this->setFlash('error', 'Jeton de sécurité invalide. Veuillez réessayer.');
                $this->redirect('auth.login');
            }

            $login = (string) ($_POST['login'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $admin = $this->auth->attemptLogin($login, $password);

            if ($admin) {
                $this->setAuditContext([
                    'id_admin' => $admin['id'],
                    'detail'   => 'Connexion réussie pour ' . $admin['login'] . ' (' . $admin['role'] . ')',
                ]);
                $this->setFlash('success', 'Bienvenue, ' . $admin['nom_prenoms'] . '.');
                $this->redirect('admin.dashboard');
            }

            $this->setAuditContext([
                'detail' => 'Échec de connexion pour l’identifiant : ' . substr(trim($login), 0, 50),
            ]);
            $this->setFlash('error', 'Identifiant ou mot de passe incorrect, ou compte inactif.');
            $this->redirect('auth.login');
        }

        $this->render('auth/login', [], 'public');
    }

    public function logout(): void
    {
        $user = $this->auth->user();
        if ($user) {
            $this->setAuditContext([
                'id_admin' => $user['id'],
                'detail'   => 'Déconnexion de ' . $user['login'],
            ]);
        }
        $this->auth->logout();
        $this->setFlash('success', 'Vous êtes déconnecté.');
        $this->redirect('auth.login');
    }
}