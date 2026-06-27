<?php
/**
 * Tableau de bord Administrateur — E-Gouvernance État Civil
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$user = getCurrentUser();

// Statistiques globales
$stats = [
    'citoyens'  => dbQuery("SELECT COUNT(*) as c FROM users WHERE role_id=3")->fetch()['c'],
    'agents'    => dbQuery("SELECT COUNT(*) as c FROM users WHERE role_id=2")->fetch()['c'],
    'demandes'  => dbQuery("SELECT COUNT(*) as c FROM demandes")->fetch()['c'],
    'valides'   => dbQuery("SELECT COUNT(*) as c FROM demandes WHERE statut='valide'")->fetch()['c'],
    'soumis'    => dbQuery("SELECT COUNT(*) as c FROM demandes WHERE statut='soumis'")->fetch()['c'],
    'en_cours'  => dbQuery("SELECT COUNT(*) as c FROM demandes WHERE statut='en_cours'")->fetch()['c'],
    'rejetes'   => dbQuery("SELECT COUNT(*) as c FROM demandes WHERE statut='rejete'")->fetch()['c'],
];

// Demandes par type
$parType = dbQuery("
    SELECT type_acte, COUNT(*) as total,
    SUM(CASE WHEN statut='valide' THEN 1 ELSE 0 END) as valides
    FROM demandes GROUP BY type_acte
")->fetchAll();

// Activité mensuelle (6 derniers mois)
$mensuel = dbQuery("
    SELECT DATE_FORMAT(created_at,'%Y-%m') as mois, COUNT(*) as total
    FROM demandes
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mois ORDER BY mois
")->fetchAll();

// Derniers utilisateurs
$derniersUsers = dbQuery("
    SELECT u.*, r.nom as role_nom FROM users u
    JOIN roles r ON u.role_id=r.id
    ORDER BY u.created_at DESC LIMIT 8
")->fetchAll();

// Journal des actions récentes
$journal = dbQuery("
    SELECT h.*, u.nom, u.prenom FROM historique_actions h
    LEFT JOIN users u ON h.user_id=u.id
    ORDER BY h.created_at DESC LIMIT 10
")->fetchAll();

$pageTitle='Administration'; $pageSection='admin';
include __DIR__.'/../views/partials/header.php';
include __DIR__.'/../views/partials/navbar.php';
?>
<div class="dashboard-wrapper">

  <!-- SIDEBAR ADMIN -->
  <aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
      <div class="sidebar-user">
        <div class="user-avatar" style="background:rgba(255,215,0,.2);"><i class="fas fa-shield-alt" style="color:gold;"></i></div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?></div>
          <div class="user-role"><i class="fas fa-circle text-danger" style="font-size:.5rem;"></i> Administrateur</div>
        </div>
      </div>
    </div>
    <nav class="sidebar-menu">
      <div class="menu-section">Tableau de bord</div>
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link active" href="<?= APP_URL ?>/admin/dashboard.php"><span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>Vue d'ensemble</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/statistiques.php"><span class="nav-icon"><i class="fas fa-chart-bar"></i></span>Statistiques</a></li>
      </ul>
      <div class="menu-section mt-3">Gestion</div>
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/utilisateurs.php"><span class="nav-icon"><i class="fas fa-users"></i></span>Utilisateurs</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/agents.php"><span class="nav-icon"><i class="fas fa-user-tie"></i></span>Agents</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/demandes.php"><span class="nav-icon"><i class="fas fa-folder-open"></i></span>Toutes les demandes</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/journal.php"><span class="nav-icon"><i class="fas fa-history"></i></span>Journal d'activité</a></li>
      </ul>
      <div class="menu-section mt-3">Système</div>
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/parametres.php"><span class="nav-icon"><i class="fas fa-cog"></i></span>Paramètres</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/auth/logout.php" style="color:rgba(255,100,100,.75)!important;"><span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>Déconnexion</a></li>
      </ul>
    </nav>
  </aside>

  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-shield-alt me-2" style="color:gold;"></i>Administration Système</h1>
        <div class="breadcrumb-bar">Tableau de bord · <?= date('l d F Y') ?></div>
      </div>
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    </div>

    <!-- Stats principales -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="stat-card primary">
          <div class="stat-icon"><i class="fas fa-users"></i></div>
          <div><div class="stat-value" data-counter="<?= $stats['citoyens'] ?>"><?= $stats['citoyens'] ?></div><div class="stat-label">Citoyens inscrits</div></div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card info">
          <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
          <div><div class="stat-value" data-counter="<?= $stats['agents'] ?>"><?= $stats['agents'] ?></div><div class="stat-label">Agents actifs</div></div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card secondary">
          <div class="stat-icon"><i class="fas fa-folder"></i></div>
          <div><div class="stat-value" data-counter="<?= $stats['demandes'] ?>"><?= $stats['demandes'] ?></div><div class="stat-label">Demandes totales</div></div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card success">
          <div class="stat-icon"><i class="fas fa-file-certificate"></i></div>
          <div><div class="stat-value" data-counter="<?= $stats['valides'] ?>"><?= $stats['valides'] ?></div><div class="stat-label">Actes délivrés</div></div>
        </div>
      </div>
    </div>

    <!-- Stats secondaires -->
    <div class="row g-3 mb-4">
      <div class="col-4">
        <div class="stat-card warning">
          <div class="stat-icon"><i class="fas fa-hourglass"></i></div>
          <div><div class="stat-value" data-counter="<?= $stats['soumis'] ?>"><?= $stats['soumis'] ?></div><div class="stat-label">En attente</div></div>
        </div>
      </div>
      <div class="col-4">
        <div class="stat-card info">
          <div class="stat-icon"><i class="fas fa-spinner"></i></div>
          <div><div class="stat-value" data-counter="<?= $stats['en_cours'] ?>"><?= $stats['en_cours'] ?></div><div class="stat-label">En traitement</div></div>
        </div>
      </div>
      <div class="col-4">
        <div class="stat-card danger">
          <div class="stat-icon"><i class="fas fa-times"></i></div>
          <div><div class="stat-value" data-counter="<?= $stats['rejetes'] ?>"><?= $stats['rejetes'] ?></div><div class="stat-label">Rejetées</div></div>
        </div>
      </div>
    </div>

    <div class="row g-4">

      <!-- Demandes par type -->
      <div class="col-lg-5">
        <div class="content-card">
          <div class="card-header-title"><i class="fas fa-chart-pie text-primary"></i>Répartition par type d'acte</div>
          <?php
          $totalDem = max(1, $stats['demandes']);
          $typeColors = ['naissance'=>'#1976d2','deces'=>'#c62828','mariage'=>'#6a1b9a','casier'=>'#2e7d32','autre'=>'#e65100'];
          foreach ($parType as $pt):
            $pct = round(($pt['total']/$totalDem)*100);
            $color = $typeColors[$pt['type_acte']] ?? '#607d8b';
          ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span style="font-size:.85rem;font-weight:600;">
                <i class="<?= getTypeActeIcon($pt['type_acte']) ?> me-2" style="color:<?= $color ?>;"></i>
                <?= getTypeActeLabel($pt['type_acte']) ?>
              </span>
              <span style="font-size:.82rem;color:#718096;"><?= $pt['total'] ?> (<?= $pct ?>%)</span>
            </div>
            <div class="progress" style="height:8px;border-radius:4px;">
              <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
            </div>
            <div style="font-size:.75rem;color:#adb5bd;margin-top:.2rem;"><?= $pt['valides'] ?> validées sur <?= $pt['total'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Derniers utilisateurs -->
      <div class="col-lg-7">
        <div class="table-card">
          <div class="table-card-header">
            <h5><i class="fas fa-users me-2 text-primary"></i>Derniers comptes créés</h5>
            <a href="<?= APP_URL ?>/admin/utilisateurs.php" class="btn btn-sm btn-outline-primary">Gérer</a>
          </div>
          <div class="table-responsive">
            <table class="table-modern">
              <thead><tr><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Inscription</th></tr></thead>
              <tbody>
                <?php foreach ($derniersUsers as $u): ?>
                <tr>
                  <td style="font-size:.85rem;">
                    <div class="d-flex align-items-center gap-2">
                      <div style="width:32px;height:32px;border-radius:50%;background:var(--gris-moyen);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:var(--bleu-primaire);flex-shrink:0;">
                        <?= strtoupper(substr($u['prenom'],0,1).substr($u['nom'],0,1)) ?>
                      </div>
                      <div><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></div>
                    </div>
                  </td>
                  <td style="font-size:.82rem;"><?= htmlspecialchars($u['email']) ?></td>
                  <td>
                    <?php
                    $roleColors = ['admin'=>'bg-danger','agent'=>'bg-warning text-dark','citoyen'=>'bg-primary'];
                    $roleColor  = $roleColors[$u['role_nom']] ?? 'bg-secondary';
                    ?>
                    <span class="badge <?= $roleColor ?>"><?= ucfirst($u['role_nom']) ?></span>
                  </td>
                  <td>
                    <span class="badge <?= $u['statut']==='actif'?'bg-success':'bg-secondary' ?>">
                      <?= ucfirst($u['statut']) ?>
                    </span>
                  </td>
                  <td style="font-size:.78rem;color:#718096;"><?= formatDate($u['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Journal d'activité -->
      <div class="col-12">
        <div class="table-card">
          <div class="table-card-header">
            <h5><i class="fas fa-history me-2 text-primary"></i>Journal d'activité récent</h5>
            <a href="<?= APP_URL ?>/admin/journal.php" class="btn btn-sm btn-outline-primary">Journal complet</a>
          </div>
          <div class="table-responsive">
            <table class="table-modern">
              <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
              <tbody>
                <?php foreach ($journal as $j):
                  $actionColors = ['connexion'=>'text-success','deconnexion'=>'text-secondary','validation_demande'=>'text-primary','depot_demande'=>'text-info','rejet'=>'text-danger'];
                  $color = $actionColors[$j['action']] ?? 'text-muted';
                ?>
                <tr>
                  <td style="font-size:.78rem;color:#718096;white-space:nowrap;"><?= formatDateTime($j['created_at']) ?></td>
                  <td style="font-size:.83rem;"><?= $j['nom']?htmlspecialchars($j['prenom'].' '.$j['nom']):'<em class="text-muted">Système</em>' ?></td>
                  <td><span class="<?= $color ?>" style="font-size:.8rem;font-weight:600;"><?= htmlspecialchars($j['action']) ?></span></td>
                  <td style="font-size:.82rem;"><?= htmlspecialchars(substr($j['description'],0,80)) ?></td>
                  <td><code style="font-size:.75rem;"><?= htmlspecialchars($j['ip_address']??'—') ?></code></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?php
// Script Chart.js pour le graphique mensuel
$moisLabels = array_map(fn($m) => $m['mois'], $mensuel);
$moisData   = array_map(fn($m) => $m['total'], $mensuel);
$extraJs = "<script>
// Les données de graphique sont disponibles pour extension avec Chart.js
const chartData = { labels: ".json_encode($moisLabels).", data: ".json_encode($moisData)." };
console.log('[Admin] Données mensuelles chargées:', chartData);
</script>";
?>
<?php include __DIR__.'/../views/partials/footer.php'; ?>
