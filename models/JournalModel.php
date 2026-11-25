<?php
// models/JournalModel.php

declare(strict_types=1);

class JournalModel extends Model
{
    public function getFiltered(array $filters = [], int $limit = 150): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['action'])) {
            $conditions[] = "j.ACTION LIKE :action";
            $params[':action'] = '%' . trim($filters['action']) . '%';
        }
        if (!empty($filters['id_scrutin'])) {
            $conditions[] = "j.ID_SCRUTIN = :scrutin";
            $params[':scrutin'] = (int) $filters['id_scrutin'];
        }
        if (!empty($filters['cni'])) {
            $conditions[] = "j.CNI = :cni";
            $params[':cni'] = strtoupper(trim($filters['cni']));
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT j.*, a.NOM_PRENOMS AS ADMIN_NOM, s.TITRE AS SCRUTIN_TITRE
                FROM JOURNAL j
                LEFT JOIN ADMIN a ON a.ID_ADMIN = j.ID_ADMIN
                LEFT JOIN SCRUTIN s ON s.ID_SCRUTIN = j.ID_SCRUTIN
                {$where}
                ORDER BY j.DATE_ACTION DESC, j.ID_JOURNAL DESC
                LIMIT :lim";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}