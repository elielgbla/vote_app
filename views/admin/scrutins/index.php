<?php
// views/admin/scrutins/index.php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/_context.php';

/** @var string $baseUrl */
/** @var string $csrfToken */
/** @var array $scrutins */
/** @var ?array $scrutinDetail */
/** @var ?array $scrutinEdit */
/** @var array $categories */
/** @var array $historique */
/** @var array $individus */

$baseUrl = $baseUrl ?? '';
$csrfToken = $csrfToken ?? '';
$scrutins = $scrutins ?? [];
$scrutinDetail = $scrutinDetail ?? null;
$scrutinEdit = $scrutinEdit ?? null;
$categories = $categories ?? [];
$historique = $historique ?? [];
$individus = $individus ?? [];

$pageTitle = 'SICTA — Scrutins, Catégories & Candidats';
$statutsOrdre = ['brouillon', 'planifie', 'actif', 'cloture', 'publie', 'archive'];

$isEditing = is_array($scrutinEdit) && !empty($scrutinEdit['ID_SCRUTIN']);
$editId = (int) ($scrutinEdit['ID_SCRUTIN'] ?? 0);

$formAction = $isEditing
    ? $baseUrl . '/index.php?action=admin.scrutins.edit&id=' . $editId
    : $baseUrl . '/index.php?action=admin.scrutins.store';

$formatDatetimeLocal = static function (?string $raw): string {
    if ($raw === null || trim($raw) === '') {
        return '';
    }
    $ts = strtotime($raw);
    return $ts !== false ? date('Y-m-d\TH:i', $ts) : '';
};

$currentTitre = (string) ($scrutinEdit['TITRE'] ?? '');
$currentAnnee = (int) ($scrutinEdit['ANNEE'] ?? date('Y'));
$currentDebut = $formatDatetimeLocal($scrutinEdit['DATE_DEBUT'] ?? null);
$currentFin = $formatDatetimeLocal($scrutinEdit['DATE_FIN'] ?? null);
$currentDesc = (string) ($scrutinEdit['DESCRIPTION'] ?? '');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--sicta-dark);">Scrutins, Catégories &amp; Candidats</h1>
        <div class="text-muted small">Création, modification, suppression, pilotage des statuts et candidatures</div>
    </div>
    <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.scrutins.index#scrutinForm" class="btn btn-sicta-primary">
        Nouveau scrutin
    </a>
</div>

