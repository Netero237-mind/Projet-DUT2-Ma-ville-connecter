<?php
/**
 * Journal d'activité — Admin
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$user = getCurrentUser();

$search = $_GET['search'] ?? '';
$action = $_GET['action'] ?? '';
$page   = max(1,(int)($_GET['page']??1));
$perPage = 20; $offset = ($page-1)*$perPage;

$where  = "WHERE 1=1";
$params = [];
if ($search) { $where .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR h.description LIKE ?)"; $s="%$search%"; $params[]=$s;$params[]=$s;$params[]=$s; }
if ($action) { $where .= " AND h.action=?"; $params[]=$action; }

$total = dbQuery("SELECT COUNT(*) as c FROM historique_actions h LEFT JOIN users u ON h.user_id=u.id $where",$params)->fetch()['c'];
$pages = max(1,ceil($total/$perPage));
$params[]=$perPage; $params[]=$offset;

$logs = dbQuery("
    SELECT h.*, u.nom, u.prenom, u.email
    FROM historique_actions h
    LEFT JOIN users u ON h.user_id=u.id
    $where
    ORDER BY h.created_at DESC LIMIT ? OFFSET ?
",$params)->fetchAll();

$actions = dbQuery("SELECT DISTINCT action FROM historique_actions ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle='Journal d\'activité'; $pageSection='admin';
include __DIR__.'/../views/partials/header.php';
include __DIR__.'/../views/partials/navbar.php';
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
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/parametres.php"><span class="nav-icon"><i class="fas fa-cog"></i></span>Paramètres</a></li>
        <li class="nav-item"><a class="nav-link active" href="<?= APP_URL ?>/admin/journal.php"><span class="nav-icon"><i class="fas fa-history"></i></span>Journal</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/auth/logout.php" style="color:rgba(255,100,100,.75)!important;"><span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>Déconnexion</a></li>
      </ul>
    </nav>
  </aside>

  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-history me-2 text-primary"></i>Journal d'activité</h1>
        <div class="breadcrumb-bar"><a href="<?= APP_URL ?>/admin/dashboard.php">Admin</a> › <span>Journal</span></div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="content-card mb-4">
      <form method="GET" class="row g-2">
        <div class="col-md-5">
          <input type="text" class="form-control form-control-sm" name="search"
                 value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Rechercher utilisateur, description…">
        </div>
        <div class="col-md-4">
          <select class="form-select form-select-sm" name="action">
            <option value="">Toutes les actions</option>
            <?php foreach ($actions as $a): ?>
            <option value="<?= htmlspecialchars($a) ?>" <?= $action===$a?'selected':'' ?>><?= htmlspecialchars($a) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-auto d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Filtrer</button>
          <a href="<?= APP_URL ?>/admin/journal.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
        </div>
      </form>
    </div>

    <div class="table-card">
      <div class="table-card-header">
        <h5><i class="fas fa-list me-2 text-primary"></i><?= $total ?> entrée<?= $total>1?'s':'' ?> dans le journal</h5>
      </div>
      <?php if (empty($logs)): ?>
      <div class="text-center py-5 text-muted"><i class="fas fa-history fa-3x mb-3 d-block opacity-25"></i>Aucune entrée</div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table-modern">
          <thead>
            <tr><th>Date et heure</th><th>Utilisateur</th><th>Action</th><th>Description</th><th>Entité</th><th>IP</th></tr>
          </thead>
          <tbody>
            <?php
            $actionIcons = [
                'connexion'=>['fas fa-sign-in-alt','text-success'],
                'deconnexion'=>['fas fa-sign-out-alt','text-secondary'],
                'depot_demande'=>['fas fa-file-upload','text-info'],
                'validation_demande'=>['fas fa-check-circle','text-success'],
                'prise_en_charge'=>['fas fa-hand-point-right','text-warning'],
                'rejet'=>['fas fa-times-circle','text-danger'],
                'inscription'=>['fas fa-user-plus','text-primary'],
                'suppression_user'=>['fas fa-trash','text-danger'],
                'modif_parametres'=>['fas fa-cog','text-warning'],
                'echec_connexion'=>['fas fa-exclamation-triangle','text-danger'],
            ];
            foreach ($logs as $log):
              [$icon,$color] = $actionIcons[$log['action']] ?? ['fas fa-dot-circle','text-muted'];
            ?>
            <tr>
              <td style="font-size:.78rem;white-space:nowrap;color:#718096;">
                <?= formatDateTime($log['created_at']) ?>
              </td>
              <td style="font-size:.82rem;">
                <?php if ($log['nom']): ?>
                  <div><?= htmlspecialchars($log['prenom'].' '.$log['nom']) ?></div>
                  <div style="font-size:.72rem;color:#adb5bd;"><?= htmlspecialchars($log['email']??'') ?></div>
                <?php else: ?>
                  <em class="text-muted" style="font-size:.8rem;">Système</em>
                <?php endif; ?>
              </td>
              <td>
                <span class="<?= $color ?>" style="font-size:.8rem;font-weight:600;">
                  <i class="<?= $icon ?> me-1"></i><?= htmlspecialchars($log['action']) ?>
                </span>
              </td>
              <td style="font-size:.82rem;"><?= htmlspecialchars(substr($log['description']??'',0,90)) ?><?= strlen($log['description']??'')>90?'…':'' ?></td>
              <td style="font-size:.78rem;color:#718096;">
                <?php if ($log['entite']): ?>
                  <code><?= htmlspecialchars($log['entite']) ?> #<?= $log['entite_id'] ?></code>
                <?php endif; ?>
              </td>
              <td><code style="font-size:.72rem;"><?= htmlspecialchars($log['ip_address']??'—') ?></code></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($pages > 1): ?>
      <div class="d-flex justify-content-center gap-1 p-3 flex-wrap">
        <?php for ($p=1;$p<=$pages;$p++): ?>
          <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($action) ?>"
             class="btn btn-sm <?= $p===$page?'btn-primary':'btn-outline-secondary' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include __DIR__.'/../views/partials/footer.php'; ?>
