<?php
/**
 * Tableau de bord citoyen — E-Gouvernance État Civil
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

requireRole('citoyen');
$user   = getCurrentUser();
$userId = $user['id'];

// Récupérer les statistiques du citoyen
$stats = [
    'total'     => dbQuery("SELECT COUNT(*) as c FROM demandes WHERE user_id=?", [$userId])->fetch()['c'],
    'soumis'    => dbQuery("SELECT COUNT(*) as c FROM demandes WHERE user_id=? AND statut='soumis'", [$userId])->fetch()['c'],
    'en_cours'  => dbQuery("SELECT COUNT(*) as c FROM demandes WHERE user_id=? AND statut='en_cours'", [$userId])->fetch()['c'],
    'valide'    => dbQuery("SELECT COUNT(*) as c FROM demandes WHERE user_id=? AND statut='valide'", [$userId])->fetch()['c'],
    'rejete'    => dbQuery("SELECT COUNT(*) as c FROM demandes WHERE user_id=? AND statut='rejete'", [$userId])->fetch()['c'],
];

// Dernières demandes
$recentDemandes = dbQuery("
    SELECT * FROM demandes
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
", [$userId])->fetchAll();

// Notifications non lues
$notifications = dbQuery("
    SELECT * FROM notifications
    WHERE user_id = ? AND lu = 0
    ORDER BY created_at DESC
    LIMIT 5
", [$userId])->fetchAll();

$pageTitle   = 'Mon tableau de bord';
$pageSection = 'citoyen';

include __DIR__ . '/../views/partials/header.php';
include __DIR__ . '/../views/partials/navbar.php';
?>

<div class="dashboard-wrapper">

  <!-- ============================================================
     SIDEBAR CITOYEN
  ============================================================ -->
  <aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
      <div class="sidebar-user">
        <div class="user-avatar">
          <?php if ($user['photo']): ?>
            <img src="<?= APP_URL . '/' . htmlspecialchars($user['photo']) ?>" alt="Avatar">
          <?php else: ?>
            <i class="fas fa-user"></i>
          <?php endif; ?>
        </div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
          <div class="user-role">Espace citoyen</div>
        </div>
      </div>
    </div>

    <nav class="sidebar-menu">
      <div class="menu-section">Principal</div>
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link active" href="<?= APP_URL ?>/citoyen/dashboard.php">
            <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
            Tableau de bord
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_URL ?>/citoyen/mes-demandes.php">
            <span class="nav-icon"><i class="fas fa-folder-open"></i></span>
            Mes demandes
            <?php if ($stats['soumis'] + $stats['en_cours'] > 0): ?>
              <span class="badge-count"><?= $stats['soumis'] + $stats['en_cours'] ?></span>
            <?php endif; ?>
          </a>
        </li>
      </ul>

      <div class="menu-section mt-3">Nouvelle demande</div>
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_URL ?>/citoyen/demande-naissance.php">
            <span class="nav-icon"><i class="fas fa-baby"></i></span>
            Acte de naissance
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_URL ?>/citoyen/demande-deces.php">
            <span class="nav-icon"><i class="fas fa-cross"></i></span>
            Acte de décès
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_URL ?>/citoyen/demande-mariage.php">
            <span class="nav-icon"><i class="fas fa-heart"></i></span>
            Acte de mariage
          </a>
        </li>
      </ul>

      <div class="menu-section mt-3">Mon compte</div>
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_URL ?>/citoyen/profil.php">
            <span class="nav-icon"><i class="fas fa-user-cog"></i></span>
            Mon profil
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_URL ?>/citoyen/notifications.php">
            <span class="nav-icon"><i class="fas fa-bell"></i></span>
            Notifications
            <?php if (count($notifications) > 0): ?>
              <span class="badge-count"><?= count($notifications) ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-danger-subtle" href="<?= APP_URL ?>/auth/logout.php">
            <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
            Déconnexion
          </a>
        </li>
      </ul>
    </nav>
  </aside>

  <!-- ============================================================
     CONTENU PRINCIPAL
  ============================================================ -->
  <main class="main-content">

    <!-- En-tête de page -->
    <div class="page-header">
      <div>
        <h1><i class="fas fa-tachometer-alt me-2 text-primary"></i>Tableau de bord</h1>
        <div class="breadcrumb-bar">
          Bonjour, <span><?= htmlspecialchars($user['prenom']) ?></span> !
          Bienvenue dans votre espace citoyen.
        </div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle">
          <i class="fas fa-bars"></i>
        </button>
        <a href="<?= APP_URL ?>/citoyen/demande-naissance.php" class="btn btn-primary btn-sm">
          <i class="fas fa-plus me-1"></i>Nouvelle demande
        </a>
      </div>
    </div>

    <!-- ---- CARTES STATISTIQUES ---- -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="stat-card primary">
          <div class="stat-icon"><i class="fas fa-folder"></i></div>
          <div>
            <div class="stat-value" data-counter="<?= $stats['total'] ?>"><?= $stats['total'] ?></div>
            <div class="stat-label">Total demandes</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card warning">
          <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
          <div>
            <div class="stat-value" data-counter="<?= $stats['soumis'] + $stats['en_cours'] ?>"><?= $stats['soumis'] + $stats['en_cours'] ?></div>
            <div class="stat-label">En attente</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card success">
          <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
          <div>
            <div class="stat-value" data-counter="<?= $stats['valide'] ?>"><?= $stats['valide'] ?></div>
            <div class="stat-label">Validés</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card danger">
          <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
          <div>
            <div class="stat-value" data-counter="<?= $stats['rejete'] ?>"><?= $stats['rejete'] ?></div>
            <div class="stat-label">Rejetés</div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">

      <!-- ---- DEMANDES RÉCENTES ---- -->
      <div class="col-lg-8">
        <div class="table-card">
          <div class="table-card-header">
            <h5><i class="fas fa-folder-open me-2 text-primary"></i>Mes demandes récentes</h5>
            <a href="<?= APP_URL ?>/citoyen/mes-demandes.php" class="btn btn-sm btn-outline-primary">
              Voir tout
            </a>
          </div>

          <?php if (empty($recentDemandes)): ?>
          <div class="text-center py-5" style="color:#adb5bd;">
            <i class="fas fa-folder-open fa-3x mb-3 d-block opacity-25"></i>
            <p>Aucune demande pour le moment.</p>
            <a href="<?= APP_URL ?>/citoyen/demande-naissance.php" class="btn btn-primary btn-sm">
              <i class="fas fa-plus me-1"></i>Faire ma première demande
            </a>
          </div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table-modern">
              <thead>
                <tr>
                  <th>Référence</th>
                  <th>Type d'acte</th>
                  <th>Date</th>
                  <th>Statut</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentDemandes as $d): ?>
                <tr>
                  <td>
                    <code style="font-size:.78rem;color:var(--bleu-primaire);">
                      <?= htmlspecialchars($d['numero_reference']) ?>
                    </code>
                  </td>
                  <td>
                    <i class="<?= getTypeActeIcon($d['type_acte']) ?> me-2 text-muted"></i>
                    <?= getTypeActeLabel($d['type_acte']) ?>
                  </td>
                  <td>
                    <span style="font-size:.82rem;color:#718096;">
                      <?= formatDate($d['created_at']) ?>
                    </span>
                  </td>
                  <td><?= getStatutBadge($d['statut']) ?></td>
                  <td>
                    <a href="<?= APP_URL ?>/citoyen/detail-demande.php?id=<?= $d['id'] ?>"
                       class="btn btn-icon btn-outline-primary btn-sm"
                       data-bs-toggle="tooltip" title="Voir le dossier">
                      <i class="fas fa-eye"></i>
                    </a>
                    <?php if ($d['statut'] === 'valide' && $d['acte_pdf_path']): ?>
                    <a href="<?= APP_URL ?>/citoyen/telecharger-acte.php?id=<?= $d['id'] ?>"
                       class="btn btn-icon btn-outline-success btn-sm"
                       data-bs-toggle="tooltip" title="Télécharger le PDF">
                      <i class="fas fa-file-pdf"></i>
                    </a>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ---- COLONNE DROITE ---- -->
      <div class="col-lg-4">

        <!-- Notifications -->
        <div class="content-card mb-3">
          <div class="card-header-title">
            <i class="fas fa-bell text-warning"></i>
            Notifications
            <?php if (count($notifications) > 0): ?>
              <span class="badge bg-danger ms-auto"><?= count($notifications) ?></span>
            <?php endif; ?>
          </div>

          <?php if (empty($notifications)): ?>
          <div class="text-center py-3" style="font-size:.85rem;color:#adb5bd;">
            <i class="fas fa-bell-slash fa-2x mb-2 d-block opacity-25"></i>
            Aucune nouvelle notification
          </div>
          <?php else: ?>
            <?php foreach ($notifications as $n): ?>
            <div class="notif-item unread rounded mb-1">
              <div class="d-flex gap-2 align-items-start">
                <div class="notif-icon bg-primary-subtle text-primary">
                  <i class="fas fa-info-circle"></i>
                </div>
                <div class="flex-1">
                  <div class="notif-title"><?= htmlspecialchars($n['titre']) ?></div>
                  <div class="notif-msg"><?= htmlspecialchars(substr($n['message'], 0, 80)) ?>…</div>
                  <div class="notif-time"><?= timeAgo($n['created_at']) ?></div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Raccourcis -->
        <div class="content-card">
          <div class="card-header-title">
            <i class="fas fa-bolt text-warning"></i>Accès rapide
          </div>
          <div class="d-grid gap-2">
            <a href="<?= APP_URL ?>/citoyen/demande-naissance.php"
               class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
              <i class="fas fa-baby"></i>Demander un acte de naissance
            </a>
            <a href="<?= APP_URL ?>/citoyen/demande-deces.php"
               class="btn btn-outline-danger btn-sm d-flex align-items-center gap-2">
              <i class="fas fa-cross"></i>Déclarer un décès
            </a>
            <a href="<?= APP_URL ?>/citoyen/demande-mariage.php"
               class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
              <i class="fas fa-heart"></i>Demande acte de mariage
            </a>
            <a href="<?= APP_URL ?>/citoyen/mes-demandes.php"
               class="btn btn-outline-success btn-sm d-flex align-items-center gap-2">
              <i class="fas fa-search"></i>Suivre mes dossiers
            </a>
          </div>
        </div>

      </div>
    </div>

  </main>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
