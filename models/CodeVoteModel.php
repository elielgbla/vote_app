<?php
// models/CodeVoteModel.php

declare(strict_types=1);

class CodeVoteModel extends Model
{
    public function getLotsByScrutin(int $idScrutin): array
    {
        $stmt = $this->db->prepare(
            "SELECT l.*, a.NOM_PRENOMS AS ADMIN_NOM, s.LIB AS SITE_LIB,
                    (SELECT COUNT(*) FROM CODE_VOTE cv WHERE cv.ID_LOT = l.ID_LOT AND cv.STATUT = 'genere') AS NB_GENERES,
                    (SELECT COUNT(*) FROM CODE_VOTE cv WHERE cv.ID_LOT = l.ID_LOT AND cv.STATUT = 'distribue') AS NB_DISTRIBUES,
                    (SELECT COUNT(*) FROM CODE_VOTE cv WHERE cv.ID_LOT = l.ID_LOT AND cv.STATUT = 'utilise') AS NB_UTILISES,
                    (SELECT COUNT(*) FROM CODE_VOTE cv WHERE cv.ID_LOT = l.ID_LOT AND cv.STATUT = 'annule') AS NB_ANNULES
             FROM LOT_CODES l
             JOIN ADMIN a ON a.ID_ADMIN = l.ID_ADMIN
             LEFT JOIN SITE s ON s.CODE_SITE = l.CODE_SITE
             WHERE l.ID_SCRUTIN = :scrutin
             ORDER BY l.DATE_GENERATION DESC, l.ID_LOT DESC"
        );
        $stmt->execute([':scrutin' => $idScrutin]);
        return $stmt->fetchAll();
    }

    public function getCodesByLot(int $idLot): array
    {
        $stmt = $this->db->prepare(
            "SELECT ID_CODE, ID_SCRUTIN, ID_LOT, LEFT(HASH_CODE, 12) AS HASH_APERCU,
                    TYPE_PORTEUR, STATUT, DATE_DISTRIBUTION, DATE_UTILISATION
             FROM CODE_VOTE
             WHERE ID_LOT = :lot
             ORDER BY ID_CODE ASC"
        );
        $stmt->execute([':lot' => $idLot]);
        return $stmt->fetchAll();
    }

    public function distribuerLotComplet(int $idLot): int
    {
        $stmt = $this->db->prepare(
            "UPDATE CODE_VOTE
             SET STATUT = 'distribue', DATE_DISTRIBUTION = NOW()
             WHERE ID_LOT = :lot AND STATUT = 'genere'"
        );
        $stmt->execute([':lot' => $idLot]);
        return $stmt->rowCount();
    }

    public function annulerCode(int $idCode): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE CODE_VOTE
             SET STATUT = 'annule'
             WHERE ID_CODE = :id AND STATUT IN ('genere', 'distribue')"
        );
        $stmt->execute([':id' => $idCode]);
        return $stmt->rowCount() > 0;
    }
}