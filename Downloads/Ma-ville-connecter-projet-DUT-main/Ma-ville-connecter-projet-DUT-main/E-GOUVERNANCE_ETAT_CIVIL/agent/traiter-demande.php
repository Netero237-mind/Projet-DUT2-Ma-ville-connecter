<?php
/**
 * Traitement d'une demande — Espace Agent
 * Valider, rejeter, commenter, générer l'acte PDF
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
requireRole(['agent','admin']);
$user   = getCurrentUser();
$agentId = $user['id'];

$id = (int)($_GET['id'] ?? 0);
if (!$id) { flash('danger','Demande introuvable.'); redirect('/agent/demandes.php'); }

$demande = dbQuery("
    SELECT d.*, u.nom as citoyen_nom, u.prenom as citoyen_prenom, u.email as citoyen_email, u.telephone as citoyen_tel
    FROM demandes d JOIN users u ON d.user_id=u.id
    WHERE d.id=?
", [$id])->fetch();

if (!$demande) { flash('danger','Demande introuvable.'); redirect('/agent/demandes.php'); }

// Détails spécifiques
$details = null;
switch ($demande['type_acte']) {
    case 'naissance': $details = dbQuery("SELECT * FROM naissances WHERE demande_id=?",[$id])->fetch(); break;
    case 'deces':     $details = dbQuery("SELECT * FROM deces WHERE demande_id=?",[$id])->fetch(); break;
    case 'mariage':   $details = dbQuery("SELECT * FROM mariages WHERE demande_id=?",[$id])->fetch(); break;
}
$docs = dbQuery("SELECT * FROM documents WHERE demande_id=?",[$id])->fetchAll();

// ---- TRAITEMENT POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flash('danger','Token invalide.'); redirect('/agent/traiter-demande.php?id='.$id);
    }
    $action    = $_POST['action_agent'] ?? '';
    $commentaire = sanitize($_POST['commentaire'] ?? '');
    $motifRejet  = sanitize($_POST['motif_rejet'] ?? '');

    try {
        $db = db();
        $db->beginTransaction();

        if ($action === 'prendre_en_charge' && $demande['statut'] === 'soumis') {
            $db->prepare("UPDATE demandes SET statut='en_cours', agent_id=?, date_traitement=NOW() WHERE id=?")
               ->execute([$agentId, $id]);
            logAction($agentId,'prise_en_charge',"Dossier $id pris en charge",'demandes',$id);
            addNotification($demande['user_id'],'Dossier pris en charge',"Votre demande {$demande['numero_reference']} est maintenant en cours de traitement.",'info','/citoyen/mes-demandes.php');
            flash('success','Dossier pris en charge avec succès.');

        } elseif ($action === 'valider') {
            // Générer numéro d'acte
            $numeroActe = generateNumeroActe($demande['type_acte']);

            // Mettre à jour le numéro dans la table spécifique
            $tableMap = ['naissance'=>'naissances','deces'=>'deces','mariage'=>'mariages'];
            if (isset($tableMap[$demande['type_acte']])) {
                $db->prepare("UPDATE {$tableMap[$demande['type_acte']]} SET numero_acte=? WHERE demande_id=?")
                   ->execute([$numeroActe, $id]);
            }

            // Générer le PDF (utilisation de la classe PDFGenerator)
            $pdfPath = generateActePDF($demande, $details, $numeroActe);

            $db->prepare("UPDATE demandes SET statut='valide', agent_id=?, commentaire_agent=?, date_validation=NOW(), acte_pdf_path=? WHERE id=?")
               ->execute([$agentId, $commentaire, $pdfPath, $id]);

            logAction($agentId,'validation',"Validation dossier {$demande['numero_reference']}",'demandes',$id);
            addNotification($demande['user_id'],'✅ Demande validée !',"Votre demande {$demande['numero_reference']} a été validée. Votre acte est disponible en téléchargement.",'succes','/citoyen/mes-demandes.php');
            flash('success',"✅ Demande validée avec succès ! Acte n° $numeroActe généré.");

        } elseif ($action === 'rejeter') {
            if (empty($motifRejet)) {
                flash('danger','Le motif de rejet est obligatoire.'); redirect('/agent/traiter-demande.php?id='.$id);
            }
            $db->prepare("UPDATE demandes SET statut='rejete', agent_id=?, motif_rejet=?, commentaire_agent=?, date_validation=NOW() WHERE id=?")
               ->execute([$agentId, $motifRejet, $commentaire, $id]);
            logAction($agentId,'rejet',"Rejet dossier {$demande['numero_reference']}",'demandes',$id);
            addNotification($demande['user_id'],'❌ Demande rejetée',"Votre demande {$demande['numero_reference']} a été rejetée. Motif : $motifRejet",'erreur','/citoyen/mes-demandes.php');
            flash('warning','Demande rejetée. Le citoyen a été notifié.');

        } elseif ($action === 'commenter') {
            $db->prepare("UPDATE demandes SET commentaire_agent=? WHERE id=?")
               ->execute([$commentaire, $id]);
            flash('success','Commentaire enregistré.');
        }

        $db->commit();
        redirect('/agent/traiter-demande.php?id='.$id);

    } catch (Exception $e) {
        $db->rollBack();
        error_log('[TraiterDemande] '.$e->getMessage());
        flash('danger','Erreur lors du traitement.');
        redirect('/agent/traiter-demande.php?id='.$id);
    }
}

/**
 * Génère l'acte PDF (version HTML convertie en PDF simulé)
 * En production : utiliser FPDF, TCPDF ou DomPDF
 */
