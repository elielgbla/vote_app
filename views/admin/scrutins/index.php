<?php
// views/admin/scrutins/index.php
/** @var string $baseUrl */
/** @var string $csrfToken */
/** @var array $scrutins */
/** @var ?array $scrutinDetail */
/** @var array $categories */
/** @var array $historique */
/** @var array $individus */
$pageTitle = 'SICTA — Scrutins, Catégories & Candidats';
$statutsOrdre = ['brouillon', 'planifie', 'actif', 'cloture', 'publie', 'archive'];
?>

<div class="row g-4">
    <!-- Colonne gauche : Création scrutin & Liste -->
    <div class="col-lg-5">
        <div class="card sicta-card p-4 mb-4">
            <h1 class="h5 fw-bold mb-3" style="color: var(--sicta-dark);">Nouveau Scrutin</h1>
            <form method="POST" action="<?= $baseUrl ?>/index.php?action=admin.scrutin.store">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="mb-2">
                    <label class="form-label small fw-bold">Titre *</label>
                    <input type="text" name="titre" class="form-control" required placeholder="Ex: Élection Délégués SICTA">
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-4">
                        <label class="form-label small fw-bold">Année *</label>
                        <input type="number" name="annee" class="form-control" value="<?= date('Y') ?>" required>
                    </div>
                    <div class="col-8">
                        <label class="form-label small fw-bold">Début *</label>
                        <input type="datetime-local" name="date_debut" class="form-control" required>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold">Fin *</label>
                    <input type="datetime-local" name="date_fin" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-sicta-primary w-100">Créer le scrutin</button>
            </form>
        </div>

        <div class="card sicta-card p-4">
            <h2 class="h6 fw-bold mb-3">Scrutins enregistrés</h2>
            <div class="list-group">
                <?php foreach ($scrutins as $s): ?>
                    <?php $active = $scrutinDetail && (int) $scrutinDetail['ID_SCRUTIN'] === (int) $s['ID_SCRUTIN']; ?>
                    <a href="<?= $baseUrl ?>/index.php?action=admin.scrutins.index&scrutin=<?= (int) $s['ID_SCRUTIN'] ?>"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $active ? 'active' : '' ?>">
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($s['TITRE']) ?> (<?= (int) $s['ANNEE'] ?>)</div>
                            <small>Émargements : <?= (int) $s['NB_EMARGEMENTS'] ?> · Catégories : <?= (int) $s['NB_CATEGORIES'] ?></small>
                        </div>
                        <span class="badge bg-warning text-dark"><?= htmlspecialchars($s['STATUT']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Colonne droite : Pilotage statut, Catégories, Candidatures & Historique -->
    <div class="col-lg-7">
        <?php if ($scrutinDetail): ?>
            <div class="card sicta-card p-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-0"><?= htmlspecialchars($scrutinDetail['TITRE']) ?></h2>
                        <small class="text-muted">Statut actuel : <strong><?= htmlspecialchars($scrutinDetail['STATUT']) ?></strong></small>
                    </div>

                    <form method="POST" action="<?= $baseUrl ?>/index.php?action=admin.scrutin.change_statut" class="d-flex gap-2">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="id_scrutin" value="<?= (int) $scrutinDetail['ID_SCRUTIN'] ?>">
                        <select name="statut" class="form-select form-select-sm">
                            <?php foreach ($statutsOrdre as $st): ?>
                                <option value="<?= $st ?>" <?= $scrutinDetail['STATUT'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-sicta-dark">Changer statut</button>
                    </form>
                </div>

                <!-- Ajouter une catégorie -->
                <form method="POST" action="<?= $baseUrl ?>/index.php?action=admin.categorie.store" class="row g-2 border-top pt-3">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id_scrutin" value="<?= (int) $scrutinDetail['ID_SCRUTIN'] ?>">
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
                        <h3 class="h6 fw-bold mb-0" style="color: var(--sicta-dark);"><?= htmlspecialchars($cat['LIB']) ?></h3>

                        <form method="POST" action="<?= $baseUrl ?>/index.php?action=admin.candidat.store" class="d-flex gap-2">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="id_scrutin" value="<?= (int) $scrutinDetail['ID_SCRUTIN'] ?>">
                            <input type="hidden" name="id_categorie" value="<?= (int) $cat['ID_CATEGORIE'] ?>">
                            <select name="cni" class="form-select form-select-sm" required>
                                <option value="">-- Choisir un individu --</option>
                                <?php foreach ($individus as $ind): ?>
                                    <option value="<?= htmlspecialchars($ind['CNI']) ?>">
                                        <?= htmlspecialchars($ind['NOM_PRENOMS'] . ' (' . $ind['CNI'] . ')') ?>
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
                                    <span><strong><?= htmlspecialchars($cand['NOM_PRENOMS']) ?></strong> <small class="text-muted">(<?= htmlspecialchars($cand['CNI']) ?>)</small></span>
                                    <span class="badge bg-secondary"><?= (int) $cand['NB_VOIX'] ?> voix</span>
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
                            <tr><th>Date</th><th>Ancien</th><th>Nouveau</th><th>Admin</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historique as $h): ?>
                                <tr>
                                    <td class="small"><?= htmlspecialchars($h['DATE_CHANGEMENT']) ?></td>
                                    <td><?= htmlspecialchars($h['ANCIEN_STATUT']) ?></td>
                                    <td><strong><?= htmlspecialchars($h['NOUVEAU_STATUT']) ?></strong></td>
                                    <td class="small"><?= htmlspecialchars($h['ADMIN_NOM'] ?? 'Système') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>