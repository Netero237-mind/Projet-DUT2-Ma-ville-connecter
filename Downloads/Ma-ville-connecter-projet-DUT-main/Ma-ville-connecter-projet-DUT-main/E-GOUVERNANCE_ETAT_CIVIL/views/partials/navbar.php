<?php
/**
 * Navbar publique — E-Gouvernance État Civil
 * Inclus sur toutes les pages publiques
 */

$user    = getCurrentUser();
$mairie  = getSystemParam('nom_mairie', 'Mairie');
$notifs  = ($user) ? countUnreadNotifications($user['id']) : 0;
?>
<nav class="navbar navbar-expand-lg navbar-etat-civil">
  <div class="container">

    <!-- BRAND -->
    <a class="navbar-brand" href="<?= APP_URL ?>/index.php">
      <div class="logo-circle"><i class="fas fa-landmark"></i></div>
      <div class="brand-text">
        <span class="brand-title"><?= htmlspecialchars($mairie) ?></span>
        <span class="brand-sub">E-État Civil</span>
      </div>
    </a>

    <!-- TOGGLE MOBILE -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#mainNav" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- MENU -->
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav mx-auto gap-1">
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_URL ?>/index.php">
            <i class="fas fa-home me-1"></i>Accueil
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_URL ?>/index.php#services">
            <i class="fas fa-concierge-bell me-1"></i>Services
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_URL ?>/index.php#actualites">
            <i class="fas fa-newspaper me-1"></i>Actualités
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_URL ?>/index.php#contact">
            <i class="fas fa-phone me-1"></i>Contact
          </a>
        </li>
      </ul>

      <!-- BOUTONS D'ACTION -->
      <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
        <?php if ($user): ?>
          <!-- Utilisateur connecté -->
          <div class="dropdown">
            <button class="btn btn-connexion dropdown-toggle" type="button"
                    data-bs-toggle="dropdown">
              <i class="fas fa-bell me-1"></i>
              <?php if ($notifs > 0): ?>
                <span class="badge bg-danger rounded-pill"><?= $notifs ?></span>
              <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end notif-dropdown p-0">
              <li class="p-3 border-bottom">
                <strong style="font-size:.88rem">Notifications</strong>
                <?php if ($notifs > 0): ?>
                  <span class="badge bg-danger ms-2"><?= $notifs ?> nouvelles</span>
                <?php endif; ?>
              </li>
              <?php
                $notifList = dbQuery(
                  "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
                  [$user['id']]
                )->fetchAll();
                if ($notifList):
                  foreach ($notifList as $n):
                    $typeIcons = ['info'=>'fa-info-circle text-info','succes'=>'fa-check-circle text-success','avertissement'=>'fa-exclamation-triangle text-warning','erreur'=>'fa-times-circle text-danger'];
                    $icon = $typeIcons[$n['type']] ?? 'fa-bell text-secondary';
              ?>
              <li>
                <a href="<?= APP_URL ?>/api/notifications.php?action=read&id=<?= $n['id'] ?>&redirect=<?= urlencode($n['lien'] ?? '') ?>"
                   class="notif-item d-flex gap-2 text-decoration-none <?= $n['lu'] ? '' : 'unread' ?>">
                  <div class="notif-icon bg-light">
                    <i class="fas <?= $icon ?>"></i>
                  </div>
                  <div class="flex-1">
                    <div class="notif-title"><?= htmlspecialchars($n['titre']) ?></div>
                    <div class="notif-msg"><?= htmlspecialchars(substr($n['message'], 0, 80)) ?>…</div>
                    <div class="notif-time"><?= timeAgo($n['created_at']) ?></div>
                  </div>
                </a>
              </li>
              <?php endforeach; else: ?>
              <li class="p-4 text-center text-muted" style="font-size:.85rem">
                <i class="fas fa-bell-slash fa-2x mb-2 d-block opacity-25"></i>
                Aucune notification
              </li>
              <?php endif; ?>
              <li class="p-2 border-top text-center">
                <a href="<?= APP_URL ?>/<?= $user['role'] ?>/notifications.php"
                   class="btn btn-sm btn-outline-primary w-100" style="font-size:.8rem">
                  Voir toutes les notifications
                </a>
              </li>
            </ul>
          </div>

          <div class="dropdown">
            <button class="btn btn-connexion dropdown-toggle" type="button"
                    data-bs-toggle="dropdown">
              <i class="fas fa-user-circle me-1"></i>
              <?= htmlspecialchars($user['prenom']) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <span class="dropdown-item-text" style="font-size:.78rem;color:#6c757d">
                  <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?><br>
                  <strong><?= ucfirst($user['role']) ?></strong>
                </span>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item" href="<?= APP_URL ?>/<?= $user['role'] ?>/dashboard.php">
                  <i class="fas fa-tachometer-alt me-2 text-primary"></i>Mon tableau de bord
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="<?= APP_URL ?>/<?= $user['role'] ?>/profil.php">
                  <i class="fas fa-user-cog me-2 text-secondary"></i>Mon profil
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="<?= APP_URL ?>/auth/logout.php">
                  <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                </a>
              </li>
            </ul>
          </div>

        <?php else: ?>
          <!-- Utilisateur non connecté -->
          <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-connexion">
            <i class="fas fa-sign-in-alt me-1"></i>Connexion
          </a>
          <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-inscription">
            <i class="fas fa-user-plus me-1"></i>Inscription
          </a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</nav>