function generateActePDF(array $demande, ?array $details, string $numeroActe): string
{
    $filename  = 'acte_' . $demande['numero_reference'] . '_' . date('Ymd') . '.html';
    $outputDir = UPLOAD_PATH . 'documents/actes/';
    if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

    $mairie  = getSystemParam('nom_mairie','Mairie');
    $ville   = getSystemParam('ville','Douala');
    $date    = date('d/m/Y');

    $content = generateActeHTML($demande, $details, $numeroActe, $mairie, $ville, $date);

    file_put_contents($outputDir . $filename, $content);
    return 'uploads/documents/actes/' . $filename;
}

function generateActeHTML(array $d, ?array $det, string $num, string $mairie, string $ville, string $date): string
{
    $type  = getTypeActeLabel($d['type_acte']);
    $html  = "<!DOCTYPE html><html lang='fr'><head><meta charset='UTF-8'>
<style>
  body{font-family:'Times New Roman',serif;margin:0;padding:40px;color:#000;}
  .header{text-align:center;border-bottom:3px double #003f8a;padding-bottom:15px;margin-bottom:20px;}
  .header h1{font-size:14pt;margin:5px 0;text-transform:uppercase;color:#003f8a;}
  .header h2{font-size:18pt;font-weight:bold;text-transform:uppercase;}
  .acte-body{line-height:1.8;font-size:11pt;text-align:justify;}
  .signature{margin-top:40px;display:flex;justify-content:space-between;}
  .numero{font-size:10pt;color:#555;margin-bottom:20px;}
  table{width:100%;border-collapse:collapse;margin:15px 0;}
  td{padding:6px 10px;border:1px solid #ccc;font-size:10pt;}
  .label{background:#f5f5f5;font-weight:bold;width:35%;}
  .footer{margin-top:30px;padding-top:10px;border-top:1px solid #ccc;font-size:8pt;color:#666;text-align:center;}
  .watermark{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-45deg);font-size:60pt;color:rgba(0,63,138,.05);pointer-events:none;}
</style>
</head><body>
<div class='watermark'>OFFICIEL</div>
<div class='header'>
  <h1>République du Cameroun<br>Paix — Travail — Patrie</h1>
  <h1>$mairie</h1>
  <h2>Acte de $type</h2>
</div>
<div class='numero'>N° $num — Délivré le $date à $ville</div>
<div class='acte-body'>";

    if ($d['type_acte']==='naissance' && $det) {
        $enfant = htmlspecialchars(($det['prenom_enfant']??'').' '.($det['nom_enfant']??''));
        $datNai = formatDate($det['date_naissance']??'');
        $html .= "<p>Nous, Officier de l'État Civil de la $mairie, certifions que :</p>
<table>
  <tr><td class='label'>Nom et Prénom(s)</td><td>$enfant</td></tr>
  <tr><td class='label'>Sexe</td><td>".($det['sexe']==='M'?'Masculin':'Féminin')."</td></tr>
  <tr><td class='label'>Date de naissance</td><td>$datNai</td></tr>
  <tr><td class='label'>Heure de naissance</td><td>".htmlspecialchars($det['heure_naissance']??'Non précisée')."</td></tr>
  <tr><td class='label'>Lieu de naissance</td><td>".htmlspecialchars($det['lieu_naissance']??'')."</td></tr>
  <tr><td class='label'>Père</td><td>".htmlspecialchars(($det['prenom_pere']??'').' '.($det['nom_pere']??''))."</td></tr>
  <tr><td class='label'>Mère</td><td>".htmlspecialchars(($det['prenom_mere']??'').' '.($det['nom_mere']??''))."</td></tr>
</table>";
    } elseif ($d['type_acte']==='deces' && $det) {
        $defunt = htmlspecialchars(($det['prenom_defunt']??'').' '.($det['nom_defunt']??''));
        $html .= "<p>Nous, Officier de l'État Civil de la $mairie, certifions le décès de :</p>
<table>
  <tr><td class='label'>Nom et Prénom(s)</td><td>$defunt</td></tr>
  <tr><td class='label'>Sexe</td><td>".($det['sexe']==='M'?'Masculin':'Féminin')."</td></tr>
  <tr><td class='label'>Né(e) le</td><td>".formatDate($det['date_naissance_defunt']??'')."</td></tr>
  <tr><td class='label'>Lieu de naissance</td><td>".htmlspecialchars($det['lieu_naissance_defunt']??'')."</td></tr>
  <tr><td class='label'>Date du décès</td><td>".formatDate($det['date_deces']??'')."</td></tr>
  <tr><td class='label'>Lieu du décès</td><td>".htmlspecialchars($det['lieu_deces']??'')."</td></tr>
</table>";
    } elseif ($d['type_acte']==='mariage' && $det) {
        $html .= "<p>Nous, Officier de l'État Civil de la $mairie, certifions le mariage civil de :</p>
<table>
  <tr><td class='label'>Époux</td><td>".htmlspecialchars(($det['prenom_epoux']??'').' '.($det['nom_epoux']??''))."</td></tr>
  <tr><td class='label'>Épouse</td><td>".htmlspecialchars(($det['prenom_epouse']??'').' '.($det['nom_epouse']??''))."</td></tr>
  <tr><td class='label'>Date du mariage</td><td>".formatDate($det['date_mariage']??'')."</td></tr>
  <tr><td class='label'>Lieu du mariage</td><td>".htmlspecialchars($det['lieu_mariage']??'')."</td></tr>
  <tr><td class='label'>Régime matrimonial</td><td>".htmlspecialchars(str_replace('_',' ',$det['regime_matrimonial']??''))."</td></tr>
</table>";
    }

    $html .= "<p>Délivré pour valoir ce que de droit.</p></div>
<div class='signature'>
  <div><p>Le Déclarant</p><br><br>Signature : ___________________</div>
  <div style='text-align:center'><p>$mairie, le $date</p><br><p><strong>L'Officier de l'État Civil</strong></p><br><br>Cachet &amp; Signature</div>
</div>
<div class='footer'>
  Acte n° $num — $mairie — Ce document est officiel et certifié conforme.<br>
  Toute falsification est passible de poursuites judiciaires.
</div>
</body></html>";

    return $html;
}

$pageTitle='Traiter la demande '.$demande['numero_reference']; $pageSection='agent';
include __DIR__.'/../views/partials/header.php';
include __DIR__.'/../views/partials/navbar.php';
?>
<div class="dashboard-wrapper">
  <!-- Sidebar agent réutilisée -->
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
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/auth/logout.php" style="color:rgba(255,100,100,.75)!important;"><span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>Déconnexion</a></li>
      </ul>
    </nav>
  </aside>

  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-edit me-2 text-primary"></i>Traitement du dossier</h1>
        <div class="breadcrumb-bar">
          <a href="<?= APP_URL ?>/agent/demandes.php">Demandes</a> ›
          <span><?= htmlspecialchars($demande['numero_reference']) ?></span>
        </div>
      </div>
      <div class="d-flex gap-2">
        <div class="badge <?= $demande['priorite']==='urgente'?'bg-danger':'bg-secondary' ?> align-self-center">
          <?= $demande['priorite']==='urgente'?'🔴 Urgent':'⚪ Normal' ?>
        </div>
        <?= getStatutBadge($demande['statut']) ?>
        <a href="<?= APP_URL ?>/agent/demandes.php" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-arrow-left me-1"></i>Retour
        </a>
      </div>
    </div>

    <div class="row g-4">

      <!-- Colonne principale -->
      <div class="col-lg-8">

        <!-- Identité citoyen -->
        <div class="content-card mb-4">
          <div class="card-header-title"><i class="fas fa-user text-primary"></i>Citoyen demandeur</div>
          <div class="row g-2" style="font-size:.88rem;">
            <div class="col-md-4"><span class="text-muted">Nom :</span> <strong class="ms-1"><?= htmlspecialchars($demande['citoyen_prenom'].' '.$demande['citoyen_nom']) ?></strong></div>
            <div class="col-md-4"><span class="text-muted">Email :</span> <span class="ms-1"><?= htmlspecialchars($demande['citoyen_email']) ?></span></div>
            <div class="col-md-4"><span class="text-muted">Tél :</span> <span class="ms-1"><?= htmlspecialchars($demande['citoyen_tel']??'—') ?></span></div>
            <div class="col-md-4"><span class="text-muted">Dépôt :</span> <span class="ms-1"><?= formatDateTime($demande['created_at']) ?></span></div>
            <div class="col-md-4"><span class="text-muted">Type :</span> <span class="ms-1"><?= getTypeActeLabel($demande['type_acte']) ?></span></div>
          </div>
        </div>

        <!-- Détails de l'acte -->
        <?php if ($details): ?>
        <div class="content-card mb-4">
          <div class="card-header-title">
            <i class="<?= getTypeActeIcon($demande['type_acte']) ?> text-primary"></i>
            Informations de l'acte
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-bordered" style="font-size:.87rem;">
              <tbody>
                <?php
                $rows = [];
                if ($demande['type_acte']==='naissance' && $details) {
                    $rows = [
                        ['Enfant',''.($details['prenom_enfant']??'').' '.($details['nom_enfant']??'')],
                        ['Sexe',$details['sexe']==='M'?'Masculin':'Féminin'],
                        ['Date de naissance',formatDate($details['date_naissance']??'')],
                        ['Heure',$details['heure_naissance']??'—'],
                        ['Lieu de naissance',$details['lieu_naissance']??'—'],
                        ['Maternité',$details['centre_sante']??'—'],
                        ['Père',($details['prenom_pere']??'').' '.($details['nom_pere']??'')],
                        ['Mère',($details['prenom_mere']??'').' '.($details['nom_mere']??'')],
                        ['Déclarant',$details['nom_declarant']??'—'],
                    ];
                } elseif ($demande['type_acte']==='deces' && $details) {
                    $rows = [
                        ['Défunt',($details['prenom_defunt']??'').' '.($details['nom_defunt']??'')],
                        ['Sexe',$details['sexe']==='M'?'Masculin':'Féminin'],
                        ['Né(e) le',formatDate($details['date_naissance_defunt']??'')],
                        ['Date du décès',formatDate($details['date_deces']??'')],
                        ['Heure',$details['heure_deces']??'—'],
                        ['Lieu du décès',$details['lieu_deces']??'—'],
                        ['Cause',$details['cause_deces']??'—'],
                        ['Déclarant',$details['nom_declarant']??'—'],
                    ];
                } elseif ($demande['type_acte']==='mariage' && $details) {
                    $rows = [
                        ['Époux',($details['prenom_epoux']??'').' '.($details['nom_epoux']??'')],
                        ['Épouse',($details['prenom_epouse']??'').' '.($details['nom_epouse']??'')],
                        ['Date du mariage',formatDate($details['date_mariage']??'')],
                        ['Lieu',$details['lieu_mariage']??'—'],
                        ['Régime matrimonial',str_replace('_',' ',$details['regime_matrimonial']??'')],
                        ['Témoin 1',$details['temoin1_nom']??'—'],
                        ['Témoin 2',$details['temoin2_nom']??'—'],
                    ];
                }
                foreach ($rows as [$label, $val]):
                ?>
                <tr>
                  <th class="bg-light" style="width:35%;font-weight:600;"><?= htmlspecialchars($label) ?></th>
                  <td><?= htmlspecialchars($val) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

        <!-- Pièces jointes -->
        <?php if (!empty($docs)): ?>
        <div class="content-card mb-4">
          <div class="card-header-title"><i class="fas fa-paperclip text-primary"></i>Pièces jointes (<?= count($docs) ?>)</div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($docs as $doc): ?>
            <div class="p-2 rounded d-flex align-items-center gap-2" style="background:var(--gris-clair);min-width:200px;">
              <i class="fas fa-file-<?= $doc['mime_type']==='application/pdf'?'pdf text-danger':'image text-primary' ?>"></i>
              <div>
                <div style="font-size:.82rem;font-weight:600;"><?= htmlspecialchars($doc['nom_original']) ?></div>
                <div style="font-size:.72rem;color:#adb5bd;"><?= round($doc['taille']/1024,1) ?> Ko</div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Zone d'actions -->
        <?php if (in_array($demande['statut'],['soumis','en_cours'])): ?>
        <div class="form-card">
          <div class="form-card-header"><h4><i class="fas fa-gavel me-2 text-primary"></i>Décision de l'agent</h4></div>

          <!-- Prendre en charge -->
          <?php if ($demande['statut']==='soumis'): ?>
          <div class="alert alert-warning d-flex gap-2 mb-3">
            <i class="fas fa-exclamation-triangle mt-1"></i>
            <div style="font-size:.87rem;">Cette demande n'a pas encore été prise en charge. Cliquez sur <strong>"Prendre en charge"</strong> pour commencer le traitement.</div>
          </div>
          <form method="POST"><?= csrfField() ?>
            <input type="hidden" name="action_agent" value="prendre_en_charge">
            <button type="submit" class="btn btn-warning w-100 mb-3">
              <i class="fas fa-hand-point-right me-2"></i>Prendre ce dossier en charge
            </button>
          </form>
          <?php endif; ?>

          <!-- Commentaire -->
          <form method="POST" class="mb-3"><?= csrfField() ?>
            <input type="hidden" name="action_agent" value="commenter">
            <label class="form-label">Commentaire interne</label>
            <textarea class="form-control mb-2" name="commentaire" rows="2"
                      placeholder="Note interne ou remarque sur le dossier…"><?= htmlspecialchars($demande['commentaire_agent']??'') ?></textarea>
            <button type="submit" class="btn btn-outline-secondary btn-sm">
              <i class="fas fa-save me-1"></i>Enregistrer la note
            </button>
          </form>

          <?php if ($demande['statut']==='en_cours'): ?>
          <hr>
          <div class="row g-3">
            <!-- VALIDER -->
            <div class="col-md-6">
              <div class="p-3 rounded" style="background:#e8f5e9;border:1px solid #c8e6c9;">
                <h6 class="text-success mb-2"><i class="fas fa-check-circle me-1"></i>Valider la demande</h6>
                <p style="font-size:.82rem;color:#388e3c;">L'acte sera généré automatiquement et le citoyen sera notifié.</p>
                <form method="POST"><?= csrfField() ?>
                  <input type="hidden" name="action_agent" value="valider">
                  <textarea class="form-control form-control-sm mb-2" name="commentaire" rows="2"
                            placeholder="Commentaire de validation (optionnel)"></textarea>
                  <button type="submit" class="btn btn-success w-100"
                          data-confirm="Confirmer la validation de cette demande ?">
                    <i class="fas fa-check me-2"></i>Valider et générer l'acte
                  </button>
                </form>
              </div>
            </div>
            <!-- REJETER -->
            <div class="col-md-6">
              <div class="p-3 rounded" style="background:#ffebee;border:1px solid #ffcdd2;">
                <h6 class="text-danger mb-2"><i class="fas fa-times-circle me-1"></i>Rejeter la demande</h6>
                <p style="font-size:.82rem;color:#c62828;">Le citoyen sera notifié avec le motif du rejet.</p>
                <form method="POST"><?= csrfField() ?>
                  <input type="hidden" name="action_agent" value="rejeter">
                  <textarea class="form-control form-control-sm mb-2" name="motif_rejet" rows="2" required
                            placeholder="Motif obligatoire : pièces manquantes, informations incorrectes…"></textarea>
                  <button type="submit" class="btn btn-danger w-100"
                          data-confirm="Confirmer le rejet de cette demande ?">
                    <i class="fas fa-times me-2"></i>Rejeter la demande
                  </button>
                </form>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Demande déjà traitée -->
        <div class="content-card">
          <div class="card-header-title">
            <?php if ($demande['statut']==='valide'): ?>
            <i class="fas fa-check-circle text-success"></i> Demande validée
            <?php else: ?>
            <i class="fas fa-times-circle text-danger"></i> Demande rejetée
            <?php endif; ?>
          </div>
          <p style="font-size:.88rem;">Cette demande a été traitée le <?= formatDateTime($demande['date_validation']??'') ?>.</p>
          <?php if ($demande['statut']==='valide' && $demande['acte_pdf_path']): ?>
          <a href="<?= APP_URL ?>/<?= $demande['acte_pdf_path'] ?>" target="_blank" class="btn btn-success">
            <i class="fas fa-file-pdf me-2"></i>Voir l'acte généré
          </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Colonne droite : infos demande -->
      <div class="col-lg-4">
        <div class="content-card">
          <div class="card-header-title"><i class="fas fa-info-circle text-primary"></i>Récapitulatif</div>
          <div style="font-size:.85rem;" class="d-flex flex-column gap-2">
            <div><span class="text-muted">Référence :</span> <code class="ms-1"><?= htmlspecialchars($demande['numero_reference']) ?></code></div>
            <div><span class="text-muted">Type :</span> <span class="ms-1"><?= getTypeActeLabel($demande['type_acte']) ?></span></div>
            <div><span class="text-muted">Statut :</span> <span class="ms-1"><?= getStatutBadge($demande['statut']) ?></span></div>
            <div><span class="text-muted">Déposé :</span> <span class="ms-1"><?= formatDate($demande['created_at']) ?></span></div>
            <?php if ($demande['commentaire_agent']): ?>
            <div class="p-2 rounded mt-1" style="background:#e3f2fd;">
              <strong style="font-size:.78rem;">Note agent :</strong>
              <p style="margin:.2rem 0 0;font-size:.82rem;"><?= htmlspecialchars($demande['commentaire_agent']) ?></p>
            </div>
            <?php endif; ?>
            <?php if ($demande['motif_rejet']): ?>
            <div class="p-2 rounded mt-1" style="background:#ffebee;">
              <strong style="font-size:.78rem;color:#c62828;">Motif rejet :</strong>
              <p style="margin:.2rem 0 0;font-size:.82rem;color:#c62828;"><?= htmlspecialchars($demande['motif_rejet']) ?></p>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__.'/../views/partials/footer.php'; ?>
