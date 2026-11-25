<?php
// core/Database.php

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require dirname(__DIR__) . '/config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                error_log('Erreur connexion BD : ' . $e->getMessage());
                throw new RuntimeException('Impossible de se connecter à la base de données.');
            }
        }

        return self::$instance;
    }

    /**
     * Définit l'administrateur courant dans la session MySQL
     * pour alimenter automatiquement HISTORIQUE_STATUT via le trigger trg_scrutin_au.
     */
    public static function setCurrentAdmin(?int $idAdmin): void
    {
        $pdo = self::getConnection();
        if ($idAdmin !== null) {
            $stmt = $pdo->prepare('SET @admin_courant = :id');
            $stmt->execute([':id' => $idAdmin]);
        } else {
            $pdo->exec('SET @admin_courant = NULL');
        }
    }
}