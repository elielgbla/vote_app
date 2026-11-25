<?php
// views/admin/admins/index.php
require dirname(__DIR__, 2) . '/_context.php';
/** @var string $baseUrl */
/** @var string $csrfToken */
/** @var array $admins */
$pageTitle = 'SICTA — Gestion des Administrateurs';
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card sicta-card p-4">
            <h1 class="h5 fw-bold mb-3" style="color: var(--sicta-dark);">Créer / Modifier un Admin</h1>
            <form method="POST" action="<?= $baseUrl ?>/index.php?action=admin.admins.store">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id_admin" id="admId" value="">

                <div class="mb-2">
                    <label class="form-label small fw-bold">Identifiant (LOGIN) *</label>
                    <input type="text" name="login" id="admLogin" class="form-control" maxlength="50" required>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Nom &amp; Prénoms *</label>
                    <input type="text" name="nom_prenoms" id="admNom" class="form-control" maxlength="100" required>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Rôle *</label>
                    <select name="role" id="admRole" class="form-select" required>
                        <option value="super_admin">super_admin</option>
                        <option value="rh">rh</option>
                        <option value="caissier">caissier</option>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Téléphone</label>
                    <input type="text" name="tel" id="admTel" class="form-control" maxlength="20">
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Mot de passe <span id="mdpHint" class="text-muted">(requis)</span></label>
                    <input type="password" name="mot_de_passe" id="admMdp" class="form-control" minlength="6">
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="actif" id="admActif" value="1" checked>
                    <label class="form-check-label small fw-bold" for="admActif">Compte actif</label>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-sicta-primary">Enregistrer</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetAdminForm()">Nouveau compte</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card sicta-card p-4">
            <h2 class="h5 fw-bold mb-3" style="color: var(--sicta-dark);">Comptes d’administration (table ADMIN)</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Login</th><th>Nom &amp; Prénoms</th><th>Rôle</th><th>État</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $a): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($a['LOGIN']) ?></td>
                                <td>
                                    <?= htmlspecialchars($a['NOM_PRENOMS']) ?>
                                    <?php if (!empty($a['TEL'])): ?>
                                        <div class="small text-muted"><?= htmlspecialchars($a['TEL']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-dark"><?= htmlspecialchars($a['ROLE']) ?></span></td>
                                <td>
                                    <?php if ((int) $a['ACTIF'] === 1): ?>
                                        <span class="badge bg-success">Actif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-dark"
                                        onclick='editAdminForm(<?= json_encode($a, JSON_HEX_APOS | JSON_HEX_TAG) ?>)'>
                                        Modifier
                                    </button>
                                    <form method="POST" action="<?= $baseUrl ?>/index.php?action=admin.admins.toggle" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="id_admin" value="<?= (int) $a['ID_ADMIN'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning text-dark">Activer/Désactiver</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function editAdminForm(adm) {
    document.getElementById('admId').value = adm.ID_ADMIN || '';
    document.getElementById('admLogin').value = adm.LOGIN || '';
    document.getElementById('admNom').value = adm.NOM_PRENOMS || '';
    document.getElementById('admRole').value = adm.ROLE || 'rh';
    document.getElementById('admTel').value = adm.TEL || '';
    document.getElementById('admActif').checked = Number(adm.ACTIF) === 1;
    const mdp = document.getElementById('admMdp');
    mdp.required = false;
    mdp.value = '';
    document.getElementById('mdpHint').textContent = '(laisser vide pour conserver)';
}
function resetAdminForm() {
    document.getElementById('admId').value = '';
    document.getElementById('admLogin').value = '';
    document.getElementById('admNom').value = '';
    document.getElementById('admRole').value = 'super_admin';
    document.getElementById('admTel').value = '';
    document.getElementById('admActif').checked = true;
    const mdp = document.getElementById('admMdp');
    mdp.required = true;
    mdp.value = '';
    document.getElementById('mdpHint').textContent = '(requis)';
}
</script>