<?php
// views/admin/journal/index.php
/** @var string $baseUrl */
/** @var array $filters */
/** @var array $scrutins */
/** @var array $entrees */
$pageTitle = 'SICTA — Journal d’Audit';
?>

<div class="card sicta-card p-4">
    <h1 class="h5 fw-bold mb-3" style="color: var(--sicta-dark);">Journal des actions (table JOURNAL)</h1>

    <form method="GET" action="<?= $baseUrl ?>/index.php" class="row g-2 mb-3">
        <input type="hidden" name="action" value="admin.journal.index">
        <div class="col-md-4">
            <input type="text" name="f_action" class="form-control" value="<?= htmlspecialchars($filters['action'] ?? '') ?>" placeholder="Filtrer par action...">
        </div>
        <div class="col-md-3">
            <input type="text" name="f_cni" class="form-control text-uppercase" value="<?= htmlspecialchars($filters['cni'] ?? '') ?>" placeholder="Filtrer par CNI...">
        </div>
        <div class="col-md-3">
            <select name="f_scrutin" class="form-select">
                <option value="">Tous scrutins</option>
                <?php foreach ($scrutins as $s): ?>
                    <option value="<?= (int) $s['ID_SCRUTIN'] ?>" <?= ((string) ($filters['id_scrutin'] ?? '') === (string) $s['ID_SCRUTIN']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['TITRE']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-sicta-dark">Filtrer</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0">
            <thead>
                <tr><th>Date</th><th>Action</th><th>Table</th><th>Admin / CNI</th><th>IP</th><th>Détail</th></tr>
            </thead>
            <tbody>
                <?php foreach ($entrees as $e): ?>
                    <tr>
                        <td class="small text-nowrap"><?= htmlspecialchars($e['DATE_ACTION']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($e['ACTION']) ?></span></td>
                        <td class="small"><?= htmlspecialchars($e['TABLE_CONCERNE'] ?? '-') ?></td>
                        <td class="small"><?= htmlspecialchars(($e['ADMIN_NOM'] ?? '-') . ($e['CNI'] ? ' / ' . $e['CNI'] : '')) ?></td>
                        <td class="small"><?= htmlspecialchars($e['IP'] ?? '-') ?></td>
                        <td class="small"><?= htmlspecialchars($e['DETAIL'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>