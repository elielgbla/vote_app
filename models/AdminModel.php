<?php
// models/AdminModel.php

declare(strict_types=1);

class AdminModel extends Model
{
    public function getAll(): array
    {
        return $this->db->query(
            "SELECT ID_ADMIN, LOGIN, NOM_PRENOMS, TEL, ROLE, ACTIF, DATE_CREATION
             FROM ADMIN
             ORDER BY NOM_PRENOMS ASC"
        )->fetchAll();
    }

    public function findById(int $idAdmin): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ID_ADMIN, LOGIN, NOM_PRENOMS, TEL, ROLE, ACTIF, DATE_CREATION
             FROM ADMIN WHERE ID_ADMIN = :id LIMIT 1"
        );
        $stmt->execute([':id' => $idAdmin]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ADMIN (LOGIN, NOM_PRENOMS, MOT_DE_PASSE, TEL, ROLE, ACTIF)
             VALUES (:login, :nom, :mdp, :tel, :role, :actif)"
        );
        $stmt->execute([
            ':login' => trim($data['login']),
            ':nom'   => trim($data['nom_prenoms']),
            ':mdp'   => password_hash($data['mot_de_passe'], PASSWORD_DEFAULT),
            ':tel'   => ($data['tel'] ?? '') !== '' ? trim($data['tel']) : null,
            ':role'  => $data['role'],
            ':actif' => isset($data['actif']) ? (int) $data['actif'] : 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $idAdmin, array $data): void
    {
        $sql = "UPDATE ADMIN
                SET LOGIN = :login, NOM_PRENOMS = :nom, TEL = :tel, ROLE = :role, ACTIF = :actif"
             . (!empty($data['mot_de_passe']) ? ", MOT_DE_PASSE = :mdp" : "")
             . " WHERE ID_ADMIN = :id";

        $params = [
            ':login' => trim($data['login']),
            ':nom'   => trim($data['nom_prenoms']),
            ':tel'   => ($data['tel'] ?? '') !== '' ? trim($data['tel']) : null,
            ':role'  => $data['role'],
            ':actif' => (int) ($data['actif'] ?? 1),
            ':id'    => $idAdmin,
        ];
        if (!empty($data['mot_de_passe'])) {
            $params[':mdp'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        }

        $this->db->prepare($sql)->execute($params);
    }

    public function toggleActif(int $idAdmin): void
    {
        $stmt = $this->db->prepare("UPDATE ADMIN SET ACTIF = 1 - ACTIF WHERE ID_ADMIN = :id");
        $stmt->execute([':id' => $idAdmin]);
    }
}