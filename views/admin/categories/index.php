<?php
// views/admin/categories/index.php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/_context.php';

/** @var string $baseUrl */
/** @var string $csrfToken */
/** @var array $categories */
/** @var array $scrutins */
/** @var ?int $scrutinId */
/** @var string $search */
/** @var ?array $categorieEdit */

$baseUrl = $baseUrl ?? '';
$csrfToken = $csrfToken ?? '';
$categories = $categories ?? [];
$scrutins = $scrutins ?? [];
$scrutinId = isset($scrutinId) && $scrutinId !== null ? (int) $scrutinId : null;
$search = $search ?? '';
$categorieEdit = $categorieEdit ?? null;

$isEditing = is_array($categorieEdit) && !empty($categorieEdit['ID_CATEGORIE']);
$editId = (int) ($categorieEdit['ID_CATEGORIE'] ?? 0);

$formAction = $isEditing
    ? $baseUrl . '/index.php?action=admin.categories.edit&id=' . $editId
    : $baseUrl . '/index.php?action=admin.categories.store';

$defaultScrutinId = $scrutinId ?? (int) ($scrutins[0]['ID_SCRUTIN'] ?? 0);
$currentScrutin = (int) ($categorieEdit['ID_SCRUTIN'] ?? $defaultScrutinId);
$currentLib = (string) ($categorieEdit['LIB'] ?? '');
$currentDesc = (string) ($categorieEdit['DESCRIPTION'] ?? '');

$pageTitle = 'SICTA — Gestion des Catégories';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--sicta-dark);">Catégories de Scrutin</h1>
        <div class="text-muted small">Création, modification et suppression des collèges / catégories par scrutin</div>
    </div>
    <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.categories.create<?= $scrutinId ? '&scrutin_id=' . $scrutinId : '' ?>" class="btn btn-sicta-primary">
        Nouvelle catégorie
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card sicta-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0" style="color: var(--sicta-dark);">
                    <?= $isEditing ? 'Modifier une Catégorie' : 'Ajouter une Catégorie' ?>
                </h2>
                <?php if ($isEditing): ?>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.categories.index<?= $scrutinId ? '&scrutin_id=' . $scrutinId : '' ?>" class="btn btn-sm btn-outline-secondary">
                        Annuler
                    </a>
                <?php endif; ?>
            </div>

            <form method="POST" action="<?= htmlspecialchars($formAction) ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                <?php if ($isEditing): ?>
                    <input type="hidden" name="id_categorie" value="<?= $editId ?>">
                <?php endif; ?>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Scrutin *</label>
                    <select name="id_scrutin" class="form-select" required>
                        <?php foreach ($scrutins as $sc): ?>
                            <?php $scId = (int) ($sc['ID_SCRUTIN'] ?? 0); ?>
                            <option value="<?= $scId ?>" <?= $currentScrutin === $scId ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($sc['TITRE'] ?? ('Scrutin #' . $scId)) . ' (' . (string) ($sc['ANNEE'] ?? '') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Libellé (max 100) *</label>
                    <input
                        type="text"
                        name="lib"
                        class="form-control"
                        maxlength="100"
                        value="<?= htmlspecialchars($currentLib) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Description</label>
                    <textarea
                        name="description"
                        class="form-control"
                        rows="3"
                    ><?= htmlspecialchars($currentDesc) ?></textarea>
                </div>

                <button type="submit" class="btn btn-sicta-primary w-100">
                    <?= $isEditing ? 'Mettre à jour' : 'Enregistrer' ?>
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card sicta-card p-4">
            <form method="GET" action="<?= htmlspecialchars($baseUrl) ?>/index.php" class="row g-2 mb-3">
                <input type="hidden" name="action" value="admin.categories.index">
                <div class="col-md-5">
                    <select name="scrutin_id" class="form-select">
                        <option value="">Tous les scrutins</option>
                        <?php foreach ($scrutins as $sc): ?>
                            <?php $scId = (int) ($sc['ID_SCRUTIN'] ?? 0); ?>
                            <option value="<?= $scId ?>" <?= $scrutinId === $scId ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($sc['TITRE'] ?? ('Scrutin #' . $scId)) . ' (' . (string) ($sc['ANNEE'] ?? '') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Rechercher libellé, description..."
                    >
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-sicta-dark">Filtrer</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Libellé</th>
                            <th>Scrutin</th>
                            <th>Description</th>
                            <th class="text-center">Candidats</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Aucune catégorie enregistrée.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <?php
                                $catId = (int) ($cat['ID_CATEGORIE'] ?? 0);
                                $catLib = (string) ($cat['LIB'] ?? '');
                                ?>
                                <tr>
                                    <td class="text-muted small"><?= $catId ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($catLib) ?></td>
                                    <td class="small">
                                        <?= htmlspecialchars((string) ($cat['SCRUTIN_TITRE'] ?? '')) ?>
                                        <span class="text-muted">(<?= htmlspecialchars((string) ($cat['SCRUTIN_ANNEE'] ?? '')) ?>)</span>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars((string) ($cat['DESCRIPTION'] ?? '-')) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= (int) ($cat['NB_CANDIDATS'] ?? 0) ?></span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a
                                            href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.categories.edit&id=<?= $catId ?>"
                                            class="btn btn-sm btn-outline-dark"
                                        >
                                            Modifier
                                        </a>
                                        <form
                                            method="POST"
                                            action="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.categories.delete"
                                            class="d-inline"
                                            onsubmit="return confirm('Supprimer la catégorie « <?= htmlspecialchars(addslashes($catLib)) ?> » ?');"
                                        >
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="id_categorie" value="<?= $catId ?>">
                                            <?php if ($scrutinId): ?>
                                                <input type="hidden" name="scrutin_id" value="<?= $scrutinId ?>">
                                            <?php endif; ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>