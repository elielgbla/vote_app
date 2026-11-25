<?php
// views/public/accueil_ecran.php
/** @var string $baseUrl */
/** @var ?array $scrutin */
/** @var array $categories */
/** @var array $kpis */
declare(strict_types=1);

require dirname(__DIR__) . '/_context.php';

$pageTitle = 'SICTA — Tableau de Bord Public du Scrutin';
$statutScrutin = strtoupper((string) ($scrutin['STATUT'] ?? 'EN ATTENTE'));
$estActif = (($scrutin['STATUT'] ?? '') === 'actif');
?>

<!-- 1. SLIDES DU DASHBOARD PUBLIC -->
<section aria-label="Présentation du scrutin" class="mb-4">
    <div id="publicCarousel" class="carousel slide shadow-sm rounded-4 overflow-hidden" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#publicCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#publicCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#publicCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>

        <div class="carousel-inner">
            <!-- Slide 1 : Scrutin en cours / état -->
            <div class="carousel-item active">
                <div class="sicta-slide d-flex flex-column justify-content-center">
                    <div class="row align-items-center gy-3">
                        <div class="col-lg-8">
                            <span class="badge bg-warning text-dark mb-2">SCRUTIN SICTA</span>
                            <h1 class="display-6 fw-bold mb-2">
                                <?= htmlspecialchars((string) ($scrutin['TITRE'] ?? 'Plateforme de Vote Électronique SICTA')) ?>
                            </h1>
                            <p class="lead mb-3">
                                <?= htmlspecialchars((string) ($scrutin['DESCRIPTION'] ?? 'Participez en toute transparence et confidentialité aux scrutins de la SICTA.')) ?>
                            </p>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if ($estActif): ?>
                                    <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=electeur.auth" class="btn btn-sicta-primary btn-lg">
                                        Voter maintenant
                                    </a>
                                <?php endif; ?>
                                <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=public.resultats" class="btn btn-outline-light btn-lg">
                                    Voir les résultats
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <div class="bg-dark bg-opacity-50 p-3 rounded-3 border border-light border-opacity-25">
                                <div class="small text-uppercase">État du scrutin</div>
                                <div class="fs-4 fw-bold" style="color: var(--sicta-orange);">
                                    <?= htmlspecialchars($statutScrutin) ?>
                                </div>
                                <div class="small mt-1">
                                    Année : <?= htmlspecialchars((string) ($scrutin['ANNEE'] ?? date('Y'))) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 2 : Authentification à chaque vote & anonymat -->
            <div class="carousel-item">
                <div class="sicta-slide d-flex flex-column justify-content-center">
                    <span class="badge bg-warning text-dark mb-2">AUTHENTIFICATION &amp; ANONYMAT</span>
                    <h2 class="h1 fw-bold mb-2">Identité vérifiée à chaque vote, bulletin anonyme</h2>
                    <p class="lead mb-0">
                        Chaque session de vote exige une authentification préalable. L’émargement et les bulletins de vote restent strictement séparés afin de garantir l’anonymat des choix.
                    </p>
                </div>
            </div>

            <!-- Slide 3 : Anti-doublon & transparence -->
            <div class="carousel-item">
                <div class="sicta-slide d-flex flex-column justify-content-center">
                    <span class="badge bg-warning text-dark mb-2">ANTI-DOUBLON &amp; RÉSULTATS</span>
                    <h2 class="h1 fw-bold mb-2">Contrôle anti-doublon et classement officiel</h2>
                    <p class="lead mb-0">
                        Un seul vote est accepté par électeur et par scrutin. Les résultats et graphiques sont mis à disposition selon l’état du scrutin.
                    </p>
                </div>
            </div>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#publicCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Précédent</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#publicCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Suivant</span>
        </button>
    </div>
</section>

