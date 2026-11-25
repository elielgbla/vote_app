<?php
// Haut de views/layouts/admin.php

$contextFile = dirname(__DIR__) . '/_context.php';
if (is_file($contextFile)) {
    require_once $contextFile;
}



/** @var string $baseUrl */
/** @var ?array $currentAdmin */
/** @var ?string $flashSuccess */
/** @var ?string $flashError */
/** @var string $content */

$currentAction = $_GET['action'] ?? 'admin.dashboard';
$roleAdmin     = $currentAdmin['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SICTA — Back-Office Scrutin') ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/admin.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg sicta-navbar sticky-top shadow-sm" aria-label="Navigation back-office">
            <div class="container-fluid px-3 px-lg-4 px-xxl-5">
                <a class="navbar-brand d-flex align-items-center py-1" href="<?= $baseUrl ?>/index.php?action=admin.dashboard">
                    <img src="<?= $baseUrl ?>/assets/img/download.jpg" alt="Logo SICTA" height="40" class="rounded-1">
                </a>

                <button class="navbar-toggler border-secondary" type="button"
                        data-bs-toggle="collapse" data-bs-target="#adminNavbarMenu"
                        aria-controls="adminNavbarMenu" aria-expanded="false" aria-label="Ouvrir ou fermer le menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="adminNavbarMenu">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-lg-1">
                        <li class="nav-item">
                            <a class="nav-link <?= $currentAction === 'admin.dashboard' ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/index.php?action=admin.dashboard">
                                Tableau de bord
                            </a>
                        </li>

                        <?php if (in_array($roleAdmin, ['super_admin', 'rh'], true)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= (str_starts_with($currentAction, 'admin.scrutin') || str_starts_with($currentAction, 'admin.categorie') || str_starts_with($currentAction, 'admin.candidat')) ? 'active' : '' ?>"
                                   href="<?= $baseUrl ?>/index.php?action=admin.scrutins.index">
                                    Scrutins &amp; Candidats
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= str_starts_with($currentAction, 'admin.categorie') ? 'active' : '' ?>"
                                   href="<?= $baseUrl ?>/index.php?action=admin.categories.index">
                                    Catégories
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= str_starts_with($currentAction, 'admin.individus') ? 'active' : '' ?>"
                                   href="<?= $baseUrl ?>/index.php?action=admin.individus.index">
                                    Individus
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (in_array($roleAdmin, ['super_admin', 'caissier'], true)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= str_starts_with($currentAction, 'admin.codes') ? 'active' : '' ?>"
                                   href="<?= $baseUrl ?>/index.php?action=admin.codes.index">
                                    Codes de vote
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($roleAdmin === 'super_admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= str_starts_with($currentAction, 'admin.sites') ? 'active' : '' ?>"
                                   href="<?= $baseUrl ?>/index.php?action=admin.sites.index">
                                    Sites
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= str_starts_with($currentAction, 'admin.admins') ? 'active' : '' ?>"
                                   href="<?= $baseUrl ?>/index.php?action=admin.admins.index">
                                    Admins
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= str_starts_with($currentAction, 'admin.journal') ? 'active' : '' ?>"
                                   href="<?= $baseUrl ?>/index.php?action=admin.journal.index">
                                    Journal
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <div class="d-flex flex-wrap align-items-center gap-2 pt-2 pt-lg-0">
                        <?php if (!empty($currentAdmin)): ?>
                            <span class="badge bg-dark py-2 px-2">
                                <?= htmlspecialchars($currentAdmin['nom_prenoms']) ?> (<?= htmlspecialchars($roleAdmin) ?>)
                            </span>
                            <a class="btn btn-sm btn-outline-dark" href="<?= $baseUrl ?>/index.php?action=public.ecran">
                                Écran public
                            </a>
                            <a class="btn btn-sm btn-sicta-primary" href="<?= $baseUrl ?>/index.php?action=auth.logout">
                                Déconnexion
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

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

    <footer class="sicta-footer py-3">
        <div class="container-fluid px-3 px-lg-4 px-xxl-5 d-flex flex-wrap justify-content-between align-items-center gap-2 small">
            <span>SICTA — Back-Office de Gestion des Scrutins</span>
            <span>© <?= date('Y') ?> SICTA Côte d’Ivoire — Tous droits réservés</span>
        </div>
    </footer>

    <script src="<?= $baseUrl ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/vendor/chartjs/chart.umd.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/app.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/admin.js"></script>
</body>
</html>