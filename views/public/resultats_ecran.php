<?php
// views/public/resultats_ecran.php

declare(strict_types=1);

/** @var string $baseUrl */
/** @var ?array $scrutin */
/** @var bool $peutAfficherResultats */
/** @var array $groupes */
/** @var array $kpis */

$baseUrl = $baseUrl ?? '';
$scrutin = $scrutin ?? null;
$peutAfficherResultats = (bool) ($peutAfficherResultats ?? false);
$groupes = $groupes ?? [];
$kpis = $kpis ?? [];

$pageTitle = 'SICTA — Résultats du Scrutin';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: var(--sicta-dark);">Résultats &amp; Participation</h1>
        <div class="text-muted small">
            <?= $scrutin
                ? htmlspecialchars((string) $scrutin['TITRE'] . ' — Statut : ' . strtoupper((string) $scrutin['STATUT']))
                : 'Aucun scrutin disponible' ?>
        </div>
    </div>
    <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=public.ecran" class="btn btn-outline-dark">
        Retour à l’accueil
    </a>
</div>

<?php if ($scrutin): ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card sicta-card p-3 h-100">
                <div class="text-muted small">Émargements</div>
                <div class="fs-4 fw-bold"><?= (int) ($kpis['emargements'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card sicta-card p-3 h-100">
                <div class="text-muted small">Taux de participation</div>
                <div class="fs-4 fw-bold" style="color: var(--sicta-orange);"><?= htmlspecialchars((string) ($kpis['taux_participation'] ?? 0)) ?> %</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card sicta-card p-3 h-100">
                <div class="text-muted small">Codes distribués</div>
                <div class="fs-4 fw-bold"><?= (int) ($kpis['codes_distribues'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card sicta-card p-3 h-100">
                <div class="text-muted small">Codes utilisés</div>
                <div class="fs-4 fw-bold"><?= (int) ($kpis['codes_utilises'] ?? 0) ?></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!$scrutin): ?>
    <div class="card sicta-card p-4 text-center">
        <p class="text-muted mb-0">Aucun scrutin n’est enregistré pour le moment.</p>
    </div>
<?php elseif (!$peutAfficherResultats): ?>
    <div class="card sicta-card p-4 text-center">
        <h2 class="h5 fw-bold mb-2" style="color: var(--sicta-dark);">Résultats non encore disponibles</h2>
        <p class="text-muted mb-0">
            Le scrutin est actuellement au statut <strong><?= htmlspecialchars(strtoupper((string) $scrutin['STATUT'])) ?></strong>. Les résultats seront affichés après clôture ou publication.
        </p>
    </div>
<?php elseif (empty($groupes)): ?>
    <div class="card sicta-card p-4 text-center">
        <p class="text-muted mb-0">Aucun résultat comptabilisé pour ce scrutin.</p>
    </div>
<?php else: ?>
    <div class="row g-4" id="resultatsChartContainer"
         data-resultats='<?= htmlspecialchars((string) json_encode($groupes, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'>
        <?php foreach ($groupes as $idx => $groupe): ?>
            <div class="col-lg-6">
                <div class="card sicta-card p-4 h-100">
                    <h2 class="h5 fw-bold mb-3" style="color: var(--sicta-dark);">
                        <?= htmlspecialchars((string) ($groupe['categorie'] ?? 'Catégorie')) ?>
                    </h2>
                    <div class="mb-3" style="height: 250px;">
                        <canvas id="chartCat<?= (int) $idx ?>"></canvas>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Candidat</th>
                                    <th class="text-end">Voix</th>
                                    <th class="text-end">%</th>
                                    <th class="text-end">Rang</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($groupe['candidats'] ?? []) as $c): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($c['CANDIDAT'] ?? '')) ?></td>
                                        <td class="text-end fw-bold"><?= (int) ($c['NB_VOIX'] ?? 0) ?></td>
                                        <td class="text-end"><?= htmlspecialchars((string) ($c['POURCENTAGE'] ?? 0)) ?> %</td>
                                        <td class="text-end">
                                            <span class="badge bg-dark">
                                                <?= (int) (!empty($c['RANG_FIGE']) ? $c['RANG_FIGE'] : ($c['RANG_CALCULE'] ?? 0)) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>