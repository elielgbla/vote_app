<?php
// core/AuthManager.php

declare(strict_types=1);

class AuthManager
{
    public function __construct(private PDO $pdo) {}

    public function attemptLogin(string $login, string $password): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT ID_ADMIN, LOGIN, NOM_PRENOMS, MOT_DE_PASSE, ROLE, ACTIF
             FROM ADMIN
             WHERE LOGIN = :login
             LIMIT 1"
        );
        $stmt->execute([':login' => trim($login)]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            !$admin
            || (int) $admin['ACTIF'] !== 1
            || !password_verify($password, (string) $admin['MOT_DE_PASSE'])
        ) {
            return null;
        }

        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id'          => (int) $admin['ID_ADMIN'],
            'login'       => (string) $admin['LOGIN'],
            'nom_prenoms' => (string) $admin['NOM_PRENOMS'],
            'role'        => (string) $admin['ROLE'],
        ];

        return $_SESSION['admin'];
    }

    public function logout(): void
    {
        unset($_SESSION['admin']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function user(): ?array
    {
        return $_SESSION['admin'] ?? null;
    }

    public function check(): bool
    {
        return isset($_SESSION['admin']['id']);
    }

    public function hasRole(array $allowedRoles): bool
    {
        if (!$this->check()) {
            return false;
        }
        return in_array($_SESSION['admin']['role'], $allowedRoles, true);
    }
}