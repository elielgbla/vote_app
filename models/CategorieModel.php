<?php
// models/CategorieModel.php

declare(strict_types=1);

class CategorieModel extends Model
{
    public function getAll(?int $idScrutin = null, string $search = ''): array
    {
        $sql = "SELECT
                    cat.ID_CATEGORIE,
                    cat.ID_SCRUTIN,
                    cat.LIB,
                    cat.DESCRIPTION,
                    s.TITRE AS SCRUTIN_TITRE,
                    s.ANNEE AS SCRUTIN_ANNEE,
                    s.STATUT AS SCRUTIN_STATUT,
                    (SELECT COUNT(*) FROM CANDIDATURE cand WHERE cand.ID_CATEGORIE = cat.ID_CATEGORIE) AS NB_CANDIDATS
                FROM CATEGORIE cat
                INNER JOIN SCRUTIN s ON s.ID_SCRUTIN = cat.ID_SCRUTIN
                WHERE 1 = 1";

        $params = [];

        if ($idScrutin !== null && $idScrutin > 0) {
            $sql .= " AND cat.ID_SCRUTIN = :id_scrutin";
            $params[':id_scrutin'] = $idScrutin;
        }

        $search = trim($search);
        if ($search !== '') {
            $sql .= " AND (cat.LIB LIKE :q_lib OR cat.DESCRIPTION LIKE :q_desc OR s.TITRE LIKE :q_scrutin)";
            $like = '%' . $search . '%';
            $params[':q_lib']     = $like;
            $params[':q_desc']    = $like;
            $params[':q_scrutin'] = $like;
        }

        $sql .= " ORDER BY s.ID_SCRUTIN DESC, cat.LIB ASC, cat.ID_CATEGORIE ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getScrutins(): array
    {
        $sql = "SELECT ID_SCRUTIN, TITRE, ANNEE, STATUT
                FROM SCRUTIN
                ORDER BY ID_SCRUTIN DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function findById(int $idCategorie): ?array
    {
        if ($idCategorie <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT
                cat.ID_CATEGORIE,
                cat.ID_SCRUTIN,
                cat.LIB,
                cat.DESCRIPTION,
                s.TITRE AS SCRUTIN_TITRE,
                s.ANNEE AS SCRUTIN_ANNEE
             FROM CATEGORIE cat
             INNER JOIN SCRUTIN s ON s.ID_SCRUTIN = cat.ID_SCRUTIN
             WHERE cat.ID_CATEGORIE = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $idCategorie]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(array $data): int
    {
        $payload = $this->normalizeAndValidate($data);

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO CATEGORIE (ID_SCRUTIN, LIB, DESCRIPTION)
                 VALUES (:id_scrutin, :lib, :description)"
            );
            $stmt->execute([
                ':id_scrutin'  => $payload['id_scrutin'],
                ':lib'         => $payload['lib'],
                ':description' => $payload['description'],
            ]);

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new RuntimeException(
                    'La catégorie « ' . $payload['lib'] . ' » existe déjà pour ce scrutin.'
                );
            }
            throw $e;
        }
    }

    public function update(int $idCategorie, array $data): void
    {
        if ($idCategorie <= 0) {
            throw new InvalidArgumentException('Identifiant de catégorie invalide.');
        }

        $existing = $this->findById($idCategorie);
        if ($existing === null) {
            throw new RuntimeException('Catégorie introuvable (ID #' . $idCategorie . ').');
        }

        $merged = array_merge($existing, $data);
        $payload = $this->normalizeAndValidate($merged);

        try {
            $stmt = $this->db->prepare(
                "UPDATE CATEGORIE
                 SET ID_SCRUTIN  = :id_scrutin,
                     LIB         = :lib,
                     DESCRIPTION = :description
                 WHERE ID_CATEGORIE = :id"
            );
            $stmt->execute([
                ':id_scrutin'  => $payload['id_scrutin'],
                ':lib'         => $payload['lib'],
                ':description' => $payload['description'],
                ':id'          => $idCategorie,
            ]);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new RuntimeException(
                    'La catégorie « ' . $payload['lib'] . ' » existe déjà pour ce scrutin.'
                );
            }
            throw $e;
        }
    }

    public function delete(int $idCategorie): void
    {
        if ($idCategorie <= 0) {
            throw new InvalidArgumentException('Identifiant de catégorie invalide.');
        }

        if ($this->findById($idCategorie) === null) {
            throw new RuntimeException('Catégorie introuvable (ID #' . $idCategorie . ').');
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM CATEGORIE WHERE ID_CATEGORIE = :id");
            $stmt->execute([':id' => $idCategorie]);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new RuntimeException(
                    'Impossible de supprimer cette catégorie : des candidatures y sont déjà associées.'
                );
            }
            throw $e;
        }
    }

    private function normalizeAndValidate(array $data): array
    {
        $idScrutin   = (int) ($data['id_scrutin'] ?? $data['ID_SCRUTIN'] ?? 0);
        $lib         = trim((string) ($data['lib'] ?? $data['LIB'] ?? ''));
        $description = trim((string) ($data['description'] ?? $data['DESCRIPTION'] ?? ''));

        if ($idScrutin <= 0) {
            throw new InvalidArgumentException('Le scrutin associé est obligatoire.');
        }

        $stmtScrutin = $this->db->prepare("SELECT ID_SCRUTIN FROM SCRUTIN WHERE ID_SCRUTIN = :id LIMIT 1");
        $stmtScrutin->execute([':id' => $idScrutin]);
        if ($stmtScrutin->fetchColumn() === false) {
            throw new InvalidArgumentException('Le scrutin sélectionné est introuvable.');
        }

        if ($lib === '') {
            throw new InvalidArgumentException('Le libellé de la catégorie est obligatoire.');
        }
        if (mb_strlen($lib) > 100) {
            throw new InvalidArgumentException('Le libellé ne doit pas dépasser 100 caractères.');
        }

        return [
            'id_scrutin'  => $idScrutin,
            'lib'         => $lib,
            'description' => $description !== '' ? $description : null,
        ];
    }
}