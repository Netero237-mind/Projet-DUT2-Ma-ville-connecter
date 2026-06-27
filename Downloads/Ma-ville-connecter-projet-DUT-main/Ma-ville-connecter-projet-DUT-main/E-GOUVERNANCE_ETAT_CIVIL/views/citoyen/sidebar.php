<?php
/**
 * Sidebar citoyen — réutilisable sur toutes les pages de l'espace citoyen
 */
$user    = getCurrentUser();
$notifs  = countUnreadNotifications($user['id']);
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="mainSidebar">
  <div class="sidebar-header">
    <div class="sidebar-user">
      <div class="user-avatar">
        <?php if ($user['photo']): ?>
          <img src="<?= APP_URL . '/' . htmlspecialchars($user['photo']) ?>" alt="Photo">
        <?php else: ?>
          <i class="fas fa-user"></i>
        <?php endif; ?>
      </div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
        <div class="user-role"><i class="fas fa-circle text-success" style="font-size:.5rem;"></i> Citoyen</div>
      </div>
    </div>
  </div>

  <nav class="sidebar-menu">
    <div class="menu-section">Principal</div>
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link <?= $current==='dashboard.php'?'active':'' ?>"
           href="<?= APP_URL ?>/citoyen/dashboard.php">
          <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>Tableau de bord
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $current==='mes-demandes.php'?'active':'' ?>"
           href="<?= APP_URL ?>/citoyen/mes-demandes.php">
          <span class="nav-icon"><i class="fas fa-folder-open"></i></span>Mes demandes
        </a>
      </li>
    </ul>

    <div class="menu-section mt-3">Nouvelle demande</div>
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link <?= $current==='demande-naissance.php'?'active':'' ?>"
           href="<?= APP_URL ?>/citoyen/demande-naissance.php">
          <span class="nav-icon"><i class="fas fa-baby"></i></span>Acte de naissance
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $current==='demande-deces.php'?'active':'' ?>"
           href="<?= APP_URL ?>/citoyen/demande-deces.php">
          <span class="nav-icon"><i class="fas fa-cross"></i></span>Acte de décès
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $current==='demande-mariage.php'?'active':'' ?>"
           href="<?= APP_URL ?>/citoyen/demande-mariage.php">
          <span class="nav-icon"><i class="fas fa-heart"></i></span>Acte de mariage
        </a>
      </li>
    </ul>

    <div class="menu-section mt-3">Mon compte</div>
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link <?= $current==='profil.php'?'active':'' ?>"
           href="<?= APP_URL ?>/citoyen/profil.php">
          <span class="nav-icon"><i class="fas fa-user-cog"></i></span>Mon profil
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $current==='notifications.php'?'active':'' ?>"
           href="<?= APP_URL ?>/citoyen/notifications.php">
          <span class="nav-icon"><i class="fas fa-bell"></i></span>Notifications
          <?php if ($notifs > 0): ?>
            <span class="badge-count"><?= $notifs ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="<?= APP_URL ?>/auth/logout.php"
           style="color:rgba(255,100,100,.75)!important;"
           onclick="return confirm('Voulez-vous vous déconnecter ?')">
          <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>Déconnexion
        </a>
      </li>
    </ul>
  </nav>
</aside>
