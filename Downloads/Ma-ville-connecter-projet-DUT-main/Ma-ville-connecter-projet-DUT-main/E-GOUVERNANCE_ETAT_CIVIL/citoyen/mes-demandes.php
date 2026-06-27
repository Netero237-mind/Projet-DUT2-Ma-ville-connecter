<?php
/**
 * Mes demandes — Espace Citoyen
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
requireRole('citoyen');
$user   = getCurrentUser();
$userId = $user['id'];

// Filtres
$filtre_type   = $_GET['type']   ?? '';
$filtre_statut = $_GET['statut'] ?? '';
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 10;
$offset        = ($page - 1) * $perPage;

$where  = "WHERE d.user_id = ?";
$params = [$userId];
if ($filtre_type)   { $where .= " AND d.type_acte = ?";   $params[] = $filtre_type; }
if ($filtre_statut) { $where .= " AND d.statut = ?";       $params[] = $filtre_statut; }

$total    = dbQuery("SELECT COUNT(*) as c FROM demandes d $where", $params)->fetch()['c'];
$pages    = max(1, ceil($total / $perPage));
$params[] = $perPage; $params[] = $offset;

$demandes = dbQuery("
    SELECT d.*, u.nom as agent_nom, u.prenom as agent_prenom
    FROM demandes d
    LEFT JOIN users u ON d.agent_id = u.id
    $where
    ORDER BY d.created_at DESC
    LIMIT ? OFFSET ?
", $params)->fetchAll();

$pageTitle='Mes demandes'; $pageSection='citoyen';
include __DIR__.'/../views/partials/header.php';
include __DIR__.'/../views/partials/navbar.php';
?>
<div class="dashboard-wrapper">
  <?php include __DIR__.'/../views/citoyen/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-folder-open me-2 text-primary"></i>Mes demandes</h1>
        <div class="breadcrumb-bar">
          <a href="<?= APP_URL ?>/citoyen/dashboard.php">Accueil</a> › <span>Mes demandes</span>
        </div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <div class="dropdown">
          <button class="btn btn-primary btn-sm dropdown-toggle">
            <i class="fas fa-plus me-1"></i>Nouvelle demande
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= APP_URL ?>/citoyen/demande-naissance.php"><i class="fas fa-baby me-2"></i>Acte de naissance</a></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/citoyen/demande-deces.php"><i class="fas fa-cross me-2"></i>Acte de décès</a></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/citoyen/demande-mariage.php"><i class="fas fa-heart me-2"></i>Acte de mariage</a></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="content-card mb-4">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label" style="font-size:.8rem;">Type d'acte</label>
          <select class="form-select form-select-sm" name="type">
            <option value="">Tous les types</option>
            <option value="naissance" <?= $filtre_type==='naissance'?'selected':'' ?>>Naissance</option>
            <option value="deces"     <?= $filtre_type==='deces'?'selected':'' ?>>Décès</option>
            <option value="mariage"   <?= $filtre_type==='mariage'?'selected':'' ?>>Mariage</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label" style="font-size:.8rem;">Statut</label>
          <select class="form-select form-select-sm" name="statut">
            <option value="">Tous les statuts</option>
            <option value="soumis"   <?= $filtre_statut==='soumis'?'selected':'' ?>>Soumis</option>
            <option value="en_cours" <?= $filtre_statut==='en_cours'?'selected':'' ?>>En cours</option>
            <option value="valide"   <?= $filtre_statut==='valide'?'selected':'' ?>>Validé</option>
            <option value="rejete"   <?= $filtre_statut==='rejete'?'selected':'' ?>>Rejeté</option>
          </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-1">
            <i class="fas fa-filter me-1"></i>Filtrer
          </button>
          <a href="<?= APP_URL ?>/citoyen/mes-demandes.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-times"></i>
          </a>
        </div>
      </form>
    </div>

    <!-- Tableau des demandes -->
    <div class="table-card">
      <div class="table-card-header">
        <h5><i class="fas fa-list me-2 text-primary"></i>
          <?= $total ?> demande<?= $total>1?'s':'' ?>
          <?= $filtre_type || $filtre_statut ? ' (filtrées)' : '' ?>
        </h5>
      </div>

      <?php if (empty($demandes)): ?>
      <div class="text-center py-5" style="color:#adb5bd;">
        <i class="fas fa-folder-open fa-3x mb-3 d-block opacity-25"></i>
        <p>Aucune demande trouvée.</p>
        <a href="<?= APP_URL ?>/citoyen/demande-naissance.php" class="btn btn-primary btn-sm">
          <i class="fas fa-plus me-1"></i>Faire ma première demande
        </a>
      </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table-modern" data-table id="tblDemandes">
          <thead>
            <tr>
              <th>#</th>
              <th>Référence</th>
              <th>Type d'acte</th>
              <th>Date de dépôt</th>
              <th>Agent</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($demandes as $i => $d): ?>
            <tr>
              <td style="color:#adb5bd;font-size:.8rem;"><?= $offset + $i + 1 ?></td>
              <td>
                <code style="font-size:.78rem;color:var(--bleu-primaire);cursor:pointer;"
                      onclick="copyToClipboard('<?= $d['numero_reference'] ?>')"
                      title="Cliquer pour copier">
                  <?= htmlspecialchars($d['numero_reference']) ?>
                </code>
              </td>
              <td>
                <i class="<?= getTypeActeIcon($d['type_acte']) ?> me-2 text-muted"></i>
                <?= getTypeActeLabel($d['type_acte']) ?>
              </td>
              <td style="font-size:.82rem;white-space:nowrap;">
                <i class="fas fa-calendar me-1 text-muted"></i>
                <?= formatDate($d['created_at']) ?>
                <div style="font-size:.72rem;color:#adb5bd;"><?= timeAgo($d['created_at']) ?></div>
              </td>
              <td style="font-size:.82rem;">
                <?php if ($d['agent_nom']): ?>
                  <i class="fas fa-user-tie me-1 text-muted"></i>
                  <?= htmlspecialchars($d['agent_prenom'].' '.$d['agent_nom']) ?>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td><?= getStatutBadge($d['statut']) ?></td>
              <td>
                <div class="d-flex gap-1">
                  <a href="<?= APP_URL ?>/citoyen/detail-demande.php?id=<?= $d['id'] ?>"
                     class="btn btn-icon btn-outline-primary btn-sm"
                     data-bs-toggle="tooltip" title="Voir le dossier">
                    <i class="fas fa-eye"></i>
                  </a>
                  <?php if ($d['statut'] === 'valide' && $d['acte_pdf_path']): ?>
                  <a href="<?= APP_URL ?>/citoyen/telecharger-acte.php?id=<?= $d['id'] ?>"
                     class="btn btn-icon btn-outline-success btn-sm"
                     data-bs-toggle="tooltip" title="Télécharger l'acte PDF">
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
      <div class="d-flex justify-content-center align-items-center gap-2 p-3">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <a href="?page=<?= $p ?>&type=<?= urlencode($filtre_type) ?>&statut=<?= urlencode($filtre_statut) ?>"
             class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include __DIR__.'/../views/partials/footer.php'; ?>
