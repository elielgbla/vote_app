<?php
// views/admin/categories/edit.php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/_context.php';

/** @var string $baseUrl */
/** @var string $csrfToken */
/** @var array $categorie */
/** @var array $scrutins */

$baseUrl = $baseUrl ?? '';
$csrfToken = $csrfToken ?? '';
$categorie = $categorie ?? [];
$scrutins = $scrutins ?? [];

$idCat = (int) ($categorie['ID_CATEGORIE'] ?? 0);
$idScrutin = (int) ($categorie['ID_SCRUTIN'] ?? 0);
$lib = (string) ($categorie['LIB'] ?? '');
$description = (string) ($categorie['DESCRIPTION'] ?? '');

$pageTitle = 'SICTA — Modifier la Catégorie #' . $idCat;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 fw-bold mb-0" style="color: var(--sicta-dark);">Modifier la Catégorie #<?= $idCat ?></h1>
    <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.categories.index" class="btn btn-outline-secondary">
        Retour à la liste
    </a>
</div>

<div class="card sicta-card p-4" style="max-width: 680px;">
    <form method="POST" action="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.categories.edit&id=<?= $idCat ?>">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="id_categorie" value="<?= $idCat ?>">

        <div class="mb-3">
            <label class="form-label fw-bold">Scrutin *</label>
            <select name="id_scrutin" class="form-select" required>
                <?php foreach ($scrutins as $sc): ?>
                    <?php $scId = (int) ($sc['ID_SCRUTIN'] ?? 0); ?>
                    <option value="<?= $scId ?>" <?= $idScrutin === $scId ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) ($sc['TITRE'] ?? ('Scrutin #' . $scId)) . ' (' . (string) ($sc['ANNEE'] ?? '') . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Libellé (max 100) *</label>
            <input type="text" name="lib" class="form-control" maxlength="100" value="<?= htmlspecialchars($lib) ?>" required>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Description</label>
            <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($description) ?></textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sicta-primary">Mettre à jour</button>
            <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.categories.index" class="btn btn-outline-secondary">Annuler</a>
        </div>
    </form>
</div>