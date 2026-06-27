<?php
/**
 * Demande d'acte de mariage — Espace Citoyen
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
requireRole('citoyen');
$user   = getCurrentUser();
$userId = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flash('danger','Token invalide.'); redirect('/citoyen/demande-mariage.php');
    }
    $data   = sanitizeAll($_POST);
    $errors = [];
    if (empty($data['nom_epoux']))    $errors[] = 'Nom de l\'époux requis.';
    if (empty($data['nom_epouse']))   $errors[] = 'Nom de l\'épouse requise.';
    if (empty($data['date_mariage'])) $errors[] = 'Date du mariage requise.';
    if (empty($data['lieu_mariage'])) $errors[] = 'Lieu du mariage requis.';

    if (empty($errors)) {
        try {
            $db  = db();
            $db->beginTransaction();
            $ref = generateReference('mariage');

            $db->prepare("INSERT INTO demandes (numero_reference,user_id,type_acte,statut,motif_demande) VALUES(?,?,'mariage','soumis',?)")
               ->execute([$ref, $userId, $data['motif_demande'] ?? 'Demande acte de mariage']);
            $demandeId = $db->lastInsertId();

            $db->prepare("INSERT INTO mariages (demande_id,nom_epoux,prenom_epoux,date_naissance_epoux,lieu_naissance_epoux,nationalite_epoux,profession_epoux,nom_epouse,prenom_epouse,date_naissance_epouse,lieu_naissance_epouse,nationalite_epouse,profession_epouse,date_mariage,lieu_mariage,regime_matrimonial,temoin1_nom,temoin2_nom) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
               ->execute([
                   $demandeId,
                   strtoupper($data['nom_epoux']),    ucwords(strtolower($data['prenom_epoux'])),
                   $data['date_naissance_epoux'] ?: null, $data['lieu_naissance_epoux'] ?? null,
                   $data['nationalite_epoux'] ?? 'Camerounaise', $data['profession_epoux'] ?? null,
                   strtoupper($data['nom_epouse']),   ucwords(strtolower($data['prenom_epouse'])),
                   $data['date_naissance_epouse'] ?: null, $data['lieu_naissance_epouse'] ?? null,
                   $data['nationalite_epouse'] ?? 'Camerounaise', $data['profession_epouse'] ?? null,
                   $data['date_mariage'], $data['lieu_mariage'],
                   $data['regime_matrimonial'] ?? 'communaute_biens',
                   $data['temoin1_nom'] ?? null, $data['temoin2_nom'] ?? null,
               ]);

            if (!empty($_FILES['piece_jointe']['name'])) {
                $up = uploadFile($_FILES['piece_jointe'], 'mariages');
                if ($up['success']) {
                    $db->prepare("INSERT INTO documents (demande_id,nom_fichier,nom_original,type_document,mime_type,taille,chemin,uploaded_by) VALUES(?,?,?,?,?,?,?,?)")
                       ->execute([$demandeId,$up['filename'],$up['original'],'piece_justificative',$up['mime'],$up['size'],$up['path'],$userId]);
                }
            }
            $db->commit();
            logAction($userId,'depot_demande',"Demande mariage $ref",'demandes',$demandeId);
            addNotification($userId,'Demande de mariage soumise',"Votre demande $ref est en attente de traitement.",'succes','/citoyen/mes-demandes.php');
            flash('success',"✅ Demande <strong>$ref</strong> soumise avec succès !");
            redirect('/citoyen/mes-demandes.php');
        } catch (Exception $e) {
            $db->rollBack();
            flash('danger','Erreur lors de l\'envoi. Réessayez.');
            redirect('/citoyen/demande-mariage.php');
        }
    } else {
        flash('danger', implode('<br>', $errors));
    }
}

$pageTitle='Demande acte de mariage'; $pageSection='citoyen';
include __DIR__.'/../views/partials/header.php';
include __DIR__.'/../views/partials/navbar.php';
?>
<div class="dashboard-wrapper">
  <?php include __DIR__.'/../views/citoyen/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-heart me-2" style="color:#6a1b9a;"></i>Demande d'acte de mariage</h1>
        <div class="breadcrumb-bar">
          <a href="<?= APP_URL ?>/citoyen/dashboard.php">Accueil</a> › <span>Acte de mariage</span>
        </div>
      </div>
    </div>

    <div class="alert alert-info d-flex gap-2 mb-4" style="border-radius:var(--radius);">
      <i class="fas fa-info-circle mt-1"></i>
      <div style="font-size:.88rem;">
        <strong>Délai de traitement :</strong> 7 jours ouvrés.
        Pièces requises : CNI des deux époux, actes de naissance, certificats de célibat.
      </div>
    </div>

    <form method="POST" enctype="multipart/form-data" data-validate>
      <?= csrfField() ?>

      <!-- Époux -->
      <div class="form-card mb-4">
        <div class="form-card-header">
          <h4><i class="fas fa-male me-2" style="color:var(--bleu-primaire);"></i>Informations sur l'époux</h4>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Nom <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nom_epoux" required placeholder="NOM" style="text-transform:uppercase;">
          </div>
          <div class="col-md-4">
            <label class="form-label">Prénom(s) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="prenom_epoux" required placeholder="Prénom(s)">
          </div>
          <div class="col-md-4">
            <label class="form-label">Date de naissance</label>
            <input type="date" class="form-control" name="date_naissance_epoux">
          </div>
          <div class="col-md-4">
            <label class="form-label">Lieu de naissance</label>
            <input type="text" class="form-control" name="lieu_naissance_epoux" placeholder="Ville / Village">
          </div>
          <div class="col-md-4">
            <label class="form-label">Nationalité</label>
            <input type="text" class="form-control" name="nationalite_epoux" value="Camerounaise">
          </div>
          <div class="col-md-4">
            <label class="form-label">Profession</label>
            <input type="text" class="form-control" name="profession_epoux" placeholder="Profession">
          </div>
        </div>
      </div>

      <!-- Épouse -->
      <div class="form-card mb-4">
        <div class="form-card-header">
          <h4><i class="fas fa-female me-2" style="color:#880e4f;"></i>Informations sur l'épouse</h4>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Nom <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nom_epouse" required placeholder="NOM" style="text-transform:uppercase;">
          </div>
          <div class="col-md-4">
            <label class="form-label">Prénom(s) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="prenom_epouse" required placeholder="Prénom(s)">
          </div>
          <div class="col-md-4">
            <label class="form-label">Date de naissance</label>
            <input type="date" class="form-control" name="date_naissance_epouse">
          </div>
          <div class="col-md-4">
            <label class="form-label">Lieu de naissance</label>
            <input type="text" class="form-control" name="lieu_naissance_epouse" placeholder="Ville / Village">
          </div>
          <div class="col-md-4">
            <label class="form-label">Nationalité</label>
            <input type="text" class="form-control" name="nationalite_epouse" value="Camerounaise">
          </div>
          <div class="col-md-4">
            <label class="form-label">Profession</label>
            <input type="text" class="form-control" name="profession_epouse" placeholder="Profession">
          </div>
        </div>
      </div>

      <!-- Informations du mariage -->
      <div class="form-card mb-4">
        <div class="form-card-header">
          <h4><i class="fas fa-rings-wedding me-2" style="color:#6a1b9a;"></i>Informations sur le mariage</h4>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Date du mariage <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="date_mariage" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Lieu du mariage <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="lieu_mariage" required placeholder="Mairie, ville…">
          </div>
          <div class="col-md-4">
            <label class="form-label">Régime matrimonial</label>
            <select class="form-select" name="regime_matrimonial">
              <option value="communaute_biens">Communauté de biens</option>
              <option value="separation_biens">Séparation de biens</option>
              <option value="autre">Autre</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Témoin 1 (nom complet)</label>
            <input type="text" class="form-control" name="temoin1_nom" placeholder="Nom et prénom du premier témoin">
          </div>
          <div class="col-md-6">
            <label class="form-label">Témoin 2 (nom complet)</label>
            <input type="text" class="form-control" name="temoin2_nom" placeholder="Nom et prénom du second témoin">
          </div>
          <div class="col-12">
            <label class="form-label">Motif / précisions</label>
            <textarea class="form-control" name="motif_demande" rows="2" placeholder="Informations complémentaires (optionnel)"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Pièces justificatives (CNI, actes de naissance…)</label>
            <div class="upload-zone" data-input="pieceJointe">
              <i class="fas fa-cloud-upload-alt fa-2x mb-2 d-block text-muted"></i>
              <div class="upload-info" style="font-size:.88rem;color:var(--gris-doux);">
                Glissez votre fichier ici ou cliquez (PDF, JPG, PNG — max 5 Mo)
              </div>
              <input type="file" id="pieceJointe" name="piece_jointe" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex gap-3 justify-content-between">
        <a href="<?= APP_URL ?>/citoyen/dashboard.php" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left me-1"></i>Annuler
        </a>
        <button type="submit" class="btn px-5" style="background:#6a1b9a;color:#fff;">
          <i class="fas fa-paper-plane me-2"></i>Soumettre la demande
        </button>
      </div>
    </form>
  </main>
</div>
<?php include __DIR__.'/../views/partials/footer.php'; ?>
