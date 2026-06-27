<?php
/**
 * Toutes les demandes — Admin
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$user = getCurrentUser();

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $demandes = dbQuery("
        SELECT d.numero_reference, d.type_acte, d.statut, d.priorite,
               u.nom as citoyen_nom, u.prenom as citoyen_prenom, u.email as citoyen_email,
               a.nom as agent_nom, a.prenom as agent_prenom,
               d.created_at, d.date_validation
        FROM demandes d
        JOIN users u ON d.user_id=u.id
        LEFT JOIN users a ON d.agent_id=a.id
        ORDER BY d.created_at DESC
    ")->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="demandes_'.date('Ymd').'.csv"');
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Référence','Type','Statut','Priorité','Citoyen','Email','Agent','Date dépôt','Date validation'], ';');
    foreach ($demandes as $d) {
        fputcsv($out, [
            $d['numero_reference'], getTypeActeLabel($d['type_acte']), $d['statut'], $d['priorite'],
            $d['citoyen_prenom'].' '.$d['citoyen_nom'], $d['citoyen_email'],
            $d['agent_nom'] ? $d['agent_prenom'].' '.$d['agent_nom'] : '—',
            $d['created_at'], $d['date_validation'] ?? '—'
        ], ';');
    }
    fclose($out);
    exit;
}

// Filtres
$filtre_type   = $_GET['type']   ?? '';
$filtre_statut = $_GET['statut'] ?? '';
$filtre_search = $_GET['search'] ?? '';
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 15; $offset = ($page-1)*$perPage;

$where  = "WHERE 1=1";
$params = [];
if ($filtre_type)   { $where .= " AND d.type_acte=?"; $params[]=$filtre_type; }
if ($filtre_statut) { $where .= " AND d.statut=?";    $params[]=$filtre_statut; }
if ($filtre_search) {
    $where .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR d.numero_reference LIKE ?)";
    $s="%$filtre_search%"; $params[]=$s;$params[]=$s;$params[]=$s;
}

$total = dbQuery("SELECT COUNT(*) as c FROM demandes d JOIN users u ON d.user_id=u.id $where",$params)->fetch()['c'];
$pages = max(1,ceil($total/$perPage));
$params[]=$perPage; $params[]=$offset;

$demandes = dbQuery("
    SELECT d.*, u.nom as citoyen_nom, u.prenom as citoyen_prenom,
           a.nom as agent_nom, a.prenom as agent_prenom
    FROM demandes d JOIN users u ON d.user_id=u.id
    LEFT JOIN users a ON d.agent_id=a.id
    $where
    ORDER BY d.created_at DESC LIMIT ? OFFSET ?
",$params)->fetchAll();

$pageTitle='Toutes les demandes'; $pageSection='admin';
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
        <li class="nav-item"><a class="nav-link active" href="<?= APP_URL ?>/admin/demandes.php"><span class="nav-icon"><i class="fas fa-folder-open"></i></span>Demandes</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/parametres.php"><span class="nav-icon"><i class="fas fa-cog"></i></span>Paramètres</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/journal.php"><span class="nav-icon"><i class="fas fa-history"></i></span>Journal</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/auth/logout.php" style="color:rgba(255,100,100,.75)!important;"><span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>Déconnexion</a></li>
      </ul>
    </nav>
  </aside>

  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-folder-open me-2 text-primary"></i>Toutes les demandes</h1>
        <div class="breadcrumb-bar"><a href="<?= APP_URL ?>/admin/dashboard.php">Admin</a> › <span>Demandes</span></div>
      </div>
      <a href="?export=csv&type=<?= urlencode($filtre_type) ?>&statut=<?= urlencode($filtre_statut) ?>"
         class="btn btn-sm btn-outline-success">
        <i class="fas fa-file-csv me-1"></i>Exporter CSV
      </a>
    </div>

    <!-- Filtres -->
    <div class="content-card mb-4">
      <form method="GET" class="row g-2">
        <div class="col-md-4">
          <input type="text" class="form-control form-control-sm" name="search"
                 value="<?= htmlspecialchars($filtre_search) ?>" placeholder="🔍 Référence ou nom citoyen…">
        </div>
        <div class="col-md-3">
          <select class="form-select form-select-sm" name="type">
            <option value="">Tous les types</option>
            <option value="naissance" <?= $filtre_type==='naissance'?'selected':'' ?>>Naissance</option>
            <option value="deces"     <?= $filtre_type==='deces'?'selected':'' ?>>Décès</option>
            <option value="mariage"   <?= $filtre_type==='mariage'?'selected':'' ?>>Mariage</option>
          </select>
        </div>
        <div class="col-md-3">
          <select class="form-select form-select-sm" name="statut">
            <option value="">Tous les statuts</option>
            <option value="soumis"   <?= $filtre_statut==='soumis'?'selected':'' ?>>Soumis</option>
            <option value="en_cours" <?= $filtre_statut==='en_cours'?'selected':'' ?>>En cours</option>
            <option value="valide"   <?= $filtre_statut==='valide'?'selected':'' ?>>Validé</option>
            <option value="rejete"   <?= $filtre_statut==='rejete'?'selected':'' ?>>Rejeté</option>
          </select>
        </div>
        <div class="col-auto d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Filtrer</button>
          <a href="<?= APP_URL ?>/admin/demandes.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
        </div>
      </form>
    </div>

    <div class="table-card">
      <div class="table-card-header">
        <h5><i class="fas fa-list me-2 text-primary"></i><?= $total ?> demande<?= $total>1?'s':'' ?></h5>
        <span style="font-size:.8rem;color:#718096;">Page <?= $page ?>/<?= $pages ?></span>
      </div>

      <?php if (empty($demandes)): ?>
      <div class="text-center py-5 text-muted"><i class="fas fa-folder-open fa-3x mb-3 d-block opacity-25"></i>Aucun résultat</div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table-modern">
          <thead>
            <tr>
              <th>Référence</th>
              <th>Type</th>
              <th>Citoyen</th>
              <th>Agent</th>
              <th>Date</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($demandes as $d): ?>
            <tr>
              <td><code style="font-size:.76rem;color:var(--bleu-primaire);"><?= htmlspecialchars($d['numero_reference']) ?></code></td>
              <td style="font-size:.83rem;white-space:nowrap;"><i class="<?= getTypeActeIcon($d['type_acte']) ?> me-1"></i><?= getTypeActeLabel($d['type_acte']) ?></td>
              <td style="font-size:.83rem;"><?= htmlspecialchars($d['citoyen_prenom'].' '.$d['citoyen_nom']) ?></td>
              <td style="font-size:.82rem;"><?= $d['agent_nom'] ? htmlspecialchars($d['agent_prenom'].' '.$d['agent_nom']) : '<span class="text-muted">—</span>' ?></td>
              <td style="font-size:.78rem;color:#718096;white-space:nowrap;"><?= formatDate($d['created_at']) ?></td>
              <td><?= getStatutBadge($d['statut']) ?></td>
              <td>
                <div class="d-flex gap-1">
                  <a href="<?= APP_URL ?>/agent/traiter-demande.php?id=<?= $d['id'] ?>"
                     class="btn btn-icon btn-outline-primary btn-sm" title="Voir/Traiter">
                    <i class="fas fa-eye"></i>
                  </a>
                  <?php if ($d['statut']==='valide' && $d['acte_pdf_path']): ?>
                  <a href="<?= APP_URL ?>/<?= htmlspecialchars($d['acte_pdf_path']) ?>" target="_blank"
                     class="btn btn-icon btn-outline-success btn-sm" title="Acte PDF">
                    <i class="fas fa-file-pdf"></i>
                  </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <!-- Pagination -->
      <?php if ($pages > 1): ?>
      <div class="d-flex justify-content-center gap-1 p-3 flex-wrap">
        <?php for ($p=1;$p<=$pages;$p++): ?>
          <a href="?page=<?= $p ?>&type=<?= urlencode($filtre_type) ?>&statut=<?= urlencode($filtre_statut) ?>&search=<?= urlencode($filtre_search) ?>"
             class="btn btn-sm <?= $p===$page?'btn-primary':'btn-outline-secondary' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include __DIR__.'/../views/partials/footer.php'; ?>