<!-- 2. INDICATEURS CLÉS (KPIs) -->
<?php if ($scrutin): ?>
<section aria-label="Indicateurs de participation" class="mb-4">
    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <div class="card sicta-card p-3 h-100">
                <div class="text-muted small">Émargements enregistrés</div>
                <div class="fs-3 fw-bold" style="color: var(--sicta-dark);"><?= (int) ($kpis['emargements'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card sicta-card p-3 h-100">
                <div class="text-muted small">Taux de participation</div>
                <div class="fs-3 fw-bold" style="color: var(--sicta-orange);"><?= htmlspecialchars((string) ($kpis['taux_participation'] ?? 0)) ?> %</div>
                <div class="small text-muted">sur <?= (int) ($kpis['total_electeurs'] ?? 0) ?> électeurs inscrits</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card sicta-card p-3 h-100">
                <div class="text-muted small">Codes distribués</div>
                <div class="fs-3 fw-bold" style="color: var(--sicta-dark);"><?= (int) ($kpis['codes_distribues'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card sicta-card p-3 h-100">
                <div class="text-muted small">Codes utilisés</div>
                <div class="fs-3 fw-bold" style="color: var(--sicta-dark);"><?= (int) ($kpis['codes_utilises'] ?? 0) ?></div>
                <div class="small text-muted">Bulletins enregistrés : <?= (int) ($kpis['bulletins'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</section>
<?php else: ?>
    <div class="card sicta-card p-4 text-center mb-4">
        <p class="text-muted mb-0">Aucun indicateur disponible : aucun scrutin n’est configuré.</p>
    </div>
<?php endif; ?>

<!-- 3. CATÉGORIES & CANDIDATS EN LICE (SCRUTIN ACTIF UNIQUEMENT) -->
<?php
$scrutinCourant = $scrutin ?? $scrutinActif ?? $scrutinPublic ?? null;
$idScrutinActif = is_array($scrutinCourant) ? (int) ($scrutinCourant['ID_SCRUTIN'] ?? 0) : 0;
$estScrutinActif = is_array($scrutinCourant)
    && (
        strtolower(trim((string) ($scrutinCourant['STATUT'] ?? ''))) === 'actif'
        || (int) ($scrutinCourant['EST_ACTIF'] ?? 0) === 1
    );

$categoriesActives = [];
if ($estScrutinActif && $idScrutinActif > 0 && !empty($categories)) {
    foreach ($categories as $catItem) {
        if (!isset($catItem['ID_SCRUTIN']) || (int) $catItem['ID_SCRUTIN'] === $idScrutinActif) {
            $categoriesActives[] = $catItem;
        }
    }
}
?>

<?php if ($estScrutinActif && !empty($categoriesActives)): ?>
    <section aria-label="Catégories et candidats en lice" class="mb-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h4 fw-bold mb-0" style="color: var(--sicta-dark);">
                Catégories &amp; Candidats en lice
            </h2>
            <span class="badge text-bg-success">Scrutin en cours</span>
        </div>

        <?php foreach ($categoriesActives as $cat): ?>
            <div class="card sicta-card mb-3">
                <div class="card-header bg-white border-bottom py-3">
                    <h3 class="h5 mb-0 fw-bold" style="color: var(--sicta-dark);">
                        <?= htmlspecialchars((string) ($cat['LIB'] ?? '')) ?>
                    </h3>
                    <?php if (!empty($cat['DESCRIPTION'])): ?>
                        <div class="text-muted small mt-1">
                            <?= htmlspecialchars((string) $cat['DESCRIPTION']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($cat['candidats'])): ?>
                        <p class="text-muted mb-0">Aucun candidat inscrit dans cette catégorie.</p>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($cat['candidats'] as $cand): ?>
                                <?php
                                $photoUrl = !empty($cand['PHOTO'])
                                    ? $baseUrl . '/index.php?action=public.photo&f=' . urlencode((string) $cand['PHOTO'])
                                    : $baseUrl . '/assets/img/default-avatar.svg';
                                ?>
                                <div class="col-12 col-sm-6 col-xl-4">
                                    <div class="border rounded-3 p-3 d-flex align-items-center gap-3 h-100 bg-light bg-opacity-50">
                                        <img src="<?= htmlspecialchars($photoUrl) ?>"
                                             alt="<?= htmlspecialchars((string) ($cand['NOM_PRENOMS'] ?? '')) ?>"
                                             class="candidate-avatar flex-shrink-0">
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars((string) ($cand['NOM_PRENOMS'] ?? '')) ?></div>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars((string) ($cand['SERVICE'] ?? 'Service non précisé')) ?>
                                            </div>
                                            <?php if (!empty($cand['SITE_LIB'])): ?>
                                                <span class="badge bg-secondary mt-1">
                                                    <?= htmlspecialchars((string) $cand['SITE_LIB']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<!-- 4. COMMENTAIRES PUBLICS & 5. À PROPOS / AIDE / CONTACT -->
<section id="apropos-aide-contact" aria-label="Commentaires, à propos, aide et contact">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card sicta-card p-4 h-100">
                <h2 class="h5 fw-bold mb-3" style="color: var(--sicta-dark);">Commentaires &amp; Observations</h2>
                <form id="commentForm" class="row g-2 mb-3">
                    <div class="col-sm-4">
                        <input type="text" id="commentAuthor" class="form-control" placeholder="Votre nom ou service" maxlength="60" required>
                    </div>
                    <div class="col-sm-8">
                        <div class="input-group">
                            <input type="text" id="commentText" class="form-control" placeholder="Ajouter un commentaire..." maxlength="240" required>
                            <button type="submit" class="btn btn-sicta-primary">Publier</button>
                        </div>
                    </div>
                </form>
                <div id="commentList" class="vstack gap-2"></div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card sicta-card p-4 h-100">
                <h2 class="h5 fw-bold mb-2" style="color: var(--sicta-dark);">À propos / Aide / Contact</h2>
                <p class="small text-muted">
                    Plateforme officielle de scrutin SICTA. Pour voter, chaque électeur doit d’abord s’authentifier puis saisir son code de vote à usage unique.
                </p>
                <ul class="list-unstyled small mb-3 vstack gap-1">
                    <li><strong>Aide :</strong> En cas de code non reçu ou d’erreur d’authentification, adressez-vous au point de distribution ou au responsable du scrutin.</li>
                    <li><strong>Contact :</strong> SICTA — Direction &amp; Organisation des Scrutins, Abidjan, Côte d’Ivoire.</li>
                </ul>
                <div class="d-flex flex-wrap gap-2 mt-auto">
                    <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=electeur.auth" class="btn btn-sicta-primary btn-sm">
                        S’authentifier pour voter
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=public.resultats" class="btn btn-outline-dark btn-sm">
                        Consulter les résultats
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>