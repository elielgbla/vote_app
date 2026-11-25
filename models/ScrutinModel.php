<?php
// models/ScrutinModel.php

declare(strict_types=1);

class ScrutinModel extends Model
{
    public function getAll(): array
    {
        $sql = "SELECT s.*,
                       (SELECT COUNT(*) FROM CATEGORIE c WHERE c.ID_SCRUTIN = s.ID_SCRUTIN) AS NB_CATEGORIES,
                       (SELECT COUNT(*) FROM EMARGEMENT e WHERE e.ID_SCRUTIN = s.ID_SCRUTIN) AS NB_EMARGEMENTS
                FROM SCRUTIN s
                ORDER BY s.ANNEE DESC, s.ID_SCRUTIN DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function findById(int $idScrutin): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM SCRUTIN WHERE ID_SCRUTIN = :id LIMIT 1");
        $stmt->execute([':id' => $idScrutin]);
        return $stmt->fetch() ?: null;
    }

    public function getScrutinActif(): ?array
    {
        return $this->db->query("SELECT * FROM SCRUTIN WHERE EST_ACTIF = 1 LIMIT 1")->fetch() ?: null;
    }

    public function getScrutinPublic(): ?array
    {
        $sql = "SELECT * FROM SCRUTIN
                WHERE STATUT IN ('actif', 'publie', 'cloture', 'planifie')
                ORDER BY FIELD(STATUT, 'actif', 'publie', 'cloture', 'planifie'), ANNEE DESC
                LIMIT 1";
        return $this->db->query($sql)->fetch() ?: null;
    }

    public function createScrutin(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO SCRUTIN (TITRE, ANNEE, DESCRIPTION, DATE_DEBUT, DATE_FIN, STATUT)
             VALUES (:titre, :annee, :description, :debut, :fin, 'brouillon')"
        );
        $stmt->execute([
            ':titre'       => trim($data['titre']),
            ':annee'       => (int) $data['annee'],
            ':description' => ($data['description'] ?? '') !== '' ? trim($data['description']) : null,
            ':debut'       => $data['date_debut'],
            ':fin'         => $data['date_fin'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateScrutin(int $idScrutin, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE SCRUTIN
             SET TITRE = :titre, ANNEE = :annee, DESCRIPTION = :description, DATE_DEBUT = :debut, DATE_FIN = :fin
             WHERE ID_SCRUTIN = :id AND STATUT IN ('brouillon', 'planifie')"
        );
        $stmt->execute([
            ':titre'       => trim($data['titre']),
            ':annee'       => (int) $data['annee'],
            ':description' => ($data['description'] ?? '') !== '' ? trim($data['description']) : null,
            ':debut'       => $data['date_debut'],
            ':fin'         => $data['date_fin'],
            ':id'          => $idScrutin,
        ]);
    }

    public function changeStatut(int $idScrutin, string $nouveauStatut): void
    {
        $stmt = $this->db->prepare("UPDATE SCRUTIN SET STATUT = :statut WHERE ID_SCRUTIN = :id");
        $stmt->execute([':statut' => $nouveauStatut, ':id' => $idScrutin]);
    }

    public function getHistoriqueStatut(int $idScrutin): array
    {
        $stmt = $this->db->prepare(
            "SELECT h.*, a.NOM_PRENOMS AS ADMIN_NOM
             FROM HISTORIQUE_STATUT h
             LEFT JOIN ADMIN a ON a.ID_ADMIN = h.ID_ADMIN
             WHERE h.ID_SCRUTIN = :scrutin
             ORDER BY h.DATE_CHANGEMENT DESC, h.ID_HISTORIQUE DESC"
        );
        $stmt->execute([':scrutin' => $idScrutin]);
        return $stmt->fetchAll();
    }

    // --- Catégories ---
    public function createCategorie(int $idScrutin, string $lib, ?string $description = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO CATEGORIE (ID_SCRUTIN, LIB, DESCRIPTION) VALUES (:scrutin, :lib, :desc)"
        );
        $stmt->execute([
            ':scrutin' => $idScrutin,
            ':lib'     => trim($lib),
            ':desc'    => ($description ?? '') !== '' ? trim($description) : null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateCategorie(int $idCategorie, string $lib, ?string $description = null): void
    {
        $stmt = $this->db->prepare(
            "UPDATE CATEGORIE SET LIB = :lib, DESCRIPTION = :desc WHERE ID_CATEGORIE = :id"
        );
        $stmt->execute([
            ':lib'  => trim($lib),
            ':desc' => ($description ?? '') !== '' ? trim($description) : null,
            ':id'   => $idCategorie,
        ]);
    }

    public function deleteCategorie(int $idCategorie): void
    {
        $stmt = $this->db->prepare("DELETE FROM CATEGORIE WHERE ID_CATEGORIE = :id");
        $stmt->execute([':id' => $idCategorie]);
    }

    // --- Candidatures ---
    public function addCandidature(int $idCategorie, string $cni): int
    {
        // Le trigger trg_candidature_ai crée automatiquement la ligne dans RESULTAT à 0 voix
        $stmt = $this->db->prepare(
            "INSERT INTO CANDIDATURE (ID_CATEGORIE, CNI) VALUES (:cat, :cni)"
        );
        $stmt->execute([
            ':cat' => $idCategorie,
            ':cni' => strtoupper(trim($cni)),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function removeCandidature(int $idCandidature): void
    {
        $this->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM RESULTAT WHERE ID_CANDIDATURE = :id AND NB_VOIX = 0")
                     ->execute([':id' => $idCandidature]);
            $this->db->prepare("DELETE FROM CANDIDATURE WHERE ID_CANDIDATURE = :id")
                     ->execute([':id' => $idCandidature]);
            $this->commit();
        } catch (Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function getCategoriesAvecCandidats(int $idScrutin): array
    {
        $stmtCat = $this->db->prepare(
            "SELECT ID_CATEGORIE, ID_SCRUTIN, LIB, DESCRIPTION
             FROM CATEGORIE
             WHERE ID_SCRUTIN = :scrutin
             ORDER BY ID_CATEGORIE ASC"
        );
        $stmtCat->execute([':scrutin' => $idScrutin]);
        $categories = $stmtCat->fetchAll();

        $stmtCand = $this->db->prepare(
            "SELECT ca.ID_CANDIDATURE, ca.ID_CATEGORIE, i.CNI, i.NOM_PRENOMS, i.TYPE_INDIVIDU,
                    i.SERVICE, i.PHOTO, s.LIB AS SITE_LIB, COALESCE(r.NB_VOIX, 0) AS NB_VOIX
             FROM CANDIDATURE ca
             JOIN INDIVIDU i ON i.CNI = ca.CNI
             LEFT JOIN SITE s ON s.CODE_SITE = i.CODE_SITE
             LEFT JOIN RESULTAT r ON r.ID_CANDIDATURE = ca.ID_CANDIDATURE
             WHERE ca.ID_CATEGORIE = :cat
             ORDER BY i.NOM_PRENOMS ASC"
        );

        foreach ($categories as &$cat) {
            $stmtCand->execute([':cat' => $cat['ID_CATEGORIE']]);
            $cat['candidats'] = $stmtCand->fetchAll();
        }

        return $categories;
    }
}