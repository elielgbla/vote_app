<?php
// views/public/voter.php

declare(strict_types=1);

/** @var string $baseUrl */
/** @var string $csrfToken */
/** @var ?array $scrutin */
/** @var ?array $electeur */
/** @var array $categories */

$baseUrl = $baseUrl ?? '';
$csrfToken = $csrfToken ?? '';
$scrutin = $scrutin ?? null;
$electeur = $electeur ?? null;
$categories = $categories ?? [];

$pageTitle = 'SICTA — Formulaire de Vote';
?>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card sicta-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1" style="color: var(--sicta-dark);">Bulletin de Vote Électronique</h1>
                    <div class="text-muted small">
                        <?= $scrutin ? htmlspecialchars((string) $scrutin['TITRE'] . ' (' . (string) $scrutin['ANNEE'] . ')') : 'Aucun scrutin actif' ?>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($electeur): ?>
                        <span class="badge bg-dark py-2 px-3">
                            Électeur : <?= htmlspecialchars((string) ($electeur['nom_prenoms'] ?? '')) ?> (<?= htmlspecialchars((string) ($electeur['cni'] ?? '')) ?>)
                        </span>
                        <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=electeur.logout" class="btn btn-sm btn-outline-secondary">
                            Verrouiller
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$scrutin): ?>
                <div class="alert alert-warning mb-0">
                    Aucun scrutin n’est actuellement ouvert au vote. Revenez à l’<a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=public.ecran">accueil</a>.
                </div>
            <?php elseif (empty($categories)): ?>
                <div class="alert alert-info mb-0">
                    Aucune catégorie n’est configurée pour ce scrutin.
                </div>
            <?php else: ?>
                <form method="POST" action="<?= htmlspecialchars($baseUrl) ?>/index.php?action=public.voter.submit" id="voteForm">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="row g-3 mb-4 p-3 rounded-3 bg-light border">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">CNI vérifiée</label>
                            <input type="text" class="form-control form-control-lg bg-white text-uppercase"
                                   value="<?= htmlspecialchars((string) ($electeur['cni'] ?? '')) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="code_vote" class="form-label fw-bold">Code de vote à usage unique *</label>
                            <input type="text" name="code_vote" id="code_vote"
                                   class="form-control form-control-lg text-uppercase"
                                   maxlength="32" required autofocus placeholder="Ex: 8A4F9C2E">
                        </div>
                    </div>

                    <?php foreach ($categories as $cat): ?>
                        <?php $idCat = (int) ($cat['ID_CATEGORIE'] ?? 0); ?>
                        <div class="mb-4">
                            <h2 class="h5 fw-bold mb-3" style="color: var(--sicta-dark);">
                                <?= htmlspecialchars((string) ($cat['LIB'] ?? '')) ?> *
                            </h2>
                            <?php if (empty($cat['candidats'])): ?>
                                <div class="alert alert-light border small text-muted">Aucun candidat dans cette catégorie.</div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($cat['candidats'] as $cand): ?>
                                        <?php
                                        $idCand = (int) ($cand['ID_CANDIDATURE'] ?? 0);
                                        $photoUrl = !empty($cand['PHOTO'])
                                            ? $baseUrl . '/index.php?action=public.photo&f=' . urlencode((string) $cand['PHOTO'])
                                            : $baseUrl . '/assets/img/default-avatar.svg';
                                        $inputId = 'cand_' . $idCat . '_' . $idCand;
                                        ?>
                                        <div class="col-md-6">
                                            <label class="card p-3 h-100 border candidate-select-card" for="<?= htmlspecialchars($inputId) ?>" style="cursor:pointer;">
                                                <div class="d-flex align-items-center gap-3">
                                                    <input class="form-check-input mt-0" type="radio"
                                                           name="choix[<?= $idCat ?>]"
                                                           id="<?= htmlspecialchars($inputId) ?>"
                                                           value="<?= $idCand ?>" required>
                                                    <img src="<?= htmlspecialchars($photoUrl) ?>"
                                                         alt="<?= htmlspecialchars((string) ($cand['NOM_PRENOMS'] ?? '')) ?>"
                                                         class="candidate-avatar flex-shrink-0">
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars((string) ($cand['NOM_PRENOMS'] ?? '')) ?></div>
                                                        <div class="small text-muted"><?= htmlspecialchars((string) ($cand['SERVICE'] ?? '')) ?></div>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="d-grid d-md-flex justify-content-md-end gap-2 pt-3 border-top">
                        <a href="<?= htmlspecialchars($baseUrl) ?>/index.php?action=public.ecran" class="btn btn-outline-secondary btn-lg">
                            Annuler
                        </a>
                        <button type="submit" class="btn btn-sicta-primary btn-lg px-5" id="btnSubmitVote">
                            Valider mon vote définitivement
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>