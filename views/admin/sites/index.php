<?php
// views/admin/sites/index.php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/_context.php';

/** @var string $baseUrl */
/** @var string $csrfToken */
/** @var array $sites */
/** @var array $admins */
/** @var ?array $siteEdit */

$baseUrl = $baseUrl ?? '';
$csrfToken = $csrfToken ?? '';
$sites = $sites ?? [];
$admins = $admins ?? [];
$siteEdit = $siteEdit ?? null;

$isEditing = is_array($siteEdit) && !empty($siteEdit['CODE_SITE']);
$formAction = $isEditing
    ? $baseUrl . '/index.php?action=admin.sites.edit&code=' . urlencode((string) $siteEdit['CODE_SITE'])
    : $baseUrl . '/index.php?action=admin.sites.store';

$currentCode = (string) ($siteEdit['CODE_SITE'] ?? '');
$currentLib = (string) ($siteEdit['LIB'] ?? '');
$currentVille = (string) ($siteEdit['VILLE'] ?? '');
$selectedAdminId = (int) ($siteEdit['ID_ADMIN'] ?? ($admins[0]['ID_ADMIN'] ?? 0));

$pageTitle = 'SICTA — Gestion des Sites';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--sicta-dark);">Sites SICTA</h1>
        <div class="text-muted small">Gestion des agences, centres techniques et responsables</div>
    </div>
    <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.sites.create" class="btn btn-sicta-primary">
        Nouveau site
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card sicta-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0" style="color: var(--sicta-dark);">
                    <?= $isEditing ? 'Modifier un Site' : 'Ajouter un Site' ?>
                </h2>
                <?php if ($isEditing): ?>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.sites.index" class="btn btn-sm btn-outline-secondary">
                        Annuler
                    </a>
                <?php endif; ?>
            </div>

            <form method="POST" action="<?= htmlspecialchars($formAction) ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="mb-2">
                    <label class="form-label small fw-bold">Code Site *</label>
                    <input
                        type="text"
                        name="code_site"
                        class="form-control text-uppercase"
                        maxlength="5"
                        value="<?= htmlspecialchars($currentCode) ?>"
                        <?= $isEditing ? 'readonly' : 'required' ?>
                    >
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Libellé *</label>
                    <input
                        type="text"
                        name="lib"
                        class="form-control"
                        maxlength="60"
                        value="<?= htmlspecialchars($currentLib) ?>"
                        required
                    >
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Ville</label>
                    <input
                        type="text"
                        name="ville"
                        class="form-control"
                        maxlength="40"
                        value="<?= htmlspecialchars($currentVille) ?>"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Responsable (Admin) *</label>
                    <select name="id_admin" class="form-select" required>
                        <?php foreach ($admins as $adm): ?>
                            <?php $admId = (int) ($adm['ID_ADMIN'] ?? 0); ?>
                            <option value="<?= $admId ?>" <?= $selectedAdminId === $admId ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($adm['NOM_PRENOMS'] ?? $adm['LOGIN'] ?? ('Admin #' . $admId))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-sicta-primary w-100">
                    <?= $isEditing ? 'Mettre à jour' : 'Enregistrer' ?>
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card sicta-card p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Libellé</th>
                            <th>Ville</th>
                            <th>Responsable</th>
                            <th class="text-center">Individus</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sites)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Aucun site enregistré.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sites as $site): ?>
                                <?php $codeRow = (string) ($site['CODE_SITE'] ?? ''); ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($codeRow) ?></td>
                                    <td><?= htmlspecialchars((string) ($site['LIB'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($site['VILLE'] ?? '-')) ?></td>
                                    <td class="small"><?= htmlspecialchars((string) ($site['RESPONSABLE_NOM'] ?? '-')) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= (int) ($site['NB_INDIVIDUS'] ?? 0) ?></span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a
                                            href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.sites.edit&code=<?= urlencode($codeRow) ?>"
                                            class="btn btn-sm btn-outline-dark"
                                        >
                                            Modifier
                                        </a>
                                        <form
                                            method="POST"
                                            action="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.sites.delete"
                                            class="d-inline"
                                            onsubmit="return confirm('Supprimer le site <?= htmlspecialchars(addslashes($codeRow)) ?> ?');"
                                        >
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="code_site" value="<?= htmlspecialchars($codeRow) ?>">
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