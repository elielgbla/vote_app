<?php
// core/CodeVoteManager.php

declare(strict_types=1);

class CodeVoteManager
{
    public function __construct(private PDO $pdo) {}

    public function hashCode(string $plainCode): string
    {
        return hash('sha256', strtoupper(trim($plainCode)));
    }

    /**
     * Génère un lot de codes et retourne les codes en clair une seule fois.
     */
    public function generateBatch(int $idScrutin, int $idAdmin, string $typePorteur, int $quantite, ?string $codeSite = null): array
    {
        $this->pdo->beginTransaction();

        try {
            $stmtLot = $this->pdo->prepare(
                "INSERT INTO LOT_CODES (ID_SCRUTIN, ID_ADMIN, CODE_SITE, TYPE_PORTEUR, QUANTITE)
                 VALUES (:scrutin, :admin, :site, :type_porteur, :qte)"
            );
            $stmtLot->execute([
                ':scrutin'      => $idScrutin,
                ':admin'        => $idAdmin,
                ':site'         => $codeSite ?: null,
                ':type_porteur' => $typePorteur,
                ':qte'          => $quantite,
            ]);
            $idLot = (int) $this->pdo->lastInsertId();

            $stmtCode = $this->pdo->prepare(
                "INSERT INTO CODE_VOTE (ID_SCRUTIN, ID_LOT, HASH_CODE, TYPE_PORTEUR, STATUT)
                 VALUES (:scrutin, :lot, :hash_code, :type_porteur, 'genere')"
            );

            $plainCodes = [];
            for ($i = 0; $i < $quantite; $i++) {
                $codeClair = strtoupper(bin2hex(random_bytes(4)));
                $stmtCode->execute([
                    ':scrutin'      => $idScrutin,
                    ':lot'          => $idLot,
                    ':hash_code'    => $this->hashCode($codeClair),
                    ':type_porteur' => $typePorteur,
                ]);
                $plainCodes[] = $codeClair;
            }

            $this->pdo->commit();
            return ['id_lot' => $idLot, 'codes' => $plainCodes];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function markDistributed(int $idCode): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE CODE_VOTE
             SET STATUT = 'distribue', DATE_DISTRIBUTION = NOW()
             WHERE ID_CODE = :id AND STATUT = 'genere'"
        );
        $stmt->execute([':id' => $idCode]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Marque un code comme utilisé uniquement s'il est 'distribue' pour le scrutin actif.
     */
    public function consumeForVote(string $plainCode, int $idScrutin, string $typeElecteur): ?int
    {
        $hash = $this->hashCode($plainCode);
        $stmt = $this->pdo->prepare(
            "UPDATE CODE_VOTE
             SET STATUT = 'utilise', DATE_UTILISATION = CURDATE()
             WHERE HASH_CODE = :hash
               AND ID_SCRUTIN = :scrutin
               AND TYPE_PORTEUR = :type_porteur
               AND STATUT = 'distribue'"
        );
        $stmt->execute([
            ':hash'         => $hash,
            ':scrutin'      => $idScrutin,
            ':type_porteur' => $typeElecteur,
        ]);

        if ($stmt->rowCount() === 0) {
            return null;
        }

        $lookup = $this->pdo->prepare("SELECT ID_CODE FROM CODE_VOTE WHERE HASH_CODE = :hash LIMIT 1");
        $lookup->execute([':hash' => $hash]);
        $idCode = $lookup->fetchColumn();

        return $idCode !== false ? (int) $idCode : null;
    }
}