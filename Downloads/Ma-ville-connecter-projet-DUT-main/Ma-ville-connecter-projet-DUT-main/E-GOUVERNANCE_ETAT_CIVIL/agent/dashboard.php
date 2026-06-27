<?php
/**
 * Tableau de bord Agent Municipal — E-Gouvernance État Civil
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
requireRole(['agent','admin']);
$user   = getCurrentUser();

// Statistiques globales
$stats = [
    'total'    => dbQuery("SELECT COUNT(*) as c FROM demandes")->fetch()['c'],
    'soumis'   => dbQuery("SELECT COUNT(*) as c FROM demandes WHERE statut='soumis'")->fetch()['c'],
    'en_cours' => dbQuery("SELECT COUNT(*) as c FROM demandes WHERE statut='en_cours'")->fetch()['c'],
    'valide'   => dbQuery("SELECT COUNT(*) as c FROM demandes WHERE statut='valide'")->fetch()['c'],
    'rejete'   => dbQuery("SELECT COUNT(*) as c FROM demandes WHERE statut='rejete'")->fetch()['c'],
    'mes_dem'  => dbQuery("SELECT COUNT(*) as c FROM demandes WHERE agent_id=?",[$user['id']])->fetch()['c'],
];

// Demandes récentes toutes
$recentes = dbQuery("
    SELECT d.*, u.nom as citoyen_nom, u.prenom as citoyen_prenom
    FROM demandes d
    JOIN users u ON d.user_id=u.id
    ORDER BY d.created_at DESC LIMIT 8
")->fetchAll();

// Mes demandes en cours
$mesDemandes = dbQuery("
    SELECT d.*, u.nom as citoyen_nom, u.prenom as citoyen_prenom
    FROM demandes d
    JOIN users u ON d.user_id=u.id
    WHERE d.agent_id=? AND d.statut='en_cours'
    ORDER BY d.created_at ASC LIMIT 5
",[$user['id']])->fetchAll();

$pageTitle='Tableau de bord Agent'; $pageSection='agent';
include __DIR__.'/../views/partials/header.php';
include __DIR__.'/../views/partials/navbar.php';
?>
<div class="dashboard-wrapper">

  <!-- SIDEBAR AGENT -->
  <aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
      <div class="sidebar-user">
        <div class="user-avatar"><i class="fas fa-user-tie"></i></div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?></div>
          <div class="user-role"><i class="fas fa-circle text-warning" style="font-size:.5rem;"></i> Agent municipal</div>
        </div>
      </div>
    </div>
    <nav class="sidebar-menu">
      <div class="menu-section">Gestion</div>
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link active" href="<?= APP_URL ?>/agent/dashboard.php"><span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>Tableau de bord</a></li>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_URL ?>/agent/demandes.php">
            <span class="nav-icon"><i class="fas fa-inbox"></i></span>Toutes les demandes
            <?php if ($stats['soumis']>0): ?><span class="badge-count"><?= $stats['soumis'] ?></span><?php endif; ?>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/agent/demandes.php?statut=en_cours"><span class="nav-icon"><i class="fas fa-spinner"></i></span>En cours de traitement</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/agent/demandes.php?statut=valide"><span class="nav-icon"><i class="fas fa-check-circle"></i></span>Demandes validées</a></li>
      </ul>
      <div class="menu-section mt-3">Par type</div>
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/agent/demandes.php?type=naissance"><span class="nav-icon"><i class="fas fa-baby"></i></span>Naissances</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/agent/demandes.php?type=deces"><span class="nav-icon"><i class="fas fa-cross"></i></span>Décès</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/agent/demandes.php?type=mariage"><span class="nav-icon"><i class="fas fa-heart"></i></span>Mariages</a></li>
      </ul>
      <div class="menu-section mt-3">Mon compte</div>
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/agent/profil.php"><span class="nav-icon"><i class="fas fa-user-cog"></i></span>Mon profil</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/auth/logout.php" style="color:rgba(255,100,100,.75)!important;"><span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>Déconnexion</a></li>
      </ul>
    </nav>
  </aside>

  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-tachometer-alt me-2 text-primary"></i>Tableau de bord Agent</h1>
        <div class="breadcrumb-bar">Bonjour, <span><?= htmlspecialchars($user['prenom']) ?></span> — <?= date('l d F Y') ?></div>
      </div>
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-2">
        <div class="stat-card primary">
          <div class="stat-icon"><i class="fas fa-folder"></i></div>
          <div><div class="stat-value" data-counter="<?= $stats['total'] ?>"><?= $stats['total'] ?></div><div class="stat-label">Total</div></div>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="stat-card secondary">
          <div class="stat-icon"><i class="fas fa-inbox"></i></div>
          <div><div class="stat-value" data-counter="<?= $stats['soumis'] ?>"><?= $stats['soumis'] ?></div><div class="stat-label">Nouvelles</div></div>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="stat-card warning">
          <div class="stat-icon"><i class="fas fa-spinner"></i></div>
          <div><div class="stat-value" data-counter="<?= $stats['en_cours'] ?>"><?= $stats['en_cours'] ?></div><div class="stat-label">En cours</div></div>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="stat-card success">
          <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
          <div><div class="stat-value" data-counter="<?= $stats['valide'] ?>"><?= $stats['valide'] ?></div><div class="stat-label">Validées</div></div>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="stat-card danger">
          <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
          <div><div class="stat-value" data-counter="<?= $stats['rejete'] ?>"><?= $stats['rejete'] ?></div><div class="stat-label">Rejetées</div></div>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="stat-card info">
          <div class="stat-icon"><i class="fas fa-user-check"></i></div>
          <div><div class="stat-value" data-counter="<?= $stats['mes_dem'] ?>"><?= $stats['mes_dem'] ?></div><div class="stat-label">Mes dossiers</div></div>
        </div>
      </div>
    </div>

    <div class="row g-4">

      <!-- Nouvelles demandes -->
      <div class="col-lg-7">
        <div class="table-card">
          <div class="table-card-header">
            <h5><i class="fas fa-inbox me-2 text-primary"></i>Demandes récentes</h5>
            <a href="<?= APP_URL ?>/agent/demandes.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
          </div>
          <?php if (empty($recentes)): ?>
          <div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-3x mb-2 d-block opacity-25"></i>Aucune demande</div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table-modern">
              <thead>
                <tr><th>Référence</th><th>Type</th><th>Citoyen</th><th>Date</th><th>Statut</th><th></th></tr>
              </thead>
              <tbody>
                <?php foreach ($recentes as $d): ?>
                <tr>
                  <td><code style="font-size:.76rem;color:var(--bleu-primaire);"><?= htmlspecialchars($d['numero_reference']) ?></code></td>
                  <td><i class="<?= getTypeActeIcon($d['type_acte']) ?> me-1"></i><?= getTypeActeLabel($d['type_acte']) ?></td>
                  <td style="font-size:.83rem;"><?= htmlspecialchars($d['citoyen_prenom'].' '.$d['citoyen_nom']) ?></td>
                  <td style="font-size:.78rem;color:#718096;"><?= formatDate($d['created_at']) ?></td>
                  <td><?= getStatutBadge($d['statut']) ?></td>
                  <td>
                    <a href="<?= APP_URL ?>/agent/traiter-demande.php?id=<?= $d['id'] ?>"
                       class="btn btn-icon btn-outline-primary btn-sm" title="Traiter">
                      <i class="fas fa-<?= $d['statut']==='soumis'?'edit':'eye' ?>"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Mes dossiers en cours -->
      <div class="col-lg-5">
        <div class="content-card">
          <div class="card-header-title">
            <i class="fas fa-tasks text-warning"></i>Mes dossiers en cours
          </div>
          <?php if (empty($mesDemandes)): ?>
          <div class="text-center py-3 text-muted" style="font-size:.85rem;">
            <i class="fas fa-check-circle fa-2x mb-2 d-block text-success opacity-50"></i>
            Aucun dossier en attente !
          </div>
          <?php else: ?>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($mesDemandes as $d): ?>
            <a href="<?= APP_URL ?>/agent/traiter-demande.php?id=<?= $d['id'] ?>"
               class="d-flex align-items-center gap-2 p-2 rounded text-decoration-none"
               style="background:var(--gris-clair);transition:var(--transition);"
               onmouseover="this.style.background='#e3f2fd'" onmouseout="this.style.background='var(--gris-clair)'">
              <div style="width:38px;height:38px;background:var(--bleu-primaire);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;font-size:.85rem;">
                <i class="<?= getTypeActeIcon($d['type_acte']) ?>"></i>
              </div>
              <div class="flex-1">
                <div style="font-size:.82rem;font-weight:600;color:var(--bleu-fonce);"><?= htmlspecialchars($d['numero_reference']) ?></div>
                <div style="font-size:.76rem;color:#718096;"><?= htmlspecialchars($d['citoyen_prenom'].' '.$d['citoyen_nom']) ?> · <?= formatDate($d['created_at']) ?></div>
              </div>
              <i class="fas fa-chevron-right text-muted" style="font-size:.7rem;"></i>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Actions rapides -->
        <div class="content-card mt-4">
          <div class="card-header-title"><i class="fas fa-bolt text-warning"></i>Actions rapides</div>
          <div class="d-grid gap-2">
            <a href="<?= APP_URL ?>/agent/demandes.php?statut=soumis" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
              <i class="fas fa-inbox"></i>Traiter les nouvelles demandes
              <?php if ($stats['soumis']>0): ?><span class="badge bg-danger ms-auto"><?= $stats['soumis'] ?></span><?php endif; ?>
            </a>
            <a href="<?= APP_URL ?>/agent/demandes.php?type=naissance" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
              <i class="fas fa-baby"></i>Dossiers naissances
            </a>
            <a href="<?= APP_URL ?>/agent/demandes.php?type=deces" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
              <i class="fas fa-cross"></i>Dossiers décès
            </a>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__.'/../views/partials/footer.php'; ?>
