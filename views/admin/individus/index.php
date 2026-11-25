<?php
// views/admin/individus/index.php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/_context.php';

/** @var string $baseUrl */
/** @var string $csrfToken */
/** @var array $sites */
/** @var string $search */
/** @var string $type */
/** @var array $individus */
/** @var ?array $individuEdit */

$baseUrl = $baseUrl ?? '';
$csrfToken = $csrfToken ?? '';
$sites = $sites ?? [];
$search = $search ?? '';
$type = $type ?? '';
$individus = $individus ?? [];
$individuEdit = $individuEdit ?? null;

$isEditing = is_array($individuEdit) && !empty($individuEdit['CNI']);
$formAction = $isEditing
    ? $baseUrl . '/index.php?action=admin.individus.edit&cni=' . urlencode((string) $individuEdit['CNI'])
    : $baseUrl . '/index.php?action=admin.individus.store';

$currentCni = (string) ($individuEdit['CNI'] ?? '');
$currentNom = (string) ($individuEdit['NOM_PRENOMS'] ?? '');
$currentType = (string) ($individuEdit['TYPE_INDIVIDU'] ?? 'employe');
$currentSite = (string) ($individuEdit['CODE_SITE'] ?? '');
$currentTel = (string) ($individuEdit['TEL'] ?? '');
$currentEmail = (string) ($individuEdit['EMAIL'] ?? '');
$currentService = (string) ($individuEdit['SERVICE'] ?? '');
$currentAdresse = (string) ($individuEdit['ADRESSE'] ?? '');
$currentPhoto = (string) ($individuEdit['PHOTO'] ?? '');

$currentPhotoUrl = $currentPhoto !== ''
    ? $baseUrl . '/index.php?action=public.photo&f=' . urlencode($currentPhoto)
    : $baseUrl . '/assets/img/default-avatar.svg';

$pageTitle = 'SICTA — Individus & Photos';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--sicta-dark);">Individus &amp; Photos</h1>
        <div class="text-muted small">Création, modification et suppression des électeurs et candidats</div>
    </div>
    <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.individus.create" class="btn btn-sicta-primary">
        Nouvel individu
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card sicta-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0" style="color: var(--sicta-dark);">
                    <?= $isEditing ? 'Modifier un Individu' : 'Ajouter / Mettre à jour un Individu' ?>
                </h2>
                <?php if ($isEditing): ?>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.individus.index" class="btn btn-sm btn-outline-secondary">
                        Annuler
                    </a>
                <?php endif; ?>
            </div>

            <form method="POST" action="<?= htmlspecialchars($formAction) ?>" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="mb-2">
                    <label class="form-label small fw-bold">CNI (max 12) *</label>
                    <input
                        type="text"
                        name="cni"
                        class="form-control text-uppercase"
                        maxlength="12"
                        value="<?= htmlspecialchars($currentCni) ?>"
                        <?= $isEditing ? 'readonly' : 'required' ?>
                    >
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Nom &amp; Prénoms *</label>
                    <input
                        type="text"
                        name="nom_prenoms"
                        class="form-control"
                        maxlength="100"
                        value="<?= htmlspecialchars($currentNom) ?>"
                        required
                    >
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Type *</label>
                        <select name="type_individu" class="form-select" required>
                            <option value="employe" <?= $currentType === 'employe' ? 'selected' : '' ?>>employe</option>
                            <option value="client" <?= $currentType === 'client' ? 'selected' : '' ?>>client</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Site</label>
                        <select name="code_site" class="form-select">
                            <option value="">-- Aucun --</option>
                            <?php foreach ($sites as $site): ?>
                                <?php $codeSite = (string) ($site['CODE_SITE'] ?? ''); ?>
                                <option value="<?= htmlspecialchars($codeSite) ?>" <?= $currentSite === $codeSite ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($site['LIB'] ?? $codeSite)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Téléphone</label>
                    <input
                        type="text"
                        name="tel"
                        class="form-control"
                        maxlength="20"
                        value="<?= htmlspecialchars($currentTel) ?>"
                    >
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Email</label>
                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        maxlength="100"
                        value="<?= htmlspecialchars($currentEmail) ?>"
                    >
                    <div class="form-text">Renseignez le téléphone ou l’email (au moins l’un des deux) pour l’authentification.</div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Service</label>
                    <input
                        type="text"
                        name="service"
                        class="form-control"
                        maxlength="50"
                        value="<?= htmlspecialchars($currentService) ?>"
                    >
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Adresse</label>
                    <input
                        type="text"
                        name="adresse"
                        class="form-control"
                        maxlength="100"
                        value="<?= htmlspecialchars($currentAdresse) ?>"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Photo (JPG, PNG, WEBP — max 2 Mo)</label>
                    <?php if ($isEditing): ?>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <img src="<?= htmlspecialchars($currentPhotoUrl) ?>" alt="Photo actuelle" width="38" height="38" class="rounded-circle object-fit-cover border">
                            <span class="small text-muted">Laisser vide pour conserver la photo actuelle</span>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
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
                <input type="hidden" name="action" value="admin.individus.index">
                <div class="col-md-6">
                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Rechercher CNI, nom, service..."
                    >
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">Tous types</option>
                        <option value="employe" <?= $type === 'employe' ? 'selected' : '' ?>>employe</option>
                        <option value="client" <?= $type === 'client' ? 'selected' : '' ?>>client</option>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-sicta-dark">Filtrer</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>CNI</th>
                            <th>Nom &amp; Prénoms</th>
                            <th>Type</th>
                            <th>Service / Site</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($individus)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Aucun individu trouvé.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($individus as $ind): ?>
                                <?php
                                $cniRow = (string) ($ind['CNI'] ?? '');
                                $photoUrl = !empty($ind['PHOTO'])
                                    ? $baseUrl . '/index.php?action=public.photo&f=' . urlencode((string) $ind['PHOTO'])
                                    : $baseUrl . '/assets/img/default-avatar.svg';
                                ?>
                                <tr>
                                    <td>
                                        <img src="<?= htmlspecialchars($photoUrl) ?>" alt="" width="42" height="42" class="rounded-circle object-fit-cover border">
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($cniRow) ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($ind['NOM_PRENOMS'] ?? '')) ?></div>
                                        <?php if (!empty($ind['TEL'])): ?>
                                            <div class="small text-muted"><?= htmlspecialchars((string) $ind['TEL']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars((string) ($ind['TYPE_INDIVIDU'] ?? '')) ?></span>
                                    </td>
                                    <td class="small">
                                        <?= htmlspecialchars(((string) ($ind['SERVICE'] ?? '-')) . ' / ' . ((string) ($ind['SITE_LIB'] ?? '-'))) ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a
                                            href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.individus.edit&cni=<?= urlencode($cniRow) ?>"
                                            class="btn btn-sm btn-outline-dark"
                                        >
                                            Modifier
                                        </a>
                                        <form
                                            method="POST"
                                            action="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.individus.delete"
                                            class="d-inline"
                                            onsubmit="return confirm('Supprimer l’individu <?= htmlspecialchars(addslashes($cniRow)) ?> ?');"
                                        >
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="cni" value="<?= htmlspecialchars($cniRow) ?>">
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