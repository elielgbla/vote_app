<?php
// controllers/CodeVoteController.php

declare(strict_types=1);

class CodeVoteController extends Controller
{
    public function index(): void
    {
        $scrutinModel = new ScrutinModel();
        $codeModel    = new CodeVoteModel();
        $siteModel    = new SiteModel();

        $scrutins = $scrutinModel->getAll();
        $selectedId = isset($_GET['scrutin']) ? (int) $_GET['scrutin'] : (int) ($scrutins[0]['ID_SCRUTIN'] ?? 0);

        $lots = $selectedId > 0 ? $codeModel->getLotsByScrutin($selectedId) : [];
        $codesEnClairGenere = $_SESSION['_last_generated_codes'] ?? null;
        unset($_SESSION['_last_generated_codes']);

        $this->render('admin/codes/index', [
            'scrutins'           => $scrutins,
            'selectedScrutinId'  => $selectedId,
            'sites'              => $siteModel->getAll(),
            'lots'               => $lots,
            'codesEnClairGenere' => $codesEnClairGenere,
        ], 'admin');
    }

    public function generateLot(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->redirect('admin.codes.index');
        }

        $idScrutin   = (int) ($_POST['id_scrutin'] ?? 0);
        $typePorteur = (string) ($_POST['type_porteur'] ?? 'employe');
        $quantite    = (int) ($_POST['quantite'] ?? 0);
        $codeSite    = ($_POST['code_site'] ?? '') !== '' ? (string) $_POST['code_site'] : null;
        $admin       = $this->auth->user();

        try {
            if ($quantite < 1 || $quantite > 1000) {
                throw new RuntimeException('La quantité doit être comprise entre 1 et 1000.');
            }
            $pdo = Database::getConnection();
            $manager = new CodeVoteManager($pdo);
            $res = $manager->generateBatch($idScrutin, (int) $admin['id'], $typePorteur, $quantite, $codeSite);

            $_SESSION['_last_generated_codes'] = $res;
            $this->setAuditContext([
                'id_scrutin' => $idScrutin,
                'detail'     => 'Génération du lot #' . $res['id_lot'] . ' (' . $quantite . ' codes ' . $typePorteur . ')',
            ]);
            $this->setFlash('success', $quantite . ' codes générés pour le lot #' . $res['id_lot'] . '. Imprimez-les maintenant (ils ne seront plus affichés en clair).');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('admin.codes.index', ['scrutin' => $idScrutin]);
    }

    public function markDistributed(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->redirect('admin.codes.index');
        }

        $idScrutin = (int) ($_POST['id_scrutin'] ?? 0);
        $idLot     = (int) ($_POST['id_lot'] ?? 0);

        try {
            $codeModel = new CodeVoteModel();
            $nb = $codeModel->distribuerLotComplet($idLot);
            $this->setAuditContext([
                'id_scrutin' => $idScrutin,
                'table'      => 'CODE_VOTE',
                'detail'     => 'Distribution de ' . $nb . ' code(s) sur le lot #' . $idLot,
            ]);
            $this->setFlash('success', $nb . ' code(s) marqué(s) comme distribué(s).');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('admin.codes.index', ['scrutin' => $idScrutin]);
    }
}