<?php
// controllers/PublicVoteController.php

declare(strict_types=1);

class PublicVoteController extends Controller
{
    public function authenticate(): void
    {
        $scrutinModel = new ScrutinModel();
        $scrutin = $scrutinModel->getScrutinActif();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['_csrf'] ?? null)) {
                $this->setFlash('error', 'Jeton de sécurité invalide.');
                $this->redirect('electeur.auth');
            }

            if (!$scrutin) {
                $this->setFlash('error', 'Aucun scrutin actif n’est ouvert actuellement.');
                $this->redirect('electeur.auth');
            }

            $cni = strtoupper(trim((string) ($_POST['cni'] ?? '')));
            $contact = trim((string) ($_POST['contact'] ?? '')); // Téléphone ou email enregistré

            $individuModel = new IndividuModel();
            $individu = $individuModel->findByCni($cni);

            $contactValide = $individu && (
                (!empty($individu['TEL']) && strcasecmp((string) $individu['TEL'], $contact) === 0)
                || (!empty($individu['EMAIL']) && strcasecmp((string) $individu['EMAIL'], $contact) === 0)
            );

            if (!$individu || !$contactValide) {
                $this->setAuditContext([
                    'cni'        => $cni !== '' ? $cni : null,
                    'id_scrutin' => (int) $scrutin['ID_SCRUTIN'],
                    'detail'     => 'Échec d’authentification électeur avant vote',
                ]);
                $this->setFlash('error', 'Authentification échouée : CNI ou téléphone/email non concordant.');
                $this->redirect('electeur.auth');
            }

            $voteModel = new VoteModel();
            if ($voteModel->aDejaVote((int) $scrutin['ID_SCRUTIN'], $cni)) {
                unset($_SESSION['_electeur_auth']);
                $this->setAuditContext([
                    'cni'        => $cni,
                    'id_scrutin' => (int) $scrutin['ID_SCRUTIN'],
                    'detail'     => 'Tentative d’authentification d’un électeur ayant déjà voté',
                ]);
                $this->setFlash('error', 'Vous avez déjà voté pour ce scrutin.');
                $this->redirect('electeur.auth');
            }

            $_SESSION['_electeur_auth'] = [
                'cni'           => $individu['CNI'],
                'nom_prenoms'   => $individu['NOM_PRENOMS'],
                'type_individu' => $individu['TYPE_INDIVIDU'],
                'id_scrutin'    => (int) $scrutin['ID_SCRUTIN'],
                'auth_at'       => time(),
            ];

            $this->setAuditContext([
                'cni'        => $individu['CNI'],
                'id_scrutin' => (int) $scrutin['ID_SCRUTIN'],
                'detail'     => 'Électeur authentifié pour session de vote',
            ]);
            $this->redirect('public.voter.form');
        }

        $this->render('public/auth_electeur', [
            'scrutin' => $scrutin,
        ], 'public');
    }

    public function logoutElecteur(): void
    {
        unset($_SESSION['_electeur_auth']);
        $this->setFlash('success', 'Session électeur verrouillée. Une authentification sera requise pour voter.');
        $this->redirect('electeur.auth');
    }

    public function form(): void
    {
        $scrutinModel = new ScrutinModel();
        $scrutin = $scrutinModel->getScrutinActif();
        $electeur = $_SESSION['_electeur_auth'] ?? null;

        // Exige une authentification valide pour le scrutin actif
        if (!$scrutin || !$electeur || (int) ($electeur['id_scrutin'] ?? 0) !== (int) $scrutin['ID_SCRUTIN']) {
            unset($_SESSION['_electeur_auth']);
            $this->redirect('electeur.auth');
        }

        $categories = $scrutinModel->getCategoriesAvecCandidats((int) $scrutin['ID_SCRUTIN']);

        $this->render('public/voter', [
            'scrutin'    => $scrutin,
            'electeur'   => $electeur,
            'categories' => $categories,
        ], 'public');
    }

    public function submit(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf($_POST['_csrf'] ?? null)) {
            $this->setFlash('error', 'Session expirée ou jeton de sécurité invalide.');
            $this->redirect('electeur.auth');
        }

        $scrutinModel = new ScrutinModel();
        $scrutin = $scrutinModel->getScrutinActif();
        $electeur = $_SESSION['_electeur_auth'] ?? null;

        if (!$scrutin || !$electeur || (int) ($electeur['id_scrutin'] ?? 0) !== (int) $scrutin['ID_SCRUTIN']) {
            unset($_SESSION['_electeur_auth']);
            $this->setFlash('error', 'Veuillez vous authentifier avant de soumettre un vote.');
            $this->redirect('electeur.auth');
        }

        $idScrutin = (int) $scrutin['ID_SCRUTIN'];
        $cni = (string) $electeur['cni'];
        $codeVote = (string) ($_POST['code_vote'] ?? '');
        $choix = $_POST['choix'] ?? [];

        try {
            $voteModel = new VoteModel();
            $voteModel->soumettreVote($idScrutin, $cni, $codeVote, is_array($choix) ? $choix : []);

            // Réinitialisation obligatoire après vote : prochain vote = nouvelle authentification
            unset($_SESSION['_electeur_auth']);

            $this->setAuditContext([
                'cni'        => $cni,
                'id_scrutin' => $idScrutin,
                'detail'     => 'Vote anonyme enregistré et session électeur fermée',
            ]);
            $this->setFlash('success', 'Votre vote a bien été pris en compte. Votre session est clôturée par sécurité.');
            $this->redirect('public.ecran');
        } catch (Throwable $e) {
            $this->setAuditContext([
                'cni'        => $cni,
                'id_scrutin' => $idScrutin,
                'detail'     => 'Tentative de vote rejetée : ' . $e->getMessage(),
            ]);
            $this->setFlash('error', $e->getMessage());
            $this->redirect('public.voter.form');
        }
    }
}