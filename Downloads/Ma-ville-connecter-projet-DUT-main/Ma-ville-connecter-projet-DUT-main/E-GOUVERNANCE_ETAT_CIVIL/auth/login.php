<?php
/**
 * Page de connexion — E-Gouvernance État Civil
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

// Si déjà connecté, rediriger
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'citoyen';
    redirect("/$role/dashboard.php");
}

$pageTitle = 'Connexion';
$msg = $_GET['msg'] ?? '';

include __DIR__ . '/../views/partials/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card">

    <!-- Logo -->
    <div class="auth-logo">
      <div class="logo-icon"><i class="fas fa-landmark"></i></div>
      <h4><?= htmlspecialchars(getSystemParam('nom_mairie', 'E-État Civil')) ?></h4>
      <p>Plateforme numérique d'État Civil</p>
    </div>

    <!-- Messages -->
    <?php if ($msg === 'registered'): ?>
    <div class="alert alert-success alert-dismissible d-flex gap-2 align-items-center mb-3" role="alert" style="font-size:.85rem;">
      <i class="fas fa-check-circle"></i>
      <div>Compte créé avec succès ! Connectez-vous ci-dessous.</div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php elseif ($msg === 'session_expired'): ?>
    <div class="alert alert-warning alert-dismissible d-flex gap-2 align-items-center mb-3" role="alert" style="font-size:.85rem;">
      <i class="fas fa-exclamation-triangle"></i>
      <div>Votre session a expiré. Veuillez vous reconnecter.</div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php elseif ($msg === 'logged_out'): ?>
    <div class="alert alert-info alert-dismissible d-flex gap-2 align-items-center mb-3" role="alert" style="font-size:.85rem;">
      <i class="fas fa-info-circle"></i>
      <div>Vous avez été déconnecté avec succès.</div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Onglets Connexion / Inscription -->
    <ul class="nav auth-tabs border-bottom mb-4" role="tablist">
      <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#loginTab">
          <i class="fas fa-sign-in-alt me-1"></i>Connexion
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#registerTab">
          <i class="fas fa-user-plus me-1"></i>Inscription
        </button>
      </li>
    </ul>

    <div class="tab-content">

      <!-- ---- FORMULAIRE CONNEXION ---- -->
      <div class="tab-pane fade show active" id="loginTab">
        <form action="<?= APP_URL ?>/controllers/AuthController.php"
              method="POST" data-validate id="loginForm">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="login">

          <div class="mb-3">
            <label class="form-label">Adresse email</label>
            <div class="input-group">
              <span class="input-group-text" style="background:var(--gris-clair);border-color:var(--gris-moyen);">
                <i class="fas fa-envelope text-muted"></i>
              </span>
              <input type="email" class="form-control" name="email" required
                     placeholder="votre@email.cm"
                     value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label d-flex justify-content-between">
              Mot de passe
              <a href="<?= APP_URL ?>/auth/forgot-password.php" style="font-size:.8rem;">
                Mot de passe oublié ?
              </a>
            </label>
            <div class="input-group">
              <span class="input-group-text" style="background:var(--gris-clair);border-color:var(--gris-moyen);">
                <i class="fas fa-lock text-muted"></i>
              </span>
              <input type="password" class="form-control" id="loginPassword"
                     name="password" required placeholder="••••••••">
              <button type="button" class="input-group-text" id="togglePwd"
                      style="background:var(--gris-clair);border-color:var(--gris-moyen);cursor:pointer;">
                <i class="fas fa-eye text-muted" id="eyeIcon"></i>
              </button>
            </div>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
            <label class="form-check-label" for="rememberMe" style="font-size:.85rem;">
              Se souvenir de moi
            </label>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="fas fa-sign-in-alt me-2"></i>Se connecter
          </button>


        </form>
      </div>

      <!-- ---- FORMULAIRE INSCRIPTION ---- -->
      <div class="tab-pane fade" id="registerTab">
        <form action="<?= APP_URL ?>/controllers/AuthController.php"
              method="POST" data-validate id="registerForm">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="register">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nom <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="nom" required
                     placeholder="NOM" style="text-transform:uppercase;">
            </div>
            <div class="col-md-6">
              <label class="form-label">Prénom(s) <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="prenom" required
                     placeholder="Prénom(s)">
            </div>
            <div class="col-12">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text" style="background:var(--gris-clair);border-color:var(--gris-moyen);">
                  <i class="fas fa-envelope text-muted"></i>
                </span>
                <input type="email" class="form-control" name="email" required
                       placeholder="votre@email.cm">
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Téléphone <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text" style="background:var(--gris-clair);border-color:var(--gris-moyen);">
                  <i class="fas fa-phone text-muted"></i>
                </span>
                <input type="tel" class="form-control" name="telephone" required
                       placeholder="+237 6XX XXX XXX">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
              <input type="password" class="form-control" name="password"
                     id="password" required minlength="8"
                     placeholder="Min. 8 caractères">
              <div class="progress mt-1" style="height:4px;">
                <div class="progress-bar" id="passwordStrength" style="width:0%;transition:.3s;"
                     role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
              <small id="strengthLabel" class="text-muted" style="font-size:.72rem;"></small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Confirmer <span class="text-danger">*</span></label>
              <input type="password" class="form-control" name="password_confirm"
                     id="password_confirm" required placeholder="Répéter le mot de passe">
            </div>
            <div class="col-12">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="acceptTerms" required>
                <label class="form-check-label" for="acceptTerms" style="font-size:.82rem;">
                  J'accepte les <a href="#">conditions d'utilisation</a> et la
                  <a href="#">politique de confidentialité</a>
                </label>
              </div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-success w-100 py-2">
                <i class="fas fa-user-plus me-2"></i>Créer mon compte
              </button>
            </div>
          </div>
        </form>
      </div>

    </div>

    <!-- Retour accueil -->
    <div class="text-center mt-4">
      <a href="<?= APP_URL ?>/index.php" style="font-size:.82rem;color:var(--gris-doux);">
        <i class="fas fa-arrow-left me-1"></i>Retour à l'accueil
      </a>
    </div>
  </div>
</div>

<script>
// Toggle affichage mot de passe
document.getElementById('togglePwd')?.addEventListener('click', function() {
  const pwd  = document.getElementById('loginPassword');
  const icon = document.getElementById('eyeIcon');
  if (pwd.type === 'password') {
    pwd.type  = 'text';
    icon.className = 'fas fa-eye-slash text-muted';
  } else {
    pwd.type  = 'password';
    icon.className = 'fas fa-eye text-muted';
  }
});

// Activer l'onglet inscription si paramètre présent
<?php if (isset($_GET['tab']) && $_GET['tab'] === 'register'): ?>
document.querySelector('[data-bs-target="#registerTab"]')?.click();
<?php endif; ?>
</script>

<?php
// Inclure seulement le footer JS, pas le footer complet
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
