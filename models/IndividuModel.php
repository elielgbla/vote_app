<?php
// models/IndividuModel.php

declare(strict_types=1);

class IndividuModel
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? \Database::getConnection();
    }

    public function getAll(string $search = '', string $type = ''): array
    {
        $sql = "SELECT
                    i.CNI,
                    i.NOM_PRENOMS,
                    i.TYPE_INDIVIDU,
                    i.TEL,
                    i.EMAIL,
                    i.SERVICE,
                    i.ADRESSE,
                    i.CODE_SITE,
                    i.PHOTO,
                    i.DATE_CREATION,
                    s.LIB AS SITE_LIB,
                    s.VILLE AS SITE_VILLE
                FROM INDIVIDU i
                LEFT JOIN SITE s ON s.CODE_SITE = i.CODE_SITE
                WHERE 1 = 1";

        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $sql .= " AND (
                        i.CNI LIKE :q_cni
                        OR i.NOM_PRENOMS LIKE :q_nom
                        OR i.TEL LIKE :q_tel
                        OR i.EMAIL LIKE :q_email
                        OR i.SERVICE LIKE :q_service
                    )";
            $like = '%' . $search . '%';
            $params[':q_cni']     = $like;
            $params[':q_nom']     = $like;
            $params[':q_tel']     = $like;
            $params[':q_email']   = $like;
            $params[':q_service'] = $like;
        }

        $type = trim($type);
        if ($type !== '') {
            $sql .= " AND i.TYPE_INDIVIDU = :type_individu";
            $params[':type_individu'] = $type;
        }

        $sql .= " ORDER BY i.NOM_PRENOMS ASC, i.CNI ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByCni(string $cni): ?array
    {
        $cni = strtoupper(trim($cni));
        if ($cni === '') {
            return null;
        }

        $stmt = $this->pdo->prepare(
            "SELECT
                CNI,
                NOM_PRENOMS,
                TYPE_INDIVIDU,
                TEL,
                EMAIL,
                SERVICE,
                ADRESSE,
                CODE_SITE,
                PHOTO,
                DATE_CREATION
             FROM INDIVIDU
             WHERE CNI = :cni
             LIMIT 1"
        );
        $stmt->execute([':cni' => $cni]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function create(array $data, ?string $photoFile = null): string
    {
        $payload = $this->normalizeAndValidate($data, $photoFile);

        if ($this->findByCni($payload['cni']) !== null) {
            throw new RuntimeException('Un individu avec la CNI ' . $payload['cni'] . ' existe déjà.');
        }

        $sql = "INSERT INTO INDIVIDU (
                    CNI,
                    NOM_PRENOMS,
                    TYPE_INDIVIDU,
                    TEL,
                    EMAIL,
                    SERVICE,
                    ADRESSE,
                    CODE_SITE,
                    PHOTO
                ) VALUES (
                    :cni,
                    :nom_prenoms,
                    :type_individu,
                    :tel,
                    :email,
                    :service,
                    :adresse,
                    :code_site,
                    :photo
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':cni'           => $payload['cni'],
            ':nom_prenoms'   => $payload['nom_prenoms'],
            ':type_individu' => $payload['type_individu'],
            ':tel'           => $payload['tel'],
            ':email'         => $payload['email'],
            ':service'       => $payload['service'],
            ':adresse'       => $payload['adresse'],
            ':code_site'     => $payload['code_site'],
            ':photo'         => $payload['photo'],
        ]);

        return $payload['cni'];
    }

    public function update(string $cni, array $data, ?string $photoFile = null): void
    {
        $cni = strtoupper(trim($cni));
        $existing = $this->findByCni($cni);

        if ($existing === null) {
            throw new RuntimeException('Individu introuvable pour la CNI ' . $cni . '.');
        }

        $merged = array_merge($existing, $data);
        $merged['cni'] = $cni;

        $finalPhoto = $photoFile !== null ? $photoFile : ($existing['PHOTO'] ?? null);
        $payload = $this->normalizeAndValidate($merged, $finalPhoto);

        $sql = "UPDATE INDIVIDU
                SET NOM_PRENOMS   = :nom_prenoms,
                    TYPE_INDIVIDU = :type_individu,
                    TEL           = :tel,
                    EMAIL         = :email,
                    SERVICE       = :service,
                    ADRESSE       = :adresse,
                    CODE_SITE     = :code_site,
                    PHOTO         = :photo
                WHERE CNI = :cni";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nom_prenoms'   => $payload['nom_prenoms'],
            ':type_individu' => $payload['type_individu'],
            ':tel'           => $payload['tel'],
            ':email'         => $payload['email'],
            ':service'       => $payload['service'],
            ':adresse'       => $payload['adresse'],
            ':code_site'     => $payload['code_site'],
            ':photo'         => $payload['photo'],
            ':cni'           => $cni,
        ]);
    }

    public function createOrUpdate(array $data, ?string $photoFile = null): string
    {
        $cni = strtoupper(trim((string) ($data['cni'] ?? $data['CNI'] ?? '')));
        if ($cni !== '' && $this->findByCni($cni) !== null) {
            $this->update($cni, $data, $photoFile);
            return $cni;
        }

        return $this->create($data, $photoFile);
    }

    public function delete(string $cni): void
    {
        $cni = strtoupper(trim($cni));
        if ($cni === '') {
            throw new InvalidArgumentException('La CNI est obligatoire pour la suppression.');
        }

        if ($this->findByCni($cni) === null) {
            throw new RuntimeException('Individu introuvable pour la CNI ' . $cni . '.');
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM INDIVIDU WHERE CNI = :cni");
            $stmt->execute([':cni' => $cni]);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new RuntimeException(
                    'Impossible de supprimer cet individu : il est déjà lié à une candidature, un émargement ou un historique.'
                );
            }
            throw $e;
        }
    }

    private function normalizeAndValidate(array $data, ?string $photoFile = null): array
    {
        $cni        = strtoupper(trim((string) ($data['cni'] ?? $data['CNI'] ?? '')));
        $nomPrenoms = trim((string) ($data['nom_prenoms'] ?? $data['NOM_PRENOMS'] ?? ''));
        $type       = strtolower(trim((string) ($data['type_individu'] ?? $data['TYPE_INDIVIDU'] ?? 'employe')));
        $tel        = trim((string) ($data['tel'] ?? $data['TEL'] ?? ''));
        $email      = trim((string) ($data['email'] ?? $data['EMAIL'] ?? ''));
        $service    = trim((string) ($data['service'] ?? $data['SERVICE'] ?? ''));
        $adresse    = trim((string) ($data['adresse'] ?? $data['ADRESSE'] ?? ''));
        $codeSite   = strtoupper(trim((string) ($data['code_site'] ?? $data['CODE_SITE'] ?? '')));

        if ($cni === '') {
            throw new InvalidArgumentException('Le numéro de CNI est obligatoire.');
        }
        if ($nomPrenoms === '') {
            throw new InvalidArgumentException('Les nom et prénoms sont obligatoires.');
        }
        if (!in_array($type, ['employe', 'client'], true)) {
            throw new InvalidArgumentException('Type d’individu invalide (employe ou client attendu).');
        }
        if ($tel === '' && $email === '') {
            throw new InvalidArgumentException('Un numéro de téléphone ou une adresse email est obligatoire.');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('L’adresse email saisie est invalide.');
        }

        $normalizedSite = null;
        if ($codeSite !== '') {
            $stmtSite = $this->pdo->prepare("SELECT CODE_SITE FROM SITE WHERE CODE_SITE = :code LIMIT 1");
            $stmtSite->execute([':code' => $codeSite]);
            if ($stmtSite->fetchColumn() === false) {
                throw new InvalidArgumentException('Le site sélectionné (' . $codeSite . ') est introuvable.');
            }
            $normalizedSite = $codeSite;
        }

        $cleanPhoto = ($photoFile !== null && trim($photoFile) !== '')
            ? basename(trim($photoFile))
            : null;

        return [
            'cni'           => $cni,
            'nom_prenoms'   => $nomPrenoms,
            'type_individu' => $type,
            'tel'           => $tel,
            'email'         => $email !== '' ? $email : null,
            'service'       => $service !== '' ? $service : null,
            'adresse'       => $adresse !== '' ? $adresse : null,
            'code_site'     => $normalizedSite,
            'photo'         => $cleanPhoto,
        ];
    }
}