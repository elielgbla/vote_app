<?php
// views/auth/login.php

declare(strict_types=1);

$contextFile = dirname(__DIR__) . '/_context.php';
if (is_file($contextFile)) {
    require_once $contextFile;
}

/** @var string $baseUrl */
/** @var string $csrfToken */

$baseUrl = $baseUrl ?? '';
$csrfToken = $csrfToken ?? '';

$pageTitle = 'SICTA — Connexion Back-Office';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card sicta-card p-4">
            <div class="text-center mb-3">
                <img src="<?= $baseUrl ?>/assets/img/download.jpg" alt="SICTA" height="54" class="mb-2">
                <h1 class="h4 fw-bold" style="color: var(--sicta-dark);">Administration du Scrutin</h1>
                <p class="text-muted small mb-0">Accès réservé (super_admin, rh, caissier)</p>
            </div>

            <form method="POST" action="<?= $baseUrl ?>/index.php?action=auth.login">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="mb-3">
                    <label for="login" class="form-label fw-bold">Identifiant (LOGIN)</label>
                    <input type="text" name="login" id="login" class="form-control form-control-lg" required autofocus>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-bold">Mot de passe</label>
                    <input type="password" name="password" id="password" class="form-control form-control-lg" required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-sicta-primary btn-lg">Se connecter</button>
                </div>
            </form>
        </div>
    </div>
</div>