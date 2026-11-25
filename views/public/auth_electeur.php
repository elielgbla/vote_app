<?php
// views/public/auth_electeur.php

declare(strict_types=1);

$contextFile = dirname(__DIR__) . '/_context.php';
if (is_file($contextFile)) {
    require_once $contextFile;
}

/** @var string $baseUrl */
/** @var string $csrfToken */
/** @var ?array $scrutin */

$baseUrl = $baseUrl ?? '';
$csrfToken = $csrfToken ?? '';
$scrutin = $scrutin ?? null;

$pageTitle = 'SICTA — Authentification Électeur';
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card sicta-card p-4">
            <div class="text-center mb-3">
                <img src="<?= $baseUrl ?>/assets/img/download.jpg" alt="SICTA" height="54" class="mb-2">
                <h1 class="h4 fw-bold" style="color: var(--sicta-dark);">Authentification pour Voter</h1>
                <p class="text-muted small mb-0">
                    <?= $scrutin ? htmlspecialchars($scrutin['TITRE']) : 'Aucun scrutin actif' ?>
                </p>
            </div>

            <?php if (!$scrutin): ?>
                <div class="alert alert-warning mb-0">Aucun scrutin actif en cours.</div>
            <?php else: ?>
                <form method="POST" action="<?= $baseUrl ?>/index.php?action=electeur.auth">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="mb-3">
                        <label for="cni" class="form-label fw-bold">Numéro CNI *</label>
                        <input type="text" name="cni" id="cni" class="form-control form-control-lg text-uppercase"
                               maxlength="12" required autofocus placeholder="Ex: CI00123456">
                    </div>

                    <div class="mb-4">
                        <label for="contact" class="form-label fw-bold">Téléphone ou Email enregistré *</label>
                        <input type="text" name="contact" id="contact" class="form-control form-control-lg"
                               required placeholder="Ex: 0700000000 ou nom@sicta.ci">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-sicta-primary btn-lg">
                            S’authentifier et accéder au vote
                        </button>
                        <a href="<?= $baseUrl ?>/index.php?action=public.ecran" class="btn btn-outline-secondary">
                            Retour au tableau de bord public
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>