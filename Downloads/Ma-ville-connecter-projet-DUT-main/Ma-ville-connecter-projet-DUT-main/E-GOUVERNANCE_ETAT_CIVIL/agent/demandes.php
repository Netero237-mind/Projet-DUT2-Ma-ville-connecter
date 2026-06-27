<?php
/**
 * Liste de toutes les demandes — Espace Agent
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
requireRole(['agent', 'admin']);
$user    = getCurrentUser();
$agentId = $user['id'];

// Filtres
$filtre_type   = $_GET['type']    ?? '';
$filtre_statut = $_GET['statut']  ?? '';
$filtre_search = $_GET['search']  ?? '';
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 12;
$offset        = ($page - 1) * $perPage;

$where  = "WHERE 1=1";
$params = [];
if ($filtre_type)   { $where .= " AND d.type_acte=?";   $params[] = $filtre_type; }
if ($filtre_statut) { $where .= " AND d.statut=?";       $params[] = $filtre_statut; }
if ($filtre_search) {
    $where .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR d.numero_reference LIKE ?)";
    $s = "%$filtre_search%"; $params[]=$s; $params[]=$s; $params[]=$s;
}

$total = dbQuery("SELECT COUNT(*) as c FROM demandes d JOIN users u ON d.user_id=u.id $where", $params)->fetch()['c'];
$pages = max(1, ceil($total / $perPage));
$params[] = $perPage; $params[] = $offset;

$demandes = dbQuery("
    SELECT d.*,
           u.nom  as citoyen_nom,  u.prenom  as citoyen_prenom,
           a.nom  as agent_nom,    a.prenom  as agent_prenom
    FROM demandes d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN users a ON d.agent_id = a.id
    $where
    ORDER BY
        FIELD(d.statut,'soumis','en_cours','valide','rejete','archive'),
        d.created_at ASC
    LIMIT ? OFFSET ?
", $params)->fetchAll();

$pageTitle   = 'Toutes les demandes';
$pageSection = 'agent';
include __DIR__ . '/../views/partials/header.php';
include __DIR__ . '/../views/partials/navbar.php';
?>
<div class="dashboard-wrapper">

  <!-- Sidebar agent -->
  <aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
      <div class="sidebar-user">
        <div class="user-avatar"><i class="fas fa-user-tie"></i></div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?></div>
          <div class="user-role">Agent municipal</div>
        </div>
      </div>
    </div>
    <nav class="sidebar-menu">
      <ul class="nav flex-column mt-2">
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/agent/dashboard.php"><span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>Tableau de bord</a></li>
        <li class="nav-item"><a class="nav-link active" href="<?= APP_URL ?>/agent/demandes.php"><span class="nav-icon"><i class="fas fa-inbox"></i></span>Toutes les demandes</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/agent/demandes.php?statut=soumis"><span class="nav-icon"><i class="fas fa-bell"></i></span>Nouvelles demandes</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/agent/demandes.php?statut=en_cours"><span class="nav-icon"><i class="fas fa-spinner"></i></span>En cours</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/agent/demandes.php?statut=valide"><span class="nav-icon"><i class="fas fa-check-circle"></i></span>Validées</a></li>
        <li><hr style="border-color:rgba(255,255,255,.1);margin:.5rem 1rem;"></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/agent/demandes.php?type=naissance"><span class="nav-icon"><i class="fas fa-baby"></i></span>Naissances</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/agent/demandes.php?type=deces"><span class="nav-icon"><i class="fas fa-cross"></i></span>Décès</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/agent/demandes.php?type=mariage"><span class="nav-icon"><i class="fas fa-heart"></i></span>Mariages</a></li>
        <li><hr style="border-color:rgba(255,255,255,.1);margin:.5rem 1rem;"></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/auth/logout.php" style="color:rgba(255,100,100,.75)!important;"><span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>Déconnexion</a></li>
      </ul>
    </nav>
  </aside>

  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-inbox me-2 text-primary"></i>Gestion des demandes</h1>
        <div class="breadcrumb-bar">
          <a href="<?= APP_URL ?>/agent/dashboard.php">Accueil</a> › <span>Demandes</span>
        </div>
      </div>
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle">
        <i class="fas fa-bars"></i>
      </button>
    </div>

    <!-- Filtres avancés -->
    <div class="content-card mb-4">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label" style="font-size:.78rem;">Recherche</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" class="form-control" name="search"
                   value="<?= htmlspecialchars($filtre_search) ?>"
                   placeholder="Référence, nom du citoyen…">
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label" style="font-size:.78rem;">Type d'acte</label>
          <select class="form-select form-select-sm" name="type">
            <option value="">Tous les types</option>
            <option value="naissance" <?= $filtre_type==='naissance'?'selected':'' ?>>🍼 Naissance</option>
            <option value="deces"     <?= $filtre_type==='deces'?'selected':'' ?>>✝ Décès</option>
            <option value="mariage"   <?= $filtre_type==='mariage'?'selected':'' ?>>💍 Mariage</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" style="font-size:.78rem;">Statut</label>
          <select class="form-select form-select-sm" name="statut">
            <option value="">Tous les statuts</option>
            <option value="soumis"   <?= $filtre_statut==='soumis'?'selected':'' ?>>🔵 Soumis</option>
            <option value="en_cours" <?= $filtre_statut==='en_cours'?'selected':'' ?>>🟡 En cours</option>
            <option value="valide"   <?= $filtre_statut==='valide'?'selected':'' ?>>🟢 Validé</option>
            <option value="rejete"   <?= $filtre_statut==='rejete'?'selected':'' ?>>🔴 Rejeté</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm flex-1">
            <i class="fas fa-filter me-1"></i>Filtrer
          </button>
          <a href="<?= APP_URL ?>/agent/demandes.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-times"></i>
          </a>
        </div>
      </form>
    </div>

    <!-- Résultats -->
    <div class="table-card">
      <div class="table-card-header">
        <h5>
          <i class="fas fa-list me-2 text-primary"></i>
          <?= $total ?> demande<?= $total>1?'s':'' ?>
          <?php if ($filtre_type || $filtre_statut || $filtre_search): ?>
            <span style="font-size:.78rem;color:#718096;font-weight:400;"> — filtrées</span>
          <?php endif; ?>
        </h5>
        <div style="font-size:.8rem;color:#718096;">
          Page <?= $page ?> / <?= $pages ?>
        </div>
      </div>

      <?php if (empty($demandes)): ?>
      <div class="text-center py-5" style="color:#adb5bd;">
        <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
        <p>Aucune demande ne correspond aux critères.</p>
      </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table-modern">
          <thead>
            <tr>
              <th>Référence</th>
              <th>Type d'acte</th>
              <th>Citoyen</th>
              <th>Soumis le</th>
              <th>Agent</th>
              <th>Priorité</th>
              <th>Statut</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($demandes as $d): ?>
            <tr <?= $d['statut']==='soumis'?'style="background:rgba(25,118,210,.03);"':'' ?>>
              <td>
                <code style="font-size:.76rem;color:var(--bleu-primaire);">
                  <?= htmlspecialchars($d['numero_reference']) ?>
                </code>
                <?php if ($d['statut']==='soumis'): ?>
                  <span class="badge bg-danger ms-1" style="font-size:.6rem;">NOUVEAU</span>
                <?php endif; ?>
              </td>
              <td style="font-size:.85rem;white-space:nowrap;">
                <i class="<?= getTypeActeIcon($d['type_acte']) ?> me-1 text-muted"></i>
                <?= getTypeActeLabel($d['type_acte']) ?>
              </td>
              <td style="font-size:.83rem;">
                <div><?= htmlspecialchars($d['citoyen_prenom'].' '.$d['citoyen_nom']) ?></div>
              </td>
              <td style="font-size:.78rem;white-space:nowrap;">
                <div><?= formatDate($d['created_at']) ?></div>
                <div style="color:#adb5bd;"><?= timeAgo($d['created_at']) ?></div>
              </td>
              <td style="font-size:.82rem;">
                <?php if ($d['agent_nom']): ?>
                  <span class="badge bg-light text-dark border">
                    <?= htmlspecialchars(substr($d['agent_prenom'],0,1).'. '.$d['agent_nom']) ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted" style="font-size:.78rem;">Non assigné</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($d['priorite']==='urgente'): ?>
                  <span class="badge bg-danger">🔴 Urgent</span>
                <?php else: ?>
                  <span class="badge bg-light text-muted border">Normal</span>
                <?php endif; ?>
              </td>
              <td><?= getStatutBadge($d['statut']) ?></td>
              <td>
                <a href="<?= APP_URL ?>/agent/traiter-demande.php?id=<?= $d['id'] ?>"
                   class="btn btn-sm btn-<?= $d['statut']==='soumis'?'primary':'outline-primary' ?> btn-icon"
                   data-bs-toggle="tooltip"
                   title="<?= $d['statut']==='soumis'?'Prendre en charge':'Consulter/Traiter' ?>">
                  <i class="fas fa-<?= $d['statut']==='soumis'?'edit':'eye' ?>"></i>
                </a>
                <?php if ($d['statut']==='valide' && $d['acte_pdf_path']): ?>
                <a href="<?= APP_URL ?>/<?= htmlspecialchars($d['acte_pdf_path']) ?>"
                   target="_blank"
                   class="btn btn-sm btn-outline-success btn-icon ms-1"
                   data-bs-toggle="tooltip" title="Voir l'acte PDF">
                  <i class="fas fa-file-pdf"></i>
                </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
      <div class="d-flex justify-content-center align-items-center gap-1 p-3 flex-wrap">
        <?php if ($page > 1): ?>
          <a href="?page=<?= $page-1 ?>&type=<?= urlencode($filtre_type) ?>&statut=<?= urlencode($filtre_statut) ?>&search=<?= urlencode($filtre_search) ?>"
             class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-chevron-left"></i>
          </a>
        <?php endif; ?>
        <?php
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1) echo '<span class="px-2 text-muted">…</span>';
        for ($p = $start; $p <= $end; $p++):
        ?>
          <a href="?page=<?= $p ?>&type=<?= urlencode($filtre_type) ?>&statut=<?= urlencode($filtre_statut) ?>&search=<?= urlencode($filtre_search) ?>"
             class="btn btn-sm <?= $p===$page?'btn-primary':'btn-outline-secondary' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
        <?php if ($end < $pages) echo '<span class="px-2 text-muted">…</span>'; ?>
        <?php if ($page < $pages): ?>
          <a href="?page=<?= $page+1 ?>&type=<?= urlencode($filtre_type) ?>&statut=<?= urlencode($filtre_statut) ?>&search=<?= urlencode($filtre_search) ?>"
             class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>

  </main>
</div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
