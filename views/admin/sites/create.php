<?php
// views/admin/sites/create.php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/_context.php';

/** @var string $baseUrl */
/** @var string $csrfToken */
/** @var array $admins */

$baseUrl = $baseUrl ?? '';
$csrfToken = $csrfToken ?? '';
$admins = $admins ?? [];
$pageTitle = 'SICTA — Nouveau Site';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 fw-bold mb-0" style="color: var(--sicta-dark);">Créer un Site</h1>
    <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.sites.index" class="btn btn-outline-secondary">
        Retour à la liste
    </a>
</div>

<div class="card sicta-card p-4" style="max-width: 680px;">
    <form method="POST" action="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.sites.create">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

        <div class="mb-3">
            <label class="form-label fw-bold">Code Site *</label>
            <input type="text" name="code_site" class="form-control text-uppercase" maxlength="5" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Libellé *</label>
            <input type="text" name="lib" class="form-control" maxlength="60" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Ville</label>
            <input type="text" name="ville" class="form-control" maxlength="40">
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Responsable (Admin) *</label>
            <select name="id_admin" class="form-select" required>
                <?php foreach ($admins as $adm): ?>
                    <?php $admId = (int) ($adm['ID_ADMIN'] ?? 0); ?>
                    <option value="<?= $admId ?>">
                        <?= htmlspecialchars((string) ($adm['NOM_PRENOMS'] ?? $adm['LOGIN'] ?? ('Admin #' . $admId))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sicta-primary">Enregistrer le site</button>
            <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.sites.index" class="btn btn-outline-secondary">Annuler</a>
        </div>
    </form>
</div>