<?php
// core/AuditLogger.php

declare(strict_types=1);

class AuditLogger
{
    public function __construct(private PDO $pdo) {}

    public function record(array $context): void
    {
        $sql = "INSERT INTO JOURNAL
                    (ID_ADMIN, CNI, ID_SCRUTIN, ACTION, TABLE_CONCERNE, DETAIL, IP)
                VALUES
                    (:id_admin, :cni, :id_scrutin, :action, :table_concerne, :detail, :ip)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_admin'       => $context['id_admin'] ?? null,
            ':cni'            => $context['cni'] ?? null,
            ':id_scrutin'     => $context['id_scrutin'] ?? null,
            ':action'         => substr((string) ($context['action'] ?? 'ACTION'), 0, 30),
            ':table_concerne' => isset($context['table']) ? substr((string) $context['table'], 0, 40) : null,
            ':detail'         => $context['detail'] ?? null,
            ':ip'             => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);
    }
}