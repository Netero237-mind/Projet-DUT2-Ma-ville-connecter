<?php
/**
 * Paramètres du système — Admin
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Token invalide.'); redirect('/admin/parametres.php');
    }
    $fields = [
        'nom_mairie','ville','region','pays','adresse_mairie',
        'telephone_mairie','email_mairie','site_web',
        'delai_traitement_naissance','delai_traitement_deces','delai_traitement_mariage',
        'maintenance_mode',
    ];
    $stmt = db()->prepare("UPDATE parametres_systeme SET valeur=? WHERE cle=?");
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $stmt->execute([sanitize($_POST[$field]), $field]);
        }
    }
    logAction($user['id'], 'modif_parametres', 'Mise à jour des paramètres système', 'parametres_systeme', null);
    flash('success', '✅ Paramètres mis à jour avec succès !');
    redirect('/admin/parametres.php');
}

// Charger tous les paramètres
$paramsAll = dbQuery("SELECT cle, valeur FROM parametres_systeme")->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Paramètres système'; $pageSection = 'admin';
include __DIR__ . '/../views/partials/header.php';
include __DIR__ . '/../views/partials/navbar.php';
?>
<div class="dashboard-wrapper">
  <aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
      <div class="sidebar-user">
        <div class="user-avatar" style="background:rgba(255,215,0,.2);"><i class="fas fa-shield-alt" style="color:gold;"></i></div>
        <div class="user-info"><div class="user-name"><?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?></div><div class="user-role">Administrateur</div></div>
      </div>
    </div>
    <nav class="sidebar-menu">
      <ul class="nav flex-column mt-2">
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/dashboard.php"><span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>Tableau de bord</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/utilisateurs.php"><span class="nav-icon"><i class="fas fa-users"></i></span>Utilisateurs</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/demandes.php"><span class="nav-icon"><i class="fas fa-folder-open"></i></span>Demandes</a></li>
        <li class="nav-item"><a class="nav-link active" href="<?= APP_URL ?>/admin/parametres.php"><span class="nav-icon"><i class="fas fa-cog"></i></span>Paramètres</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/journal.php"><span class="nav-icon"><i class="fas fa-history"></i></span>Journal</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/auth/logout.php" style="color:rgba(255,100,100,.75)!important;"><span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>Déconnexion</a></li>
      </ul>
    </nav>
  </aside>

  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-cog me-2 text-primary"></i>Paramètres du système</h1>
        <div class="breadcrumb-bar"><a href="<?= APP_URL ?>/admin/dashboard.php">Admin</a> › <span>Paramètres</span></div>
      </div>
    </div>

    <form method="POST" data-validate>
      <?= csrfField() ?>

      <!-- Informations de la mairie -->
      <div class="form-card mb-4">
        <div class="form-card-header">
          <h4><i class="fas fa-landmark me-2 text-primary"></i>Informations de la mairie</h4>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nom officiel de la mairie <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nom_mairie" required
                   value="<?= htmlspecialchars($paramsAll['nom_mairie'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Ville</label>
            <input type="text" class="form-control" name="ville"
                   value="<?= htmlspecialchars($paramsAll['ville'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Région</label>
            <input type="text" class="form-control" name="region"
                   value="<?= htmlspecialchars($paramsAll['region'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Adresse postale</label>
            <input type="text" class="form-control" name="adresse_mairie"
                   value="<?= htmlspecialchars($paramsAll['adresse_mairie'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Pays</label>
            <input type="text" class="form-control" name="pays"
                   value="<?= htmlspecialchars($paramsAll['pays'] ?? 'Cameroun') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Site web</label>
            <input type="text" class="form-control" name="site_web"
                   value="<?= htmlspecialchars($paramsAll['site_web'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Téléphone</label>
            <input type="tel" class="form-control" name="telephone_mairie"
                   value="<?= htmlspecialchars($paramsAll['telephone_mairie'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email officiel</label>
            <input type="email" class="form-control" name="email_mairie"
                   value="<?= htmlspecialchars($paramsAll['email_mairie'] ?? '') ?>">
          </div>
        </div>
      </div>

      <!-- Délais de traitement -->
      <div class="form-card mb-4">
        <div class="form-card-header">
          <h4><i class="fas fa-clock me-2 text-primary"></i>Délais de traitement (jours ouvrés)</h4>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">
              <i class="fas fa-baby me-1 text-primary"></i>Acte de naissance
            </label>
            <div class="input-group">
              <input type="number" class="form-control" name="delai_traitement_naissance" min="1" max="30"
                     value="<?= htmlspecialchars($paramsAll['delai_traitement_naissance'] ?? '5') ?>">
              <span class="input-group-text">jours</span>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label">
              <i class="fas fa-cross me-1 text-danger"></i>Acte de décès
            </label>
            <div class="input-group">
              <input type="number" class="form-control" name="delai_traitement_deces" min="1" max="30"
                     value="<?= htmlspecialchars($paramsAll['delai_traitement_deces'] ?? '3') ?>">
              <span class="input-group-text">jours</span>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label">
              <i class="fas fa-heart me-1" style="color:#6a1b9a;"></i>Acte de mariage
            </label>
            <div class="input-group">
              <input type="number" class="form-control" name="delai_traitement_mariage" min="1" max="30"
                     value="<?= htmlspecialchars($paramsAll['delai_traitement_mariage'] ?? '7') ?>">
              <span class="input-group-text">jours</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Maintenance -->
      <div class="form-card mb-4">
        <div class="form-card-header">
          <h4><i class="fas fa-tools me-2 text-warning"></i>Mode maintenance</h4>
        </div>
        <div class="d-flex align-items-center gap-3 p-3 rounded" style="background:<?= ($paramsAll['maintenance_mode']??'0')==='1'?'#fff3e0':'#e8f5e9' ?>;">
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch" id="maintenance"
                   name="maintenance_mode" value="1"
                   <?= ($paramsAll['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>
                   style="width:3rem;height:1.5rem;">
            <label class="form-check-label ms-2 fw-bold" for="maintenance">
              <?= ($paramsAll['maintenance_mode'] ?? '0') === '1'
                  ? '<span class="text-warning">⚠️ Maintenance ACTIVE — Site inaccessible aux citoyens</span>'
                  : '<span class="text-success">✅ Système opérationnel</span>' ?>
            </label>
          </div>
        </div>
        <div class="alert alert-warning mt-3 d-flex gap-2" style="font-size:.85rem;">
          <i class="fas fa-exclamation-triangle mt-1"></i>
          <div>En mode maintenance, les citoyens et agents ne peuvent pas accéder à la plateforme. Seul l'administrateur conserve l'accès.</div>
        </div>
      </div>

      <!-- Bouton de sauvegarde -->
      <div class="d-flex justify-content-end gap-3">
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary">
          <i class="fas fa-times me-1"></i>Annuler
        </a>
        <button type="submit" class="btn btn-primary px-5">
          <i class="fas fa-save me-2"></i>Enregistrer les modifications
        </button>
      </div>
    </form>
  </main>
</div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
