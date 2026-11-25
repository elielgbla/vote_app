<?php
// models/VoteModel.php

declare(strict_types=1);

class VoteModel extends Model
{
    public function aDejaVote(int $idScrutin, string $cni): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM EMARGEMENT WHERE ID_SCRUTIN = :scrutin AND CNI = :cni LIMIT 1"
        );
        $stmt->execute([':scrutin' => $idScrutin, ':cni' => strtoupper(trim($cni))]);
        return (bool) $stmt->fetchColumn();
    }

    public function getEmargementsByScrutin(int $idScrutin): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.ID_SCRUTIN, e.CNI, e.DATE_VOTE, i.NOM_PRENOMS, i.TYPE_INDIVIDU, i.SERVICE, s.LIB AS SITE_LIB
             FROM EMARGEMENT e
             JOIN INDIVIDU i ON i.CNI = e.CNI
             LEFT JOIN SITE s ON s.CODE_SITE = i.CODE_SITE
             WHERE e.ID_SCRUTIN = :scrutin
             ORDER BY e.DATE_VOTE DESC, i.NOM_PRENOMS ASC"
        );
        $stmt->execute([':scrutin' => $idScrutin]);
        return $stmt->fetchAll();
    }

    public function soumettreVote(int $idScrutin, string $cni, string $codeClair, array $choixParCategorie): array
    {
        $cni = strtoupper(trim($cni));
        $codeClair = strtoupper(trim($codeClair));

        if ($cni === '' || $codeClair === '' || empty($choixParCategorie)) {
            throw new RuntimeException('Veuillez renseigner votre CNI, votre code de vote et vos choix.');
        }

        $this->beginTransaction();

        try {
            $stmtScrutin = $this->db->prepare(
                "SELECT ID_SCRUTIN, STATUT, DATE_DEBUT, DATE_FIN
                 FROM SCRUTIN WHERE ID_SCRUTIN = :id FOR UPDATE"
            );
            $stmtScrutin->execute([':id' => $idScrutin]);
            $scrutin = $stmtScrutin->fetch();

            if (!$scrutin || $scrutin['STATUT'] !== 'actif') {
                throw new RuntimeException('Aucun scrutin actif n’est ouvert actuellement.');
            }

            $now = new DateTimeImmutable();
            if ($now < new DateTimeImmutable($scrutin['DATE_DEBUT']) || $now > new DateTimeImmutable($scrutin['DATE_FIN'])) {
                throw new RuntimeException('La période de vote pour ce scrutin est fermée.');
            }

            $stmtIndividu = $this->db->prepare(
                "SELECT CNI, NOM_PRENOMS, TYPE_INDIVIDU FROM INDIVIDU WHERE CNI = :cni LIMIT 1"
            );
            $stmtIndividu->execute([':cni' => $cni]);
            $individu = $stmtIndividu->fetch();

            if (!$individu) {
                throw new RuntimeException('Numéro CNI non reconnu dans le fichier électoral.');
            }

            if ($this->aDejaVote($idScrutin, $cni)) {
                throw new RuntimeException('Vous avez déjà voté pour ce scrutin (double vote bloqué).');
            }

            $stmtCats = $this->db->prepare(
                "SELECT ID_CATEGORIE FROM CATEGORIE WHERE ID_SCRUTIN = :scrutin"
            );
            $stmtCats->execute([':scrutin' => $idScrutin]);
            $categoriesAttendues = array_map('intval', $stmtCats->fetchAll(PDO::FETCH_COLUMN));

            if (empty($categoriesAttendues)) {
                throw new RuntimeException('Ce scrutin ne comporte aucune catégorie.');
            }

            foreach ($categoriesAttendues as $idCat) {
                if (empty($choixParCategorie[$idCat])) {
                    throw new RuntimeException('Veuillez sélectionner un candidat dans chaque catégorie.');
                }
            }

            $codeManager = new CodeVoteManager($this->db);
            $idCode = $codeManager->consumeForVote($codeClair, $idScrutin, $individu['TYPE_INDIVIDU']);

            if ($idCode === null) {
                throw new RuntimeException('Code de vote invalide, non distribué, déjà utilisé ou incompatible avec votre profil.');
            }

            // Enregistrement de l'émargement (sans choix)
            $this->db->prepare(
                "INSERT INTO EMARGEMENT (ID_SCRUTIN, CNI, DATE_VOTE) VALUES (:scrutin, :cni, CURDATE())"
            )->execute([':scrutin' => $idScrutin, ':cni' => $cni]);

            // Enregistrement des bulletins (sans CNI) -> déclenche trg_vote_ai sur RESULTAT
            $stmtVote = $this->db->prepare(
                "INSERT INTO VOTE (ID_CATEGORIE, ID_CANDIDATURE, DATE_VOTE)
                 VALUES (:id_cat, :id_cand, CURDATE())"
            );
            foreach ($categoriesAttendues as $idCat) {
                $stmtVote->execute([
                    ':id_cat'  => $idCat,
                    ':id_cand' => (int) $choixParCategorie[$idCat],
                ]);
            }

            $this->commit();

            return [
                'cni'        => $cni,
                'id_scrutin' => $idScrutin,
                'nb_votes'   => count($categoriesAttendues),
            ];
        } catch (Throwable $e) {
            $this->rollBack();
            if ($e instanceof PDOException && $e->getCode() === '23000') {
                throw new RuntimeException('Double vote bloqué ou candidature invalide.');
            }
            throw $e;
        }
    }
}