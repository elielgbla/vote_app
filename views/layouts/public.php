<?php
// views/layouts/public.php
/** @var string $baseUrl */
/** @var ?array $currentAdmin */
/** @var ?string $flashSuccess */
/** @var ?string $flashError */
/** @var string $content */

declare(strict_types=1);

$currentAction  = $_GET['action'] ?? 'public.ecran';
$electeurAuth   = $_SESSION['_electeur_auth'] ?? null;
$isVoteSection  = in_array($currentAction, ['electeur.auth', 'public.voter.form', 'public.resultats'], true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SICTA — Plateforme de Scrutin & Vote') ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/public.css">
</head>
<body>
    <!-- Barre horizontale adaptative (mobile / tablette / ordi / grand écran / TV) -->
    <header>
        <nav class="navbar navbar-expand-lg sicta-navbar sticky-top shadow-sm" aria-label="Navigation principale">
            <div class="container-fluid px-3 px-lg-4 px-xxl-5">
                <a class="navbar-brand d-flex align-items-center py-1" href="<?= $baseUrl ?>/index.php?action=public.ecran">
                    <img src="<?= $baseUrl ?>/assets/img/download.jpg" alt="Logo SICTA" height="42" class="rounded-1">
                </a>

                <!-- Bouton menu burger / camembert sur mobile ou fenêtre réduite -->
                <button class="navbar-toggler border-secondary" type="button"
                        data-bs-toggle="collapse" data-bs-target="#publicNavbarMenu"
                        aria-controls="publicNavbarMenu" aria-expanded="false" aria-label="Ouvrir ou fermer le menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="publicNavbarMenu">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-lg-1">
                        <li class="nav-item">
                            <a class="nav-link <?= $currentAction === 'public.ecran' ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/index.php?action=public.ecran">
                                Accueil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= in_array($currentAction, ['electeur.auth', 'public.voter.form'], true) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/index.php?action=electeur.auth">
                                Vote en cours
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentAction === 'public.resultats' ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/index.php?action=public.resultats">
                                Résultats
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link"
                               href="<?= $baseUrl ?>/index.php?action=public.ecran#apropos-aide-contact">
                                À propos / Aide / Contact
                            </a>
                        </li>
                    </ul>

                    <div class="d-flex flex-wrap align-items-center gap-2 pt-2 pt-lg-0">
                        <?php if (!empty($electeurAuth)): ?>
                            <span class="badge bg-secondary py-2 px-2">
                                Électeur : <?= htmlspecialchars($electeurAuth['cni']) ?>
                            </span>
                            <a class="btn btn-sm btn-outline-dark" href="<?= $baseUrl ?>/index.php?action=electeur.logout">
                                Verrouiller session
                            </a>
                        <?php endif; ?>

                        <a class="btn btn-sm btn-sicta-primary" href="<?= $baseUrl ?>/index.php?action=electeur.auth">
                            Voter maintenant
                        </a>

                        <?php if (!empty($currentAdmin)): ?>
                            <a class="btn btn-sm btn-sicta-dark" href="<?= $baseUrl ?>/index.php?action=admin.dashboard">
                                Back-Office
                            </a>
                        <?php else: ?>
                            <a class="btn btn-sm btn-outline-dark" href="<?= $baseUrl ?>/index.php?action=auth.login">
                                Connexion Admin
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Contenu principal de la page -->
    <main class="app-main container-fluid px-3 px-lg-4 px-xxl-5 py-4">
        <?php if (!empty($flashSuccess)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <?= htmlspecialchars($flashSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($flashError)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <?= htmlspecialchars($flashError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <!-- Footer systématique sur toutes les pages publiques -->
    <footer class="sicta-footer py-4">
        <div class="container-fluid px-3 px-lg-4 px-xxl-5">
            <div class="row gy-3 align-items-center">
                <div class="col-md-6">
                    <div class="fw-bold text-white">SICTA — Système de Scrutin &amp; Vote Électronique</div>
                    <div class="small">
                        Authentification requise à chaque vote · Anti-doublon par CNI &amp; code à usage unique · Anonymat du bulletin garanti
                    </div>
                </div>
                <div class="col-md-6 text-md-end small">
                    <a href="<?= $baseUrl ?>/index.php?action=public.ecran">Accueil</a> ·
                    <a href="<?= $baseUrl ?>/index.php?action=electeur.auth">Vote en cours</a> ·
                    <a href="<?= $baseUrl ?>/index.php?action=public.resultats">Résultats</a> ·
                    <a href="<?= $baseUrl ?>/index.php?action=public.ecran#apropos-aide-contact">À propos / Aide / Contact</a>
                    <div class="mt-1">© <?= date('Y') ?> SICTA Côte d’Ivoire — Tous droits réservés</div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Dépendances locales -->
    <script src="<?= $baseUrl ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/vendor/chartjs/chart.umd.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/app.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/public-screen.js"></script>
</body>
</html>