<?php
/**
 * Demande d'acte de décès — Espace Citoyen
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
requireRole('citoyen');
$user   = getCurrentUser();
$userId = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Token de sécurité invalide.'); redirect('/citoyen/demande-deces.php');
    }
    $data   = sanitizeAll($_POST);
    $errors = [];
    if (empty($data['nom_defunt']))    $errors[] = 'Nom du défunt requis.';
    if (empty($data['prenom_defunt'])) $errors[] = 'Prénom du défunt requis.';
    if (empty($data['sexe']))          $errors[] = 'Sexe requis.';
    if (empty($data['date_deces']))    $errors[] = 'Date du décès requise.';
    if (empty($data['lieu_deces']))    $errors[] = 'Lieu du décès requis.';
    if (empty($data['nom_declarant'])) $errors[] = 'Nom du déclarant requis.';

    if (empty($errors)) {
        try {
            $db  = db();
            $db->beginTransaction();
            $ref = generateReference('deces');

            $db->prepare("INSERT INTO demandes (numero_reference,user_id,type_acte,statut,motif_demande) VALUES(?,?,'deces','soumis',?)")
               ->execute([$ref, $userId, $data['motif_demande'] ?? 'Déclaration de décès']);
            $demandeId = $db->lastInsertId();

            $db->prepare("INSERT INTO deces (demande_id,nom_defunt,prenom_defunt,sexe,date_naissance_defunt,lieu_naissance_defunt,nationalite_defunt,profession_defunt,date_deces,heure_deces,lieu_deces,cause_deces,nom_conjoint,nom_declarant,lien_declarant) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
               ->execute([
                   $demandeId,
                   strtoupper($data['nom_defunt']),
                   ucwords(strtolower($data['prenom_defunt'])),
                   $data['sexe'],
                   $data['date_naissance_defunt'] ?: null,
                   $data['lieu_naissance_defunt'] ?? null,
                   $data['nationalite_defunt'] ?? 'Camerounaise',
                   $data['profession_defunt'] ?? null,
                   $data['date_deces'],
                   $data['heure_deces'] ?: null,
                   $data['lieu_deces'],
                   $data['cause_deces'] ?? null,
                   $data['nom_conjoint'] ?? null,
                   $data['nom_declarant'],
                   $data['lien_declarant'] ?? 'Famille',
               ]);

            if (!empty($_FILES['piece_jointe']['name'])) {
                $up = uploadFile($_FILES['piece_jointe'], 'deces');
                if ($up['success']) {
                    $db->prepare("INSERT INTO documents (demande_id,nom_fichier,nom_original,type_document,mime_type,taille,chemin,uploaded_by) VALUES(?,?,?,?,?,?,?,?)")
                       ->execute([$demandeId,$up['filename'],$up['original'],'piece_justificative',$up['mime'],$up['size'],$up['path'],$userId]);
                }
            }
            $db->commit();
            logAction($userId,'depot_demande',"Déclaration de décès $ref",'demandes',$demandeId);
            addNotification($userId,'Déclaration soumise',"Votre déclaration de décès $ref a bien été enregistrée.",'succes','/citoyen/mes-demandes.php');
            flash('success',"✅ Déclaration <strong>$ref</strong> soumise avec succès !");
            redirect('/citoyen/mes-demandes.php');
        } catch (Exception $e) {
            $db->rollBack();
            flash('danger','Une erreur est survenue. Réessayez.');
            redirect('/citoyen/demande-deces.php');
        }
    } else {
        flash('danger', implode('<br>', $errors));
    }
}

$pageTitle='Déclaration de décès'; $pageSection='citoyen';
include __DIR__.'/../views/partials/header.php';
include __DIR__.'/../views/partials/navbar.php';
?>
<div class="dashboard-wrapper">
  <?php include __DIR__.'/../views/citoyen/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-cross me-2 text-danger"></i>Déclaration de décès</h1>
        <div class="breadcrumb-bar">
          <a href="<?= APP_URL ?>/citoyen/dashboard.php">Accueil</a> › <span>Acte de décès</span>
        </div>
      </div>
    </div>

    <div class="alert alert-warning d-flex gap-2 mb-4" style="border-radius:var(--radius);">
      <i class="fas fa-exclamation-triangle mt-1"></i>
      <div style="font-size:.88rem;">
        <strong>Délai légal :</strong> La déclaration de décès doit être faite dans les <strong>90 jours</strong> suivant le décès.
        Pièces requises : CNI du défunt, certificat de décès délivré par un médecin ou l'hôpital.
      </div>
    </div>

    <form method="POST" enctype="multipart/form-data" data-validate>
      <?= csrfField() ?>

      <!-- Identité du défunt -->
      <div class="form-card mb-4">
        <div class="form-card-header">
          <h4><i class="fas fa-id-card me-2 text-danger"></i>Identité du défunt</h4>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Nom <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nom_defunt" required placeholder="NOM" style="text-transform:uppercase;">
          </div>
          <div class="col-md-5">
            <label class="form-label">Prénom(s) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="prenom_defunt" required placeholder="Prénom(s)">
          </div>
          <div class="col-md-3">
            <label class="form-label">Sexe <span class="text-danger">*</span></label>
            <select class="form-select" name="sexe" required>
              <option value="">-- Choisir --</option>
              <option value="M">Masculin</option>
              <option value="F">Féminin</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Date de naissance</label>
            <input type="date" class="form-control" name="date_naissance_defunt">
          </div>
          <div class="col-md-4">
            <label class="form-label">Lieu de naissance</label>
            <input type="text" class="form-control" name="lieu_naissance_defunt" placeholder="Ville / Village">
          </div>
          <div class="col-md-2">
            <label class="form-label">Nationalité</label>
            <input type="text" class="form-control" name="nationalite_defunt" value="Camerounaise">
          </div>
          <div class="col-md-2">
            <label class="form-label">Profession</label>
            <input type="text" class="form-control" name="profession_defunt" placeholder="Profession">
          </div>
          <div class="col-md-4">
            <label class="form-label">Nom du conjoint (si marié)</label>
            <input type="text" class="form-control" name="nom_conjoint" placeholder="Nom et prénom">
          </div>
        </div>
      </div>

      <!-- Circonstances du décès -->
      <div class="form-card mb-4">
        <div class="form-card-header">
          <h4><i class="fas fa-calendar-times me-2 text-danger"></i>Circonstances du décès</h4>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Date du décès <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="date_deces" required max="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Heure du décès</label>
            <input type="time" class="form-control" name="heure_deces">
          </div>
          <div class="col-md-5">
            <label class="form-label">Lieu du décès <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="lieu_deces" required placeholder="Hôpital, domicile, ville…">
          </div>
          <div class="col-12">
            <label class="form-label">Cause du décès (si connue)</label>
            <input type="text" class="form-control" name="cause_deces" placeholder="Cause médicale ou circonstance">
          </div>
        </div>
      </div>

      <!-- Déclarant & pièces -->
      <div class="form-card mb-4">
        <div class="form-card-header">
          <h4><i class="fas fa-file-upload me-2 text-danger"></i>Déclarant & pièces justificatives</h4>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nom complet du déclarant <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nom_declarant" required
                   value="<?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Lien avec le défunt</label>
            <select class="form-select" name="lien_declarant">
              <option value="Fils">Fils</option>
              <option value="Fille">Fille</option>
              <option value="Conjoint(e)">Conjoint(e)</option>
              <option value="Frère/Sœur">Frère/Sœur</option>
              <option value="Ami de la famille">Ami de la famille</option>
              <option value="Autre">Autre</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Précisions supplémentaires</label>
            <textarea class="form-control" name="motif_demande" rows="2" placeholder="Informations complémentaires (optionnel)"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Pièce justificative (certificat de décès, CNI…)</label>
            <div class="upload-zone" data-input="pieceJointe">
              <i class="fas fa-cloud-upload-alt fa-2x mb-2 d-block text-muted"></i>
              <div class="upload-info" style="font-size:.88rem;color:var(--gris-doux);">
                Glissez votre fichier ici ou cliquez pour sélectionner (PDF, JPG, PNG — max 5 Mo)
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
        <button type="submit" class="btn btn-danger px-5">
          <i class="fas fa-paper-plane me-2"></i>Soumettre la déclaration
        </button>
      </div>
    </form>
  </main>
</div>
<?php include __DIR__.'/../views/partials/footer.php'; ?>
