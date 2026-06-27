<?php
/**
 * Demande d'acte de naissance — Espace Citoyen
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

requireRole('citoyen');
$user   = getCurrentUser();
$userId = $user['id'];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Token de sécurité invalide.');
        redirect('/citoyen/demande-naissance.php');
    }

    $data = sanitizeAll($_POST);
    $errors = [];

    // Validations
    if (empty($data['nom_enfant']))    $errors[] = 'Le nom de l\'enfant est requis.';
    if (empty($data['prenom_enfant'])) $errors[] = 'Le prénom de l\'enfant est requis.';
    if (empty($data['sexe']))          $errors[] = 'Le sexe est requis.';
    if (empty($data['date_naissance']))$errors[] = 'La date de naissance est requise.';
    if (empty($data['lieu_naissance']))$errors[] = 'Le lieu de naissance est requis.';

    if (empty($errors)) {
        try {
            $db = db();
            $db->beginTransaction();

            $reference = generateReference('naissance');

            // Insérer la demande
            $stmtD = $db->prepare("
                INSERT INTO demandes (numero_reference, user_id, type_acte, statut, motif_demande)
                VALUES (?, ?, 'naissance', 'soumis', ?)
            ");
            $stmtD->execute([$reference, $userId, $data['motif_demande'] ?? 'Demande d\'acte de naissance']);
            $demandeId = $db->lastInsertId();

            // Insérer les détails de naissance
            $stmtN = $db->prepare("
                INSERT INTO naissances
                (demande_id, nom_enfant, prenom_enfant, sexe, date_naissance, heure_naissance,
                 lieu_naissance, centre_sante, nom_pere, prenom_pere, nationalite_pere, profession_pere,
                 nom_mere, prenom_mere, nationalite_mere, profession_mere, nom_declarant, lien_declarant)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmtN->execute([
                $demandeId,
                strtoupper($data['nom_enfant']),
                ucwords(strtolower($data['prenom_enfant'])),
                $data['sexe'],
                $data['date_naissance'],
                $data['heure_naissance'] ?: null,
                $data['lieu_naissance'],
                $data['centre_sante'] ?? null,
                strtoupper($data['nom_pere'] ?? ''),
                ucwords(strtolower($data['prenom_pere'] ?? '')),
                $data['nationalite_pere'] ?? 'Camerounaise',
                $data['profession_pere'] ?? null,
                strtoupper($data['nom_mere'] ?? ''),
                ucwords(strtolower($data['prenom_mere'] ?? '')),
                $data['nationalite_mere'] ?? 'Camerounaise',
                $data['profession_mere'] ?? null,
                $data['nom_declarant'] ?? ($user['prenom'] . ' ' . $user['nom']),
                $data['lien_declarant'] ?? 'Parent',
            ]);

            // Traitement du fichier joint
            if (!empty($_FILES['piece_jointe']['name'])) {
                $upload = uploadFile($_FILES['piece_jointe'], 'naissances');
                if ($upload['success']) {
                    $db->prepare("
                        INSERT INTO documents (demande_id, nom_fichier, nom_original, type_document, mime_type, taille, chemin, uploaded_by)
                        VALUES (?,?,?,?,?,?,?,?)
                    ")->execute([
                        $demandeId,
                        $upload['filename'],
                        $upload['original'],
                        'piece_justificative',
                        $upload['mime'],
                        $upload['size'],
                        $upload['path'],
                        $userId,
                    ]);
                }
            }

            $db->commit();

            logAction($userId, 'depot_demande', "Nouvelle demande de naissance $reference", 'demandes', $demandeId);
            addNotification($userId,
                'Demande soumise avec succès',
                "Votre demande d'acte de naissance $reference a bien été enregistrée et est en attente de traitement.",
                'succes',
                '/citoyen/mes-demandes.php'
            );

            flash('success', "✅ Demande <strong>$reference</strong> soumise avec succès ! Vous serez notifié de l'avancement.");
            redirect('/citoyen/mes-demandes.php');

        } catch (Exception $e) {
            $db->rollBack();
            error_log('[DemNaissance] ' . $e->getMessage());
            flash('danger', 'Une erreur est survenue. Veuillez réessayer.');
            redirect('/citoyen/demande-naissance.php');
        }
    } else {
        flash('danger', implode('<br>', $errors));
    }
}

$pageTitle   = 'Demande d\'acte de naissance';
$pageSection = 'citoyen';

include __DIR__ . '/../views/partials/header.php';
include __DIR__ . '/../views/partials/navbar.php';
?>

<div class="dashboard-wrapper">

  <?php include __DIR__ . '/../views/citoyen/sidebar.php'; ?>

  <main class="main-content">
    <div class="page-header">
      <div>
        <h1><i class="fas fa-baby me-2 text-primary"></i>Demande d'acte de naissance</h1>
        <div class="breadcrumb-bar">
          <a href="<?= APP_URL ?>/citoyen/dashboard.php">Accueil</a> ›
          <span>Acte de naissance</span>
        </div>
      </div>
    </div>

    <!-- Informations -->
    <div class="alert alert-info d-flex gap-2 mb-4" style="border-radius:var(--radius);">
      <i class="fas fa-info-circle mt-1"></i>
      <div style="font-size:.88rem;">
        <strong>Délai de traitement :</strong> 5 jours ouvrés.
        Vous recevrez une notification dès que votre dossier sera traité.
        Pièces requises : CNI du déclarant, carnet de santé ou certificat de naissance de la maternité.
      </div>
    </div>

    <form method="POST" action="" enctype="multipart/form-data" data-validate id="formNaissance">
      <?= csrfField() ?>

      <!-- =========================================================
         INFORMATIONS SUR L'ENFANT
      ========================================================= -->
      <div class="form-card mb-4">
        <div class="form-card-header">
          <h4><i class="fas fa-baby me-2 text-primary"></i>Informations sur l'enfant</h4>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Nom <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nom_enfant" required
                   placeholder="NOM DE FAMILLE" style="text-transform:uppercase;">
          </div>
          <div class="col-md-5">
            <label class="form-label">Prénom(s) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="prenom_enfant" required
                   placeholder="Prénoms de l'enfant">
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
            <label class="form-label">Date de naissance <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="date_naissance" required
                   max="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Heure de naissance</label>
            <input type="time" class="form-control" name="heure_naissance">
          </div>
          <div class="col-md-5">
            <label class="form-label">Lieu de naissance <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="lieu_naissance" required
                   placeholder="Ville / Quartier">
          </div>
          <div class="col-md-12">
            <label class="form-label">Centre de santé / Maternité</label>
            <input type="text" class="form-control" name="centre_sante"
                   placeholder="Nom de la maternité ou hôpital">
          </div>
        </div>
      </div>

      <!-- =========================================================
         INFORMATIONS SUR LES PARENTS
      ========================================================= -->
      <div class="form-card mb-4">
        <div class="form-card-header">
          <h4><i class="fas fa-users me-2 text-primary"></i>Informations sur les parents</h4>
        </div>

        <div class="form-section-title"><i class="fas fa-male me-2"></i>Père</div>
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <label class="form-label">Nom du père</label>
            <input type="text" class="form-control" name="nom_pere"
                   placeholder="NOM" style="text-transform:uppercase;">
          </div>
          <div class="col-md-4">
            <label class="form-label">Prénom(s) du père</label>
            <input type="text" class="form-control" name="prenom_pere" placeholder="Prénom(s)">
          </div>
          <div class="col-md-2">
            <label class="form-label">Nationalité</label>
            <input type="text" class="form-control" name="nationalite_pere" value="Camerounaise">
          </div>
          <div class="col-md-2">
            <label class="form-label">Profession</label>
            <input type="text" class="form-control" name="profession_pere" placeholder="Profession">
          </div>
        </div>

        <div class="form-section-title"><i class="fas fa-female me-2"></i>Mère</div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Nom de la mère</label>
            <input type="text" class="form-control" name="nom_mere"
                   placeholder="NOM" style="text-transform:uppercase;">
          </div>
          <div class="col-md-4">
            <label class="form-label">Prénom(s) de la mère</label>
            <input type="text" class="form-control" name="prenom_mere" placeholder="Prénom(s)">
          </div>
          <div class="col-md-2">
            <label class="form-label">Nationalité</label>
            <input type="text" class="form-control" name="nationalite_mere" value="Camerounaise">
          </div>
          <div class="col-md-2">
            <label class="form-label">Profession</label>
            <input type="text" class="form-control" name="profession_mere" placeholder="Profession">
          </div>
        </div>
      </div>

      <!-- =========================================================
         DÉCLARANT ET PIÈCES JOINTES
      ========================================================= -->
      <div class="form-card mb-4">
        <div class="form-card-header">
          <h4><i class="fas fa-file-upload me-2 text-primary"></i>Déclarant & pièces justificatives</h4>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nom du déclarant</label>
            <input type="text" class="form-control" name="nom_declarant"
                   value="<?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Lien avec l'enfant</label>
            <select class="form-select" name="lien_declarant">
              <option value="Père">Père</option>
              <option value="Mère">Mère</option>
              <option value="Tuteur légal">Tuteur légal</option>
              <option value="Autre">Autre</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Motif ou précisions</label>
            <textarea class="form-control" name="motif_demande" rows="2"
                      placeholder="Précisions sur votre demande (optionnel)"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">
              Pièce justificative (CNI, carnet de santé…)
              <span class="text-muted" style="font-weight:400;">(PDF, JPG, PNG — max 5 Mo)</span>
            </label>
            <div class="upload-zone" data-input="pieceJointe">
              <i class="fas fa-cloud-upload-alt fa-2x mb-2 d-block text-muted"></i>
              <div class="upload-info" style="font-size:.88rem;color:var(--gris-doux);">
                Glissez votre fichier ici ou cliquez pour sélectionner
              </div>
              <input type="file" id="pieceJointe" name="piece_jointe"
                     accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
            </div>
          </div>
        </div>
      </div>

      <!-- BOUTONS -->
      <div class="d-flex gap-3 justify-content-between">
        <a href="<?= APP_URL ?>/citoyen/dashboard.php" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left me-1"></i>Annuler
        </a>
        <button type="submit" class="btn btn-primary px-5">
          <i class="fas fa-paper-plane me-2"></i>Soumettre la demande
        </button>
      </div>
    </form>

  </main>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
