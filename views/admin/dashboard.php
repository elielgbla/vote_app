<?php
// views/admin/dashboard.php
/** @var string $baseUrl */
/** @var array $scrutins */
/** @var ?array $scrutinCourant */
/** @var array $kpis */
/** @var array $resultatsGroupes */
$pageTitle = 'SICTA — Tableau de bord Admin';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--sicta-dark);">Tableau de bord du Scrutin</h1>
        <div class="text-muted small">Suivi des émargements, codes de vote et résultats par catégorie</div>
    </div>

    <?php if (!empty($scrutins)): ?>
        <form method="GET" action="<?= $baseUrl ?>/index.php" class="d-flex align-items-center gap-2">
            <input type="hidden" name="action" value="admin.dashboard">
            <label for="selectScrutin" class="form-label mb-0 small fw-bold">Scrutin :</label>
            <select name="scrutin" id="selectScrutin" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($scrutins as $s): ?>
                    <option value="<?= (int) $s['ID_SCRUTIN'] ?>"
                        <?= ($scrutinCourant && (int) $scrutinCourant['ID_SCRUTIN'] === (int) $s['ID_SCRUTIN']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['TITRE'] . ' (' . $s['ANNEE'] . ') - ' . $s['STATUT']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card sicta-card kpi-card p-3 h-100">
            <div class="text-muted small">Émargements / Électeurs</div>
            <div class="kpi-value"><?= (int) ($kpis['emargements'] ?? 0) ?> <span class="fs-6 text-muted">/ <?= (int) ($kpis['total_electeurs'] ?? 0) ?></span></div>
            <div class="small text-success fw-semibold">Participation : <?= htmlspecialchars((string) ($kpis['taux_participation'] ?? 0)) ?> %</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card sicta-card kpi-card p-3 h-100">
            <div class="text-muted small">Codes générés (total)</div>
            <div class="kpi-value"><?= (int) ($kpis['total_codes'] ?? 0) ?></div>
            <div class="small text-muted">En attente : <?= (int) ($kpis['codes_generes'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card sicta-card kpi-card p-3 h-100">
            <div class="text-muted small">Codes distribués</div>
            <div class="kpi-value"><?= (int) ($kpis['codes_distribues'] ?? 0) ?></div>
            <div class="small text-muted">Annulés : <?= (int) ($kpis['codes_annules'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card sicta-card kpi-card p-3 h-100">
            <div class="text-muted small">Codes utilisés (votes)</div>
            <div class="kpi-value" style="color: var(--sicta-orange);"><?= (int) ($kpis['codes_utilises'] ?? 0) ?></div>
            <div class="small text-muted">Statut : <?= htmlspecialchars($scrutinCourant['STATUT'] ?? 'aucun') ?></div>
        </div>
    </div>
</div>

<?php if (!empty($resultatsGroupes)): ?>
    <div class="row g-4" id="adminResultatsCharts" data-resultats='<?= htmlspecialchars(json_encode($resultatsGroupes, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'>
        <?php foreach ($resultatsGroupes as $idx => $groupe): ?>
            <div class="col-lg-6">
                <div class="card sicta-card p-4 h-100">
                    <h2 class="h5 fw-bold mb-3" style="color: var(--sicta-dark);"><?= htmlspecialchars($groupe['categorie']) ?></h2>
                    <div style="height: 240px;">
                        <canvas id="adminChart<?= $idx ?>"></canvas>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>