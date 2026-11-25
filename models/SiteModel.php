<?php
// models/SiteModel.php

declare(strict_types=1);

class SiteModel extends \Model
{
    public function getAll(): array
    {
        $sql = "SELECT s.CODE_SITE, s.ID_ADMIN, s.LIB, s.VILLE,
                       a.NOM_PRENOMS AS RESPONSABLE_NOM,
                       (SELECT COUNT(*) FROM INDIVIDU i WHERE i.CODE_SITE = s.CODE_SITE) AS NB_INDIVIDUS
                FROM SITE s
                LEFT JOIN ADMIN a ON a.ID_ADMIN = s.ID_ADMIN
                ORDER BY s.LIB ASC, s.CODE_SITE ASC";

        return $this->db->query($sql)->fetchAll();
    }

    public function getAdmins(): array
    {
        $sql = "SELECT ID_ADMIN, LOGIN, NOM_PRENOMS, ROLE
                FROM ADMIN
                ORDER BY NOM_PRENOMS ASC, LOGIN ASC";

        return $this->db->query($sql)->fetchAll();
    }

    public function findByCode(string $codeSite): ?array
    {
        $code = strtoupper(trim($codeSite));
        if ($code === '') {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM SITE WHERE CODE_SITE = :code LIMIT 1");
        $stmt->execute([':code' => $code]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function create(array $data): string
    {
        $payload = $this->normalizeAndValidate($data);

        if ($this->findByCode($payload['code_site']) !== null) {
            throw new RuntimeException('Un site avec le code ' . $payload['code_site'] . ' existe déjà.');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO SITE (CODE_SITE, ID_ADMIN, LIB, VILLE)
             VALUES (:code, :admin, :lib, :ville)"
        );
        $stmt->execute([
            ':code'  => $payload['code_site'],
            ':admin' => $payload['id_admin'],
            ':lib'   => $payload['lib'],
            ':ville' => $payload['ville'],
        ]);

        return $payload['code_site'];
    }

    public function update(string $codeSite, array $data): void
    {
        $code = strtoupper(trim($codeSite));
        $existing = $this->findByCode($code);

        if ($existing === null) {
            throw new RuntimeException('Site introuvable pour le code ' . $code . '.');
        }

        $merged = array_merge($existing, $data);
        $merged['code_site'] = $code;

        $payload = $this->normalizeAndValidate($merged);

        $stmt = $this->db->prepare(
            "UPDATE SITE
             SET ID_ADMIN = :admin,
                 LIB      = :lib,
                 VILLE    = :ville
             WHERE CODE_SITE = :code"
        );
        $stmt->execute([
            ':admin' => $payload['id_admin'],
            ':lib'   => $payload['lib'],
            ':ville' => $payload['ville'],
            ':code'  => $code,
        ]);
    }

    public function delete(string $codeSite): void
    {
        $code = strtoupper(trim($codeSite));
        if ($code === '') {
            throw new InvalidArgumentException('Le code du site est obligatoire.');
        }

        if ($this->findByCode($code) === null) {
            throw new RuntimeException('Site introuvable pour le code ' . $code . '.');
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM SITE WHERE CODE_SITE = :code");
            $stmt->execute([':code' => $code]);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new RuntimeException(
                    'Impossible de supprimer ce site : des individus ou lots de vote y sont rattachés.'
                );
            }
            throw $e;
        }
    }

    private function normalizeAndValidate(array $data): array
    {
        $code    = strtoupper(trim((string) ($data['code_site'] ?? $data['CODE_SITE'] ?? '')));
        $lib     = trim((string) ($data['lib'] ?? $data['LIB'] ?? ''));
        $ville   = trim((string) ($data['ville'] ?? $data['VILLE'] ?? ''));
        $idAdmin = (int) ($data['id_admin'] ?? $data['ID_ADMIN'] ?? 0);

        if ($code === '') {
            throw new InvalidArgumentException('Le code du site est obligatoire.');
        }
        if (mb_strlen($code, 'UTF-8') > 5) {
            throw new InvalidArgumentException('Le code du site ne doit pas dépasser 5 caractères.');
        }
        if ($lib === '') {
            throw new InvalidArgumentException('Le libellé du site est obligatoire.');
        }
        if (mb_strlen($lib, 'UTF-8') > 60) {
            throw new InvalidArgumentException('Le libellé du site ne doit pas dépasser 60 caractères.');
        }
        if (mb_strlen($ville, 'UTF-8') > 40) {
            throw new InvalidArgumentException('La ville ne doit pas dépasser 40 caractères.');
        }
        if ($idAdmin <= 0) {
            throw new InvalidArgumentException('Le responsable (administrateur) du site est obligatoire.');
        }

        $stmtAdmin = $this->db->prepare("SELECT ID_ADMIN FROM ADMIN WHERE ID_ADMIN = :id LIMIT 1");
        $stmtAdmin->execute([':id' => $idAdmin]);
        if ($stmtAdmin->fetchColumn() === false) {
            throw new InvalidArgumentException('Administrateur responsable introuvable.');
        }

        return [
            'code_site' => $code,
            'id_admin'  => $idAdmin,
            'lib'       => $lib,
            'ville'     => $ville !== '' ? $ville : null,
        ];
    }
}