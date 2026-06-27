<?php
/**
 * Détail d'une demande — Espace Citoyen
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
requireRole('citoyen');
$user   = getCurrentUser();
$userId = $user['id'];

$id = (int)($_GET['id'] ?? 0);
if (!$id) { flash('danger','Demande introuvable.'); redirect('/citoyen/mes-demandes.php'); }

$demande = dbQuery("
    SELECT d.*, u.nom as agent_nom, u.prenom as agent_prenom, u.email as agent_email
    FROM demandes d
    LEFT JOIN users u ON d.agent_id = u.id
    WHERE d.id = ? AND d.user_id = ?
", [$id, $userId])->fetch();

if (!$demande) { flash('danger','Demande introuvable ou accès refusé.'); redirect('/citoyen/mes-demandes.php'); }

// Détails spécifiques
$details = null;
switch ($demande['type_acte']) {
    case 'naissance': $details = dbQuery("SELECT * FROM naissances WHERE demande_id=?",[$id])->fetch(); break;
    case 'deces':     $details = dbQuery("SELECT * FROM deces WHERE demande_id=?",[$id])->fetch(); break;
    case 'mariage':   $details = dbQuery("SELECT * FROM mariages WHERE demande_id=?",[$id])->fetch(); break;
}
$docs = dbQuery("SELECT * FROM documents WHERE demande_id=?",[$id])->fetchAll();

// Marquer les notifications liées comme lues
dbQuery("UPDATE notifications SET lu=1 WHERE user_id=? AND lien LIKE '%mes-demandes%'",[$userId]);

$pageTitle='Détail demande '.$demande['numero_reference']; $pageSection='citoyen';
include __DIR__.'/../views/partials/header.php';
include __DIR__.'/../views/partials/navbar.php';
?>
<div class="dashboard-wrapper">
  <?php include __DIR__.'/../views/citoyen/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-file-alt me-2 text-primary"></i>Dossier <code style="font-size:.8em;"><?= htmlspecialchars($demande['numero_reference']) ?></code></h1>
        <div class="breadcrumb-bar">
          <a href="<?= APP_URL ?>/citoyen/mes-demandes.php">Mes demandes</a> › <span>Détail</span>
        </div>
      </div>
      <div class="d-flex gap-2">
        <?php if ($demande['statut']==='valide' && $demande['acte_pdf_path']): ?>
        <a href="<?= APP_URL ?>/citoyen/telecharger-acte.php?id=<?= $id ?>" class="btn btn-success">
          <i class="fas fa-file-pdf me-2"></i>Télécharger l'acte
        </a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/citoyen/mes-demandes.php" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left me-1"></i>Retour
        </a>
      </div>
    </div>

    <div class="row g-4">

      <!-- Colonne principale -->
      <div class="col-lg-8">

        <!-- Résumé -->
        <div class="content-card mb-4">
          <div class="card-header-title">
            <i class="<?= getTypeActeIcon($demande['type_acte']) ?> text-primary"></i>
            <?= getTypeActeLabel($demande['type_acte']) ?>
            <div class="ms-auto"><?= getStatutBadge($demande['statut']) ?></div>
          </div>
          <div class="row g-2" style="font-size:.88rem;">
            <div class="col-md-6">
              <span class="text-muted">Référence :</span>
              <strong class="ms-2"><?= htmlspecialchars($demande['numero_reference']) ?></strong>
            </div>
            <div class="col-md-6">
              <span class="text-muted">Date de dépôt :</span>
              <strong class="ms-2"><?= formatDateTime($demande['created_at']) ?></strong>
            </div>
            <?php if ($demande['agent_nom']): ?>
            <div class="col-md-6">
              <span class="text-muted">Agent traitant :</span>
              <strong class="ms-2"><?= htmlspecialchars($demande['agent_prenom'].' '.$demande['agent_nom']) ?></strong>
            </div>
            <?php endif; ?>
            <?php if ($demande['date_traitement']): ?>
            <div class="col-md-6">
              <span class="text-muted">Date traitement :</span>
              <strong class="ms-2"><?= formatDateTime($demande['date_traitement']) ?></strong>
            </div>
            <?php endif; ?>
            <?php if ($demande['motif_demande']): ?>
            <div class="col-12 mt-2">
              <span class="text-muted">Motif :</span>
              <span class="ms-2"><?= htmlspecialchars($demande['motif_demande']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($demande['commentaire_agent']): ?>
            <div class="col-12 mt-2 p-2 rounded" style="background:#e3f2fd;">
              <i class="fas fa-comment-alt me-2 text-info"></i>
              <strong>Commentaire de l'agent :</strong>
              <span class="ms-1"><?= htmlspecialchars($demande['commentaire_agent']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($demande['motif_rejet']): ?>
            <div class="col-12 mt-2 p-2 rounded" style="background:#ffebee;">
              <i class="fas fa-exclamation-circle me-2 text-danger"></i>
              <strong>Motif de rejet :</strong>
              <span class="ms-1 text-danger"><?= htmlspecialchars($demande['motif_rejet']) ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Détails de l'acte -->
        <?php if ($details): ?>
        <div class="content-card mb-4">
          <div class="card-header-title">
            <i class="fas fa-info-circle text-primary"></i>Informations détaillées
          </div>

          <?php if ($demande['type_acte']==='naissance'): ?>
          <div class="row g-2" style="font-size:.87rem;">
            <div class="col-md-6"><span class="text-muted">Enfant :</span> <strong class="ms-1"><?= htmlspecialchars($details['prenom_enfant'].' '.$details['nom_enfant']) ?></strong></div>
            <div class="col-md-3"><span class="text-muted">Sexe :</span> <span class="ms-1"><?= $details['sexe']==='M'?'Masculin':'Féminin' ?></span></div>
            <div class="col-md-3"><span class="text-muted">Date naiss. :</span> <span class="ms-1"><?= formatDate($details['date_naissance']) ?></span></div>
            <div class="col-md-6"><span class="text-muted">Lieu :</span> <span class="ms-1"><?= htmlspecialchars($details['lieu_naissance']) ?></span></div>
            <?php if ($details['centre_sante']): ?>
            <div class="col-md-6"><span class="text-muted">Maternité :</span> <span class="ms-1"><?= htmlspecialchars($details['centre_sante']) ?></span></div>
            <?php endif; ?>
            <div class="col-md-6 mt-2"><strong>Père :</strong> <?= htmlspecialchars(($details['prenom_pere'].' '.$details['nom_pere'])??'—') ?></div>
            <div class="col-md-6 mt-2"><strong>Mère :</strong> <?= htmlspecialchars(($details['prenom_mere'].' '.$details['nom_mere'])??'—') ?></div>
            <?php if ($details['numero_acte']): ?>
            <div class="col-12 mt-2"><span class="text-muted">N° Acte :</span> <code class="ms-1"><?= htmlspecialchars($details['numero_acte']) ?></code></div>
            <?php endif; ?>
          </div>

          <?php elseif ($demande['type_acte']==='deces'): ?>
          <div class="row g-2" style="font-size:.87rem;">
            <div class="col-md-6"><span class="text-muted">Défunt :</span> <strong class="ms-1"><?= htmlspecialchars($details['prenom_defunt'].' '.$details['nom_defunt']) ?></strong></div>
            <div class="col-md-3"><span class="text-muted">Sexe :</span> <span class="ms-1"><?= $details['sexe']==='M'?'Masculin':'Féminin' ?></span></div>
            <div class="col-md-3"><span class="text-muted">Date décès :</span> <span class="ms-1"><?= formatDate($details['date_deces']) ?></span></div>
            <div class="col-md-6"><span class="text-muted">Lieu :</span> <span class="ms-1"><?= htmlspecialchars($details['lieu_deces']) ?></span></div>
            <?php if ($details['cause_deces']): ?>
            <div class="col-md-6"><span class="text-muted">Cause :</span> <span class="ms-1"><?= htmlspecialchars($details['cause_deces']) ?></span></div>
            <?php endif; ?>
          </div>

          <?php elseif ($demande['type_acte']==='mariage'): ?>
          <div class="row g-2" style="font-size:.87rem;">
            <div class="col-md-6"><span class="text-muted">Époux :</span> <strong class="ms-1"><?= htmlspecialchars($details['prenom_epoux'].' '.$details['nom_epoux']) ?></strong></div>
            <div class="col-md-6"><span class="text-muted">Épouse :</span> <strong class="ms-1"><?= htmlspecialchars($details['prenom_epouse'].' '.$details['nom_epouse']) ?></strong></div>
            <div class="col-md-4"><span class="text-muted">Date mariage :</span> <span class="ms-1"><?= formatDate($details['date_mariage']) ?></span></div>
            <div class="col-md-8"><span class="text-muted">Lieu :</span> <span class="ms-1"><?= htmlspecialchars($details['lieu_mariage']) ?></span></div>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Documents joints -->
        <?php if (!empty($docs)): ?>
        <div class="content-card">
          <div class="card-header-title">
            <i class="fas fa-paperclip text-primary"></i>Pièces jointes (<?= count($docs) ?>)
          </div>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($docs as $doc): ?>
            <div class="d-flex align-items-center gap-3 p-2 rounded" style="background:var(--gris-clair);">
              <i class="fas fa-file-<?= $doc['mime_type']==='application/pdf'?'pdf text-danger':'image text-primary' ?> fa-lg"></i>
              <div class="flex-1">
                <div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($doc['nom_original']) ?></div>
                <div style="font-size:.75rem;color:#adb5bd;"><?= round($doc['taille']/1024,1) ?> Ko · <?= formatDate($doc['created_at']) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Timeline -->
      <div class="col-lg-4">
        <div class="content-card">
          <div class="card-header-title">
            <i class="fas fa-history text-primary"></i>Suivi du dossier
          </div>
          <div class="tracking-timeline">
            <!-- Étape 1 : Soumis -->
            <div class="timeline-item">
              <div class="timeline-dot done"><i class="fas fa-check"></i></div>
              <div class="timeline-content done">
                <div class="timeline-label">Demande soumise</div>
                <div class="timeline-date"><?= formatDateTime($demande['created_at']) ?></div>
              </div>
            </div>
            <!-- Étape 2 : En cours -->
            <div class="timeline-item">
              <div class="timeline-dot <?= in_array($demande['statut'],['en_cours','valide','rejete'])?'done':'' ?>">
                <?php if (in_array($demande['statut'],['en_cours','valide','rejete'])): ?>
                <i class="fas fa-check"></i>
                <?php endif; ?>
              </div>
              <div class="timeline-content <?= $demande['statut']==='en_cours'?'current':'' ?> <?= in_array($demande['statut'],['valide','rejete'])?'done':'' ?>">
                <div class="timeline-label">Prise en charge</div>
                <div class="timeline-date">
                  <?= $demande['date_traitement'] ? formatDateTime($demande['date_traitement']) : 'En attente d\'un agent' ?>
                </div>
                <?php if ($demande['agent_nom']): ?>
                <div class="timeline-date mt-1">
                  <i class="fas fa-user-tie me-1"></i><?= htmlspecialchars($demande['agent_prenom'].' '.$demande['agent_nom']) ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
            <!-- Étape 3 : Validation ou Rejet -->
            <div class="timeline-item">
              <?php
              $isDone   = in_array($demande['statut'],['valide','rejete']);
              $isValide = $demande['statut']==='valide';
              $dotClass = $isDone ? ($isValide?'done':'reject') : '';
              ?>
              <div class="timeline-dot <?= $dotClass ?>">
                <?php if ($isDone): ?><i class="fas fa-<?= $isValide?'check':'times' ?>"></i><?php endif; ?>
              </div>
              <div class="timeline-content <?= $dotClass ?>">
                <div class="timeline-label">
                  <?php if ($demande['statut']==='valide'): ?>
                    <span class="text-success"><i class="fas fa-check-circle me-1"></i>Demande validée</span>
                  <?php elseif ($demande['statut']==='rejete'): ?>
                    <span class="text-danger"><i class="fas fa-times-circle me-1"></i>Demande rejetée</span>
                  <?php else: ?>
                    Décision finale
                  <?php endif; ?>
                </div>
                <div class="timeline-date">
                  <?= $demande['date_validation'] ? formatDateTime($demande['date_validation']) : 'En attente de décision' ?>
                </div>
              </div>
            </div>
            <!-- Étape 4 : PDF disponible -->
            <?php if ($demande['statut']==='valide'): ?>
            <div class="timeline-item">
              <div class="timeline-dot <?= $demande['acte_pdf_path']?'done':'' ?>">
                <?php if ($demande['acte_pdf_path']): ?><i class="fas fa-check"></i><?php endif; ?>
              </div>
              <div class="timeline-content <?= $demande['acte_pdf_path']?'done':'current' ?>">
                <div class="timeline-label">Acte PDF disponible</div>
                <div class="timeline-date">
                  <?php if ($demande['acte_pdf_path']): ?>
                    <a href="<?= APP_URL ?>/citoyen/telecharger-acte.php?id=<?= $id ?>" class="btn btn-success btn-sm mt-1">
                      <i class="fas fa-download me-1"></i>Télécharger
                    </a>
                  <?php else: ?>
                    Génération en cours…
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__.'/../views/partials/footer.php'; ?>
