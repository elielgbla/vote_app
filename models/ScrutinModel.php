<?php
// models/ScrutinModel.php

declare(strict_types=1);

class ScrutinModel extends Model
{
    private const ALLOWED_STATUTS = ['brouillon', 'planifie', 'actif', 'cloture', 'publie'];
    private const EDITABLE_STATUTS = ['brouillon', 'planifie'];

    public function getAll(string $search = '', string $statut = ''): array
    {
        $sql = "SELECT s.*,
                       (SELECT COUNT(*) FROM CATEGORIE c WHERE c.ID_SCRUTIN = s.ID_SCRUTIN) AS NB_CATEGORIES,
                       (SELECT COUNT(*) FROM EMARGEMENT e WHERE e.ID_SCRUTIN = s.ID_SCRUTIN) AS NB_EMARGEMENTS
                FROM SCRUTIN s
                WHERE 1 = 1";

        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $sql .= " AND (s.TITRE LIKE :q_titre OR s.DESCRIPTION LIKE :q_desc OR CAST(s.ANNEE AS CHAR) LIKE :q_annee)";
            $like = '%' . $search . '%';
            $params[':q_titre'] = $like;
            $params[':q_desc']  = $like;
            $params[':q_annee'] = $like;
        }

        $statut = strtolower(trim($statut));
        if ($statut !== '' && in_array($statut, self::ALLOWED_STATUTS, true)) {
            $sql .= " AND s.STATUT = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY s.ANNEE DESC, s.ID_SCRUTIN DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findById(int $idScrutin): ?array
    {
        if ($idScrutin <= 0) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM SCRUTIN WHERE ID_SCRUTIN = :id LIMIT 1");
        $stmt->execute([':id' => $idScrutin]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function getScrutinActif(): ?array
    {
        $row = $this->db->query("SELECT * FROM SCRUTIN WHERE EST_ACTIF = 1 LIMIT 1")->fetch();
        return $row !== false ? $row : null;
    }

    public function getScrutinPublic(): ?array
    {
        $sql = "SELECT * FROM SCRUTIN
                WHERE STATUT IN ('actif', 'publie', 'cloture', 'planifie')
                ORDER BY FIELD(STATUT, 'actif', 'publie', 'cloture', 'planifie'), ANNEE DESC
                LIMIT 1";
        $row = $this->db->query($sql)->fetch();

        return $row !== false ? $row : null;
    }

    public function create(array $data): int
    {
        return $this->createScrutin($data);
    }

    public function createScrutin(array $data): int
    {
        $payload = $this->normalizeAndValidateScrutin($data);

        $stmt = $this->db->prepare(
            "INSERT INTO SCRUTIN (TITRE, ANNEE, DESCRIPTION, DATE_DEBUT, DATE_FIN, STATUT)
             VALUES (:titre, :annee, :description, :debut, :fin, 'brouillon')"
        );
        $stmt->execute([
            ':titre'       => $payload['titre'],
            ':annee'       => $payload['annee'],
            ':description' => $payload['description'],
            ':debut'       => $payload['date_debut'],
            ':fin'         => $payload['date_fin'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $idScrutin, array $data): void
    {
        $this->updateScrutin($idScrutin, $data);
    }

    public function updateScrutin(int $idScrutin, array $data): void
    {
        if ($idScrutin <= 0) {
            throw new InvalidArgumentException('Identifiant de scrutin invalide.');
        }

        $existing = $this->findById($idScrutin);
        if ($existing === null) {
            throw new RuntimeException('Scrutin introuvable (ID #' . $idScrutin . ').');
        }

        $statutActuel = strtolower((string) ($existing['STATUT'] ?? ''));
        if (!in_array($statutActuel, self::EDITABLE_STATUTS, true)) {
            throw new RuntimeException(
                'Modification impossible : le scrutin est au statut "' . $statutActuel . '" (seuls "brouillon" et "planifie" sont modifiables).'
            );
        }

        $merged = array_merge($existing, $data);
        $payload = $this->normalizeAndValidateScrutin($merged);

        $stmt = $this->db->prepare(
            "UPDATE SCRUTIN
             SET TITRE       = :titre,
                 ANNEE       = :annee,
                 DESCRIPTION = :description,
                 DATE_DEBUT  = :debut,
                 DATE_FIN    = :fin
             WHERE ID_SCRUTIN = :id"
        );
        $stmt->execute([
            ':titre'       => $payload['titre'],
            ':annee'       => $payload['annee'],
            ':description' => $payload['description'],
            ':debut'       => $payload['date_debut'],
            ':fin'         => $payload['date_fin'],
            ':id'          => $idScrutin,
        ]);
    }

    public function changeStatut(int $idScrutin, string $nouveauStatut): void
    {
        if ($idScrutin <= 0) {
            throw new InvalidArgumentException('Identifiant de scrutin invalide.');
        }

        $existing = $this->findById($idScrutin);
        if ($existing === null) {
            throw new RuntimeException('Scrutin introuvable (ID #' . $idScrutin . ').');
        }

        $statut = strtolower(trim($nouveauStatut));
        if (!in_array($statut, self::ALLOWED_STATUTS, true)) {
            throw new InvalidArgumentException('Statut de scrutin invalide : ' . $nouveauStatut);
        }

        $stmt = $this->db->prepare("UPDATE SCRUTIN SET STATUT = :statut WHERE ID_SCRUTIN = :id");
        $stmt->execute([
            ':statut' => $statut,
            ':id'     => $idScrutin,
        ]);
    }

    public function delete(int $idScrutin): void
    {
        if ($idScrutin <= 0) {
            throw new InvalidArgumentException('Identifiant de scrutin invalide.');
        }

        $existing = $this->findById($idScrutin);
        if ($existing === null) {
            throw new RuntimeException('Scrutin introuvable (ID #' . $idScrutin . ').');
        }

        $statutActuel = strtolower((string) ($existing['STATUT'] ?? ''));
        if ($statutActuel === 'actif') {
            throw new RuntimeException('Impossible de supprimer un scrutin actuellement actif.');
        }

        try {
            $this->beginTransaction();
            $this->db->prepare("DELETE FROM SCRUTIN WHERE ID_SCRUTIN = :id")
                     ->execute([':id' => $idScrutin]);
            $this->commit();
        } catch (PDOException $e) {
            $this->rollBack();
            if ((string) $e->getCode() === '23000') {
                throw new RuntimeException(
                    'Impossible de supprimer ce scrutin : des émargements, codes de vote, bulletins ou catégories liées existent déjà.'
                );
            }
            throw $e;
        } catch (Throwable $e) {
            $this->rollBack();
            throw $e;
        }
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
        $libClean = trim($lib);
        if ($idScrutin <= 0 || $libClean === '') {
            throw new InvalidArgumentException('Le scrutin et le libellé de catégorie sont obligatoires.');
        }

        $descClean = ($description !== null && trim($description) !== '') ? trim($description) : null;

        $stmt = $this->db->prepare(
            "INSERT INTO CATEGORIE (ID_SCRUTIN, LIB, DESCRIPTION) VALUES (:scrutin, :lib, :desc)"
        );
        $stmt->execute([
            ':scrutin' => $idScrutin,
            ':lib'     => $libClean,
            ':desc'    => $descClean,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateCategorie(int $idCategorie, string $lib, ?string $description = null): void
    {
        $libClean = trim($lib);
        if ($idCategorie <= 0 || $libClean === '') {
            throw new InvalidArgumentException('Identifiant de catégorie et libellé obligatoires.');
        }

        $descClean = ($description !== null && trim($description) !== '') ? trim($description) : null;

        $stmt = $this->db->prepare(
            "UPDATE CATEGORIE SET LIB = :lib, DESCRIPTION = :desc WHERE ID_CATEGORIE = :id"
        );
        $stmt->execute([
            ':lib'  => $libClean,
            ':desc' => $descClean,
            ':id'   => $idCategorie,
        ]);
    }

    public function deleteCategorie(int $idCategorie): void
    {
        if ($idCategorie <= 0) {
            throw new InvalidArgumentException('Identifiant de catégorie invalide.');
        }

        $stmt = $this->db->prepare("DELETE FROM CATEGORIE WHERE ID_CATEGORIE = :id");
        $stmt->execute([':id' => $idCategorie]);
    }

    // --- Candidatures ---
    public function addCandidature(int $idCategorie, string $cni): int
    {
        $cniClean = strtoupper(trim($cni));
        if ($idCategorie <= 0 || $cniClean === '') {
            throw new InvalidArgumentException('La catégorie et la CNI du candidat sont obligatoires.');
        }

        // Le trigger trg_candidature_ai crée automatiquement la ligne dans RESULTAT à 0 voix
        $stmt = $this->db->prepare(
            "INSERT INTO CANDIDATURE (ID_CATEGORIE, CNI) VALUES (:cat, :cni)"
        );
        $stmt->execute([
            ':cat' => $idCategorie,
            ':cni' => $cniClean,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function removeCandidature(int $idCandidature): void
    {
        if ($idCandidature <= 0) {
            throw new InvalidArgumentException('Identifiant de candidature invalide.');
        }

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
        unset($cat);

        return $categories;
    }

    private function normalizeAndValidateScrutin(array $data): array
    {
        $titre       = trim((string) ($data['titre'] ?? $data['TITRE'] ?? ''));
        $annee       = (int) ($data['annee'] ?? $data['ANNEE'] ?? 0);
        $description = trim((string) ($data['description'] ?? $data['DESCRIPTION'] ?? ''));
        $dateDebut   = trim((string) ($data['date_debut'] ?? $data['DATE_DEBUT'] ?? ''));
        $dateFin     = trim((string) ($data['date_fin'] ?? $data['DATE_FIN'] ?? ''));

        if ($titre === '') {
            throw new InvalidArgumentException('Le titre du scrutin est obligatoire.');
        }
        if ($annee < 2000 || $annee > 2100) {
            throw new InvalidArgumentException('L’année du scrutin doit être comprise entre 2000 et 2100.');
        }
        if ($dateDebut === '' || $dateFin === '') {
            throw new InvalidArgumentException('Les dates de début et de fin sont obligatoires.');
        }
        if (strtotime($dateFin) !== false && strtotime($dateDebut) !== false && strtotime($dateFin) < strtotime($dateDebut)) {
            throw new InvalidArgumentException('La date de fin doit être postérieure ou égale à la date de début.');
        }

        return [
            'titre'       => $titre,
            'annee'       => $annee,
            'description' => $description !== '' ? $description : null,
            'date_debut'  => $dateDebut,
            'date_fin'    => $dateFin,
        ];
    }
}