<div class="row g-4">
    <!-- Colonne gauche : Création / Édition scrutin & Liste -->
    <div class="col-lg-5">
        <div id="scrutinForm" class="card sicta-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0" style="color: var(--sicta-dark);">
                    <?= $isEditing ? 'Modifier le Scrutin #' . $editId : 'Nouveau Scrutin' ?>
                </h2>
                <?php if ($isEditing): ?>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.scrutins.index&scrutin=<?= $editId ?>" class="btn btn-sm btn-outline-secondary">
                        Annuler
                    </a>
                <?php endif; ?>
            </div>

            <form method="POST" action="<?= htmlspecialchars($formAction) ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                <?php if ($isEditing): ?>
                    <input type="hidden" name="id_scrutin" value="<?= $editId ?>">
                <?php endif; ?>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Titre *</label>
                    <input
                        type="text"
                        name="titre"
                        class="form-control"
                        value="<?= htmlspecialchars($currentTitre) ?>"
                        required
                        placeholder="Ex: Élection Délégués SICTA"
                    >
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-4">
                        <label class="form-label small fw-bold">Année *</label>
                        <input
                            type="number"
                            name="annee"
                            class="form-control"
                            value="<?= $currentAnnee ?>"
                            required
                        >
                    </div>
                    <div class="col-8">
                        <label class="form-label small fw-bold">Début *</label>
                        <input
                            type="datetime-local"
                            name="date_debut"
                            class="form-control"
                            value="<?= htmlspecialchars($currentDebut) ?>"
                            required
                        >
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Fin *</label>
                    <input
                        type="datetime-local"
                        name="date_fin"
                        class="form-control"
                        value="<?= htmlspecialchars($currentFin) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($currentDesc) ?></textarea>
                </div>

                <button type="submit" class="btn btn-sicta-primary w-100">
                    <?= $isEditing ? 'Enregistrer les modifications' : 'Créer le scrutin' ?>
                </button>
            </form>
        </div>

        <div class="card sicta-card p-4">
            <h2 class="h6 fw-bold mb-3">Scrutins enregistrés</h2>

            <?php if (empty($scrutins)): ?>
                <div class="text-muted small">Aucun scrutin enregistré.</div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($scrutins as $s): ?>
                        <?php
                        $sId = (int) ($s['ID_SCRUTIN'] ?? 0);
                        $sTitre = (string) ($s['TITRE'] ?? '');
                        $sAnnee = (int) ($s['ANNEE'] ?? 0);
                        $sStatut = (string) ($s['STATUT'] ?? '');
                        $active = is_array($scrutinDetail) && (int) ($scrutinDetail['ID_SCRUTIN'] ?? 0) === $sId;
                        ?>
                        <div class="list-group-item <?= $active ? 'active' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <a
                                    href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.scrutins.index&scrutin=<?= $sId ?>"
                                    class="text-decoration-none flex-grow-1 <?= $active ? 'text-white' : 'text-dark' ?>"
                                >
                                    <div class="fw-bold"><?= htmlspecialchars($sTitre) ?> (<?= $sAnnee ?>)</div>
                                    <small class="<?= $active ? 'text-light' : 'text-muted' ?>">
                                        Émargements : <?= (int) ($s['NB_EMARGEMENTS'] ?? 0) ?> · Catégories : <?= (int) ($s['NB_CATEGORIES'] ?? 0) ?>
                                    </small>
                                </a>
                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($sStatut) ?></span>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-2 pt-2 border-top <?= $active ? 'border-light-subtle' : '' ?>">
                                <a
                                    href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.scrutins.index&scrutin=<?= $sId ?>&edit=<?= $sId ?>#scrutinForm"
                                    class="btn btn-sm <?= $active ? 'btn-light' : 'btn-outline-dark' ?>"
                                >
                                    Modifier
                                </a>
                                <form
                                    method="POST"
                                    action="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.scrutins.delete"
                                    class="d-inline"
                                    onsubmit="return confirm('Supprimer le scrutin « <?= htmlspecialchars(addslashes($sTitre)) ?> » ?');"
                                >
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="id_scrutin" value="<?= $sId ?>">
                                    <button type="submit" class="btn btn-sm <?= $active ? 'btn-warning' : 'btn-outline-danger' ?>">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Colonne droite : Pilotage statut, Catégories, Candidatures & Historique -->
    <div class="col-lg-7">
        <?php if (is_array($scrutinDetail)): ?>
            <?php
            $detailId = (int) ($scrutinDetail['ID_SCRUTIN'] ?? 0);
            $detailTitre = (string) ($scrutinDetail['TITRE'] ?? '');
            $detailStatut = (string) ($scrutinDetail['STATUT'] ?? '');
            ?>
            <div class="card sicta-card p-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-0"><?= htmlspecialchars($detailTitre) ?></h2>
                        <small class="text-muted">Statut actuel : <strong><?= htmlspecialchars($detailStatut) ?></strong></small>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <a
                            href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.scrutins.index&scrutin=<?= $detailId ?>&edit=<?= $detailId ?>#scrutinForm"
                            class="btn btn-sm btn-outline-dark"
                        >
                            Modifier
                        </a>

                        <form
                            method="POST"
                            action="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.scrutins.delete"
                            class="d-inline"
                            onsubmit="return confirm('Supprimer définitivement le scrutin « <?= htmlspecialchars(addslashes($detailTitre)) ?> » ?');"
                        >
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="id_scrutin" value="<?= $detailId ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                        </form>

                        <form method="POST" action="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.scrutin.change_statut" class="d-flex gap-2">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="id_scrutin" value="<?= $detailId ?>">
                            <select name="statut" class="form-select form-select-sm">
                                <?php foreach ($statutsOrdre as $st): ?>
                                    <option value="<?= htmlspecialchars($st) ?>" <?= $detailStatut === $st ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($st) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-sicta-dark">Changer statut</button>
                        </form>
                    </div>
                </div>

                <!-- Ajouter une catégorie -->
                <form method="POST" action="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.categorie.store" class="row g-2 border-top pt-3">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id_scrutin" value="<?= $detailId ?>">
                    <div class="col-md-5">
                        <input type="text" name="lib" class="form-control form-control-sm" placeholder="Nouvelle catégorie *" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="description" class="form-control form-control-sm" placeholder="Description (optionnel)">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-sm btn-sicta-primary">Ajouter</button>
                    </div>
                </form>
            </div>

            <!-- Catégories & Inscription des candidats -->
            <?php foreach ($categories as $cat): ?>
                <div class="card sicta-card p-4 mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h3 class="h6 fw-bold mb-0" style="color: var(--sicta-dark);">
                            <?= htmlspecialchars((string) ($cat['LIB'] ?? '')) ?>
                        </h3>

                        <form method="POST" action="<?= htmlspecialchars($baseUrl) ?>/index.php?action=admin.candidat.store" class="d-flex gap-2">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="id_scrutin" value="<?= $detailId ?>">
                            <input type="hidden" name="id_categorie" value="<?= (int) ($cat['ID_CATEGORIE'] ?? 0) ?>">
                            <select name="cni" class="form-select form-select-sm" required>
                                <option value="">-- Choisir un individu --</option>
                                <?php foreach ($individus as $ind): ?>
                                    <?php $cniOpt = (string) ($ind['CNI'] ?? ''); ?>
                                    <option value="<?= htmlspecialchars($cniOpt) ?>">
                                        <?= htmlspecialchars((string) ($ind['NOM_PRENOMS'] ?? '') . ' (' . $cniOpt . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-sicta-primary text-nowrap">+ Candidat</button>
                        </form>
                    </div>

                    <?php if (empty($cat['candidats'])): ?>
                        <div class="text-muted small">Aucun candidat dans cette catégorie.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($cat['candidats'] as $cand): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <span>
                                        <strong><?= htmlspecialchars((string) ($cand['NOM_PRENOMS'] ?? '')) ?></strong>
                                        <small class="text-muted">(<?= htmlspecialchars((string) ($cand['CNI'] ?? '')) ?>)</small>
                                    </span>
                                    <span class="badge bg-secondary"><?= (int) ($cand['NB_VOIX'] ?? 0) ?> voix</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Historique des statuts -->
            <div class="card sicta-card p-4">
                <h3 class="h6 fw-bold mb-2">Historique des statuts</h3>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Ancien</th>
                                <th>Nouveau</th>
                                <th>Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historique)): ?>
                                <tr>
                                    <td colspan="4" class="text-muted small">Aucun changement de statut enregistré.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($historique as $h): ?>
                                    <tr>
                                        <td class="small"><?= htmlspecialchars((string) ($h['DATE_CHANGEMENT'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($h['ANCIEN_STATUT'] ?? '')) ?></td>
                                        <td><strong><?= htmlspecialchars((string) ($h['NOUVEAU_STATUT'] ?? '')) ?></strong></td>
                                        <td class="small"><?= htmlspecialchars((string) ($h['ADMIN_NOM'] ?? 'Système')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>