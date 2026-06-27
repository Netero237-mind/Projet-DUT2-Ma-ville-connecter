<?php
/**
 * Gestion des utilisateurs — Admin
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
requireRole('admin');
$user = getCurrentUser();

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { flash('danger','Token invalide.'); redirect('/admin/utilisateurs.php'); }
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);

    if ($action === 'toggle_statut' && $uid) {
        $current = dbQuery("SELECT statut FROM users WHERE id=?",[$uid])->fetch()['statut'];
        $new     = $current === 'actif' ? 'suspendu' : 'actif';
        dbQuery("UPDATE users SET statut=? WHERE id=?"   ,[$new,$uid]);
        logAction($user['id'],'modif_statut',"Statut user $uid → $new",'users',$uid);
        flash('success',"Statut mis à jour : $new.");
    } elseif ($action === 'delete' && $uid && $uid !== $user['id']) {
        dbQuery("DELETE FROM users WHERE id=?",[$uid]);
        logAction($user['id'],'suppression_user',"Suppression user $uid",'users',$uid);
        flash('warning','Utilisateur supprimé.');
    } elseif ($action === 'ajouter_agent') {
        $data = sanitizeAll($_POST);
        if (!empty($data['email']) && !empty($data['nom']) && !empty($data['prenom']) && !empty($data['password'])) {
            $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $db   = db();
            $db->beginTransaction();
            $db->prepare("INSERT INTO users (role_id,nom,prenom,email,password,telephone,statut,email_verifie) VALUES(2,?,?,?,?,?,'actif',1)")
               ->execute([strtoupper($data['nom']),ucwords(strtolower($data['prenom'])),$data['email'],$hash,$data['telephone']??'']);
            $uid = $db->lastInsertId();
            $matricule = 'AGT-'.date('Y').'-'.str_pad($uid,3,'0',STR_PAD_LEFT);
            $db->prepare("INSERT INTO agents (user_id,matricule,departement,poste) VALUES(?,?,'État Civil',?)")
               ->execute([$uid,$matricule,$data['poste']??'Agent de traitement']);
            $db->commit();
            logAction($user['id'],'creation_agent',"Nouvel agent $uid",'users',$uid);
            flash('success','Agent créé avec succès. Matricule : '.$matricule);
        } else {
            flash('danger','Champs obligatoires manquants.');
        }
    }
    redirect('/admin/utilisateurs.php');
}

// Filtres
$role   = $_GET['role']   ?? '';
$search = $_GET['search'] ?? '';
$page   = max(1,(int)($_GET['page']??1));
$perPage = 15; $offset = ($page-1)*$perPage;

$where  = "WHERE 1=1";
$params = [];
if ($role)   { $where .= " AND r.nom=?"; $params[] = $role; }
if ($search) { $where .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)"; $s="%$search%"; $params[]=$s;$params[]=$s;$params[]=$s; }

$total = dbQuery("SELECT COUNT(*) as c FROM users u JOIN roles r ON u.role_id=r.id $where",$params)->fetch()['c'];
$pages = max(1,ceil($total/$perPage));
$params[] = $perPage; $params[] = $offset;

$users = dbQuery("
    SELECT u.*, r.nom as role_nom FROM users u
    JOIN roles r ON u.role_id=r.id
    $where ORDER BY u.created_at DESC LIMIT ? OFFSET ?
",$params)->fetchAll();

$pageTitle='Gestion des utilisateurs'; $pageSection='admin';
include __DIR__.'/../views/partials/header.php';
include __DIR__.'/../views/partials/navbar.php';
?>
<div class="dashboard-wrapper">
  <!-- Sidebar admin inline -->
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
        <li class="nav-item"><a class="nav-link active" href="<?= APP_URL ?>/admin/utilisateurs.php"><span class="nav-icon"><i class="fas fa-users"></i></span>Utilisateurs</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/demandes.php"><span class="nav-icon"><i class="fas fa-folder-open"></i></span>Demandes</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/parametres.php"><span class="nav-icon"><i class="fas fa-cog"></i></span>Paramètres</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/journal.php"><span class="nav-icon"><i class="fas fa-history"></i></span>Journal</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/auth/logout.php" style="color:rgba(255,100,100,.75)!important;"><span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>Déconnexion</a></li>
      </ul>
    </nav>
  </aside>

  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-users me-2 text-primary"></i>Gestion des utilisateurs</h1>
        <div class="breadcrumb-bar"><a href="<?= APP_URL ?>/admin/dashboard.php">Admin</a> › <span>Utilisateurs</span></div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgent">
          <i class="fas fa-user-plus me-1"></i>Ajouter un agent
        </button>
      </div>
    </div>

    <!-- Filtres -->
    <div class="content-card mb-4">
      <form method="GET" class="row g-2">
        <div class="col-md-4">
          <input type="text" class="form-control form-control-sm" name="search"
                 value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Rechercher nom, prénom, email…">
        </div>
        <div class="col-md-3">
          <select class="form-select form-select-sm" name="role">
            <option value="">Tous les rôles</option>
            <option value="admin"   <?= $role==='admin'?'selected':'' ?>>Administrateurs</option>
            <option value="agent"   <?= $role==='agent'?'selected':'' ?>>Agents</option>
            <option value="citoyen" <?= $role==='citoyen'?'selected':'' ?>>Citoyens</option>
          </select>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Filtrer</button>
          <a href="<?= APP_URL ?>/admin/utilisateurs.php" class="btn btn-outline-secondary btn-sm ms-1"><i class="fas fa-times"></i></a>
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="table-card">
      <div class="table-card-header">
        <h5><i class="fas fa-list me-2 text-primary"></i><?= $total ?> utilisateur<?= $total>1?'s':'' ?></h5>
        <span style="font-size:.82rem;color:#718096;">Page <?= $page ?>/<?= $pages ?></span>
      </div>
      <div class="table-responsive">
        <table class="table-modern">
          <thead><tr><th>#</th><th>Utilisateur</th><th>Email</th><th>Téléphone</th><th>Rôle</th><th>Statut</th><th>Inscription</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($users as $i => $u):
              $roleColors = ['admin'=>'bg-danger','agent'=>'bg-warning text-dark','citoyen'=>'bg-primary'];
              $rc = $roleColors[$u['role_nom']] ?? 'bg-secondary';
            ?>
            <tr>
              <td style="color:#adb5bd;font-size:.8rem;"><?= $offset+$i+1 ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div style="width:34px;height:34px;border-radius:50%;background:var(--gris-moyen);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:var(--bleu-primaire);flex-shrink:0;">
                    <?= strtoupper(substr($u['prenom'],0,1).substr($u['nom'],0,1)) ?>
                  </div>
                  <div>
                    <div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></div>
                    <?php if ($u['derniere_connexion']): ?>
                    <div style="font-size:.72rem;color:#adb5bd;">Dernière co. : <?= timeAgo($u['derniere_connexion']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td style="font-size:.83rem;"><?= htmlspecialchars($u['email']) ?></td>
              <td style="font-size:.82rem;"><?= htmlspecialchars($u['telephone']??'—') ?></td>
              <td><span class="badge <?= $rc ?>"><?= ucfirst($u['role_nom']) ?></span></td>
              <td>
                <span class="badge <?= $u['statut']==='actif'?'bg-success':($u['statut']==='suspendu'?'bg-warning text-dark':'bg-secondary') ?>">
                  <?= ucfirst($u['statut']) ?>
                </span>
              </td>
              <td style="font-size:.78rem;color:#718096;"><?= formatDate($u['created_at']) ?></td>
              <td>
                <div class="d-flex gap-1">
                  <?php if ($u['id'] !== $user['id']): ?>
                  <form method="POST" class="d-inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="toggle_statut">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-icon btn-sm btn-outline-<?= $u['statut']==='actif'?'warning':'success' ?>"
                            data-bs-toggle="tooltip" title="<?= $u['statut']==='actif'?'Suspendre':'Activer' ?>"
                            data-confirm="<?= $u['statut']==='actif'?'Suspendre':'Activer' ?> cet utilisateur ?">
                      <i class="fas fa-<?= $u['statut']==='actif'?'ban':'check' ?>"></i>
                    </button>
                  </form>
                  <?php if ($u['role_nom'] !== 'admin'): ?>
                  <form method="POST" class="d-inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-icon btn-sm btn-outline-danger"
                            data-bs-toggle="tooltip" title="Supprimer"
                            data-confirm="Supprimer définitivement cet utilisateur et toutes ses données ?">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                  <?php endif; ?>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($pages > 1): ?>
      <div class="d-flex justify-content-center gap-2 p-3">
        <?php for ($p=1;$p<=$pages;$p++): ?>
          <a href="?page=<?= $p ?>&role=<?= urlencode($role) ?>&search=<?= urlencode($search) ?>"
             class="btn btn-sm <?= $p===$page?'btn-primary':'btn-outline-secondary' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<!-- Modal Ajouter Agent -->
<div class="modal fade" id="modalAgent" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--bleu-primaire);color:#fff;">
        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Ajouter un agent municipal</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="ajouter_agent">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Nom *</label><input type="text" class="form-control" name="nom" required placeholder="NOM" style="text-transform:uppercase;"></div>
            <div class="col-md-6"><label class="form-label">Prénom *</label><input type="text" class="form-control" name="prenom" required placeholder="Prénom(s)"></div>
            <div class="col-12"><label class="form-label">Email *</label><input type="email" class="form-control" name="email" required placeholder="email@mairie.cm"></div>
            <div class="col-md-6"><label class="form-label">Téléphone</label><input type="tel" class="form-control" name="telephone" placeholder="+237 6XX XXX XXX"></div>
            <div class="col-md-6"><label class="form-label">Poste</label><input type="text" class="form-control" name="poste" value="Agent de traitement"></div>
            <div class="col-12"><label class="form-label">Mot de passe temporaire *</label><input type="password" class="form-control" name="password" required minlength="8" placeholder="Min. 8 caractères"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Créer l'agent</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__.'/../views/partials/footer.php'; ?>
