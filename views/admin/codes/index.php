<?php
// views/admin/codes/index.php
require dirname(__DIR__, 2) . '/_context.php';
/** @var string $baseUrl */
/** @var string $csrfToken */
/** @var ?array $codesEnClairGenere */
/** @var array $scrutins */
/** @var int $selectedScrutinId */
/** @var array $sites */
/** @var array $lots */
$pageTitle = 'SICTA — Lots & Codes de Vote';
?>

<?php if (!empty($codesEnClairGenere)): ?>
    <div class="alert alert-warning border-2 shadow-sm mb-4">
        <div class="fw-bold mb-2">Codes générés pour le lot #<?= (int) $codesEnClairGenere['id_lot'] ?> (à imprimer / remettre maintenant) :</div>
        <div class="row g-2 code-print-grid">
            <?php foreach ($codesEnClairGenere['codes'] as $codeClair): ?>
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="bg-white border rounded p-2 text-center fw-bold"><?= htmlspecialchars($codeClair) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card sicta-card p-4">
            <h1 class="h5 fw-bold mb-3" style="color: var(--sicta-dark);">Générer un Lot de Codes</h1>
            <form method="POST" action="<?= $baseUrl ?>/index.php?action=admin.codes.generate">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="mb-2">
                    <label class="form-label small fw-bold">Scrutin *</label>
                    <select name="id_scrutin" class="form-select" required>
                        <?php foreach ($scrutins as $s): ?>
                            <option value="<?= (int) $s['ID_SCRUTIN'] ?>" <?= ((int) ($selectedScrutinId ?? 0) === (int) $s['ID_SCRUTIN']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['TITRE'] . ' (' . $s['ANNEE'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Type de porteur *</label>
                    <select name="type_porteur" class="form-select" required>
                        <option value="employe">employe</option>
                        <option value="client">client</option>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Point de distribution (Site)</label>
                    <select name="code_site" class="form-select">
                        <option value="">-- Tous / Non spécifié --</option>
                        <?php foreach ($sites as $site): ?>
                            <option value="<?= htmlspecialchars($site['CODE_SITE']) ?>"><?= htmlspecialchars($site['LIB']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Quantité (1 à 1000) *</label>
                    <input type="number" name="quantite" class="form-control" min="1" max="1000" value="25" required>
                </div>

                <button type="submit" class="btn btn-sicta-primary w-100">Générer le lot</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card sicta-card p-4">
            <h2 class="h5 fw-bold mb-3">Lots existants</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Lot</th><th>Porteur</th><th>Site</th><th>Total</th><th>Générés</th><th>Distribués</th><th>Utilisés</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lots as $lot): ?>
                            <tr>
                                <td class="fw-bold">#<?= (int) $lot['ID_LOT'] ?></td>
                                <td><?= htmlspecialchars($lot['TYPE_PORTEUR']) ?></td>
                                <td><?= htmlspecialchars($lot['SITE_LIB'] ?? '-') ?></td>
                                <td><?= (int) $lot['QUANTITE'] ?></td>
                                <td><?= (int) $lot['NB_GENERES'] ?></td>
                                <td><?= (int) $lot['NB_DISTRIBUES'] ?></td>
                                <td><?= (int) $lot['NB_UTILISES'] ?></td>
                                <td>
                                    <?php if ((int) $lot['NB_GENERES'] > 0): ?>
                                        <form method="POST" action="<?= $baseUrl ?>/index.php?action=admin.codes.distribute" class="d-inline">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="id_scrutin" value="<?= (int) $lot['ID_SCRUTIN'] ?>">
                                            <input type="hidden" name="id_lot" value="<?= (int) $lot['ID_LOT'] ?>">
                                            <button type="submit" class="btn btn-sm btn-sicta-dark">Distribuer</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Traité</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>