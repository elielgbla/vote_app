<?php
// models/ResultatModel.php

declare(strict_types=1);

class ResultatModel
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \Database::getConnection();
    }
    
    public function getKpis(int $idScrutin): array
    {
        $stmtScrutin = $this->pdo->prepare("SELECT STATUT FROM SCRUTIN WHERE ID_SCRUTIN = :id LIMIT 1");
        $stmtScrutin->execute([':id' => $idScrutin]);
        $statut = (string) ($stmtScrutin->fetchColumn() ?: '');

        $stmtEmarg = $this->pdo->prepare("SELECT COUNT(*) FROM EMARGEMENT WHERE ID_SCRUTIN = :id");
        $stmtEmarg->execute([':id' => $idScrutin]);
        $emargements = (int) $stmtEmarg->fetchColumn();

        $totalElecteurs = (int) $this->pdo->query("SELECT COUNT(*) FROM INDIVIDU")->fetchColumn();

        $stmtBulletins = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM VOTE v
             INNER JOIN CATEGORIE c ON c.ID_CATEGORIE = v.ID_CATEGORIE
             WHERE c.ID_SCRUTIN = :id"
        );
        $stmtBulletins->execute([':id' => $idScrutin]);
        $bulletins = (int) $stmtBulletins->fetchColumn();

        $stmtCodes = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS total_codes,
                SUM(CASE WHEN STATUT = 'genere' THEN 1 ELSE 0 END) AS codes_generes,
                SUM(CASE WHEN STATUT = 'distribue' THEN 1 ELSE 0 END) AS codes_distribues,
                SUM(CASE WHEN STATUT = 'utilise' THEN 1 ELSE 0 END) AS codes_utilises,
                SUM(CASE WHEN STATUT = 'annule' THEN 1 ELSE 0 END) AS codes_annules
             FROM CODE_VOTE
             WHERE ID_SCRUTIN = :id"
        );
        $stmtCodes->execute([':id' => $idScrutin]);
        $codesRow = $stmtCodes->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalCodes = (int) ($codesRow['total_codes'] ?? 0);
        $codesDistribues = (int) ($codesRow['codes_distribues'] ?? 0);
        $codesUtilises = (int) ($codesRow['codes_utilises'] ?? 0);

        $tauxParticipation = $totalElecteurs > 0
            ? round(($emargements / $totalElecteurs) * 100, 1)
            : 0.0;

        return [
            'statut'             => $statut,
            'total_electeurs'    => $totalElecteurs,
            'emargements'        => $emargements,
            'bulletins'          => $bulletins,
            'taux_participation' => $tauxParticipation,
            'total_codes'        => $totalCodes,
            'codes_generes'      => (int) ($codesRow['codes_generes'] ?? 0),
            'codes_distribues'   => $codesDistribues,
            'codes_utilises'     => $codesUtilises,
            'codes_annules'      => (int) ($codesRow['codes_annules'] ?? 0),
        ];
    }

    // Conserve ou complète ici getByScrutin(int $idScrutin) si nécessaire
    public function getByScrutin(int $idScrutin): array
    {
        $sql = "SELECT
                    c.ID_CATEGORIE,
                    c.LIB AS CATEGORIE,
                    cand.ID_CANDIDATURE,
                    ind.NOM_PRENOMS AS CANDIDAT,
                    r.NB_VOIX,
                    r.POURCENTAGE,
                    CASE WHEN r.EST_FIGE = 1 THEN r.RANG ELSE NULL END AS RANG_FIGE
                FROM CATEGORIE c
                LEFT JOIN CANDIDATURE cand ON cand.ID_CATEGORIE = c.ID_CATEGORIE
                LEFT JOIN INDIVIDU ind ON ind.CNI = cand.CNI
                LEFT JOIN RESULTAT r ON r.ID_CANDIDATURE = cand.ID_CANDIDATURE
                WHERE c.ID_SCRUTIN = :id_scrutin
                ORDER BY c.ID_CATEGORIE ASC, r.NB_VOIX DESC, ind.NOM_PRENOMS ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_scrutin' => $idScrutin]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $groupesMap = [];
        foreach ($rows as $row) {
            $idCat = (int) $row['ID_CATEGORIE'];
            if (!isset($groupesMap[$idCat])) {
                $groupesMap[$idCat] = [
                    'categorie' => (string) $row['CATEGORIE'],
                    'candidats' => [],
                    'labels'    => [],
                    'voix'      => [],
                ];
            }

            if (!empty($row['ID_CANDIDATURE'])) {
                $voix = (int) ($row['NB_VOIX'] ?? 0);
                $candidatNom = (string) ($row['CANDIDAT'] ?? '');
                $groupesMap[$idCat]['candidats'][] = [
                    'ID_CANDIDATURE' => (int) $row['ID_CANDIDATURE'],
                    'CANDIDAT'       => $candidatNom,
                    'NB_VOIX'        => $voix,
                    'POURCENTAGE'    => $row['POURCENTAGE'] ?? 0,
                    'RANG_FIGE'      => $row['RANG_FIGE'] ?? null,
                    'RANG_CALCULE'   => count($groupesMap[$idCat]['candidats']) + 1,
                ];
                $groupesMap[$idCat]['labels'][] = $candidatNom;
                $groupesMap[$idCat]['voix'][] = $voix;
            }
        }

        return array_values($groupesMap);
    }
}