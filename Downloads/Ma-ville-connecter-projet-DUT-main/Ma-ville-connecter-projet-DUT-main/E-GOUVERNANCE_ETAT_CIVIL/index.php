<?php
/**
 * Page d'accueil publique — E-Gouvernance État Civil
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

$pageTitle = 'Accueil';

// Statistiques pour l'affichage
try {
    $statCitoyens  = dbQuery("SELECT COUNT(*) as c FROM users WHERE role_id=3")->fetch()['c'];
    $statDemandes  = dbQuery("SELECT COUNT(*) as c FROM demandes")->fetch()['c'];
    $statValides   = dbQuery("SELECT COUNT(*) as c FROM demandes WHERE statut='valide'")->fetch()['c'];
} catch (Exception $e) {
    $statCitoyens = $statDemandes = $statValides = 0;
}

include __DIR__ . '/views/partials/header.php';
include __DIR__ . '/views/partials/navbar.php';
?>

<!-- ============================================================
   HERO SECTION
   ============================================================ -->
<section class="hero-section">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <div class="hero-badge">
          <i class="fas fa-shield-alt me-1"></i>Plateforme officielle sécurisée
        </div>
        <h1>Vos services d'<span>État Civil</span><br>en ligne, simplement.</h1>
        <p>
          Accédez à tous vos actes d'état civil depuis chez vous.
          Déclarez une naissance, un décès, demandez votre acte de mariage
          et suivez vos dossiers en temps réel.
        </p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-hero-primary">
            <i class="fas fa-user-plus me-2"></i>Créer un compte gratuit
          </a>
          <a href="#services" class="btn btn-hero-outline">
            <i class="fas fa-concierge-bell me-2"></i>Voir les services
          </a>
        </div>
        <div class="hero-stats">
          <div class="hero-stat">
            <span class="stat-number" data-counter="<?= $statCitoyens ?: 1240 ?>"><?= $statCitoyens ?: 1240 ?></span>
            <span class="stat-label">Citoyens inscrits</span>
          </div>
          <div class="hero-stat">
            <span class="stat-number" data-counter="<?= $statDemandes ?: 3560 ?>"><?= $statDemandes ?: 3560 ?></span>
            <span class="stat-label">Demandes traitées</span>
          </div>
          <div class="hero-stat">
            <span class="stat-number" data-counter="<?= $statValides ?: 2890 ?>"><?= $statValides ?: 2890 ?></span>
            <span class="stat-label">Actes délivrés</span>
          </div>
        </div>
      </div>
      <div class="col-lg-6 d-none d-lg-flex justify-content-center">
        <!-- Illustration SVG -->
        <svg viewBox="0 0 520 460" xmlns="http://www.w3.org/2000/svg" width="460" style="max-width:100%;">
          <!-- Bâtiment mairie stylisé -->
          <rect x="60" y="160" width="400" height="260" rx="8" fill="rgba(255,255,255,.08)" stroke="rgba(255,255,255,.2)" stroke-width="1.5"/>
          <!-- Colonnes -->
          <rect x="100" y="200" width="18" height="200" rx="4" fill="rgba(255,255,255,.15)"/>
          <rect x="150" y="200" width="18" height="200" rx="4" fill="rgba(255,255,255,.15)"/>
          <rect x="352" y="200" width="18" height="200" rx="4" fill="rgba(255,255,255,.15)"/>
          <rect x="402" y="200" width="18" height="200" rx="4" fill="rgba(255,255,255,.15)"/>
          <!-- Fronton -->
          <polygon points="60,160 260,80 460,160" fill="rgba(255,255,255,.1)" stroke="rgba(255,255,255,.25)" stroke-width="1.5"/>
          <!-- Drapeau -->
          <rect x="255" y="30" width="2" height="60" fill="rgba(255,255,255,.5)"/>
          <rect x="257" y="32" width="30" height="18" fill="#4caf50"/>
          <!-- Porte centrale -->
          <rect x="220" y="310" width="80" height="110" rx="40" fill="rgba(255,255,255,.2)" stroke="rgba(255,255,255,.35)" stroke-width="1.5"/>
          <circle cx="260" cy="365" r="5" fill="rgba(255,255,255,.6)"/>
          <!-- Fenêtres -->
          <rect x="110" y="240" width="55" height="45" rx="4" fill="rgba(255,255,255,.18)" stroke="rgba(255,255,255,.3)" stroke-width="1"/>
          <rect x="188" y="240" width="55" height="45" rx="4" fill="rgba(255,255,255,.18)" stroke="rgba(255,255,255,.3)" stroke-width="1"/>
          <rect x="277" y="240" width="55" height="45" rx="4" fill="rgba(255,255,255,.18)" stroke="rgba(255,255,255,.3)" stroke-width="1"/>
          <rect x="355" y="240" width="55" height="45" rx="4" fill="rgba(255,255,255,.18)" stroke="rgba(255,255,255,.3)" stroke-width="1"/>
          <!-- Texte gravé -->
          <text x="260" y="195" text-anchor="middle" font-size="11" fill="rgba(255,255,255,.7)" font-family="Arial" letter-spacing="2">MAIRIE</text>
          <!-- Éléments flottants -->
          <rect x="350" y="80" width="130" height="60" rx="10" fill="rgba(255,255,255,.12)" stroke="rgba(255,255,255,.25)" stroke-width="1"/>
          <text x="375" y="107" font-size="11" fill="rgba(255,255,255,.85)" font-family="Arial">📄 Acte délivré</text>
          <text x="375" y="126" font-size="9" fill="rgba(255,255,255,.5)" font-family="Arial">REF-2024-NAI-00142</text>
          <rect x="40" y="290" width="110" height="55" rx="10" fill="rgba(255,255,255,.12)" stroke="rgba(255,255,255,.25)" stroke-width="1"/>
          <text x="63" y="315" font-size="10" fill="rgba(255,255,255,.85)" font-family="Arial">✅ Validé</text>
          <text x="63" y="333" font-size="9" fill="rgba(255,255,255,.5)" font-family="Arial">En 48h en ligne</text>
          <rect x="370" y="290" width="100" height="55" rx="10" fill="rgba(255,255,255,.12)" stroke="rgba(255,255,255,.25)" stroke-width="1"/>
          <text x="385" y="315" font-size="10" fill="rgba(255,255,255,.85)" font-family="Arial">🔒 Sécurisé</text>
          <text x="385" y="333" font-size="9" fill="rgba(255,255,255,.5)" font-family="Arial">SSL + Chiffrement</text>
        </svg>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
   BANDEAU CONFIANCE
   ============================================================ -->
<div style="background:#fff;border-bottom:1px solid #e8ecf0;padding:1.2rem 0;">
  <div class="container">
    <div class="row g-2 text-center">
      <div class="col-6 col-md-3">
        <div style="font-size:.8rem;color:#6c757d;">
          <i class="fas fa-lock text-primary me-1"></i>
          <strong>Données sécurisées</strong>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div style="font-size:.8rem;color:#6c757d;">
          <i class="fas fa-clock text-success me-1"></i>
          <strong>Traitement sous 5 jours</strong>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div style="font-size:.8rem;color:#6c757d;">
          <i class="fas fa-file-pdf text-danger me-1"></i>
          <strong>Actes PDF officiels</strong>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div style="font-size:.8rem;color:#6c757d;">
          <i class="fas fa-mobile-alt text-warning me-1"></i>
          <strong>Accessible 24h/24</strong>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ============================================================
   SERVICES
   ============================================================ -->
<section id="services" class="py-5" style="background:var(--gris-clair);">
  <div class="container">
    <div class="text-center mb-5">
      <div class="hero-badge" style="background:rgba(0,63,138,.08);color:var(--bleu-primaire);border:1px solid rgba(0,63,138,.2);">
        Nos services numériques
      </div>
      <h2 class="section-title mt-3">Tous vos actes d'État Civil</h2>
      <div class="divider-bleu"></div>
      <p class="section-subtitle">
        Déposez vos demandes en ligne, sans vous déplacer.<br>
        Nos agents traitent votre dossier sous quelques jours ouvrés.
      </p>
    </div>

    <div class="row g-4">
      <!-- Acte de naissance -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon bg-naissance">
            <i class="fas fa-baby"></i>
          </div>
          <h5>Acte de naissance</h5>
          <p>Déclarez la naissance d'un enfant ou obtenez une copie de votre acte de naissance en quelques clics.</p>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <small class="text-muted"><i class="fas fa-clock me-1"></i>Délai : 5 jours ouvrés</small>
            <small class="text-success fw-bold">Gratuit</small>
          </div>
          <a href="<?= APP_URL ?>/auth/login.php?next=naissance" class="btn btn-service">
            <i class="fas fa-file-alt me-1"></i>Faire une demande
          </a>
        </div>
      </div>

      <!-- Acte de décès -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon bg-deces">
            <i class="fas fa-cross"></i>
          </div>
          <h5>Acte de décès</h5>
          <p>Déclarez un décès ou obtenez une copie d'acte de décès pour vos démarches administratives.</p>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <small class="text-muted"><i class="fas fa-clock me-1"></i>Délai : 3 jours ouvrés</small>
            <small class="text-success fw-bold">Gratuit</small>
          </div>
          <a href="<?= APP_URL ?>/auth/login.php?next=deces" class="btn btn-service">
            <i class="fas fa-file-alt me-1"></i>Faire une demande
          </a>
        </div>
      </div>

      <!-- Acte de mariage -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon bg-mariage">
            <i class="fas fa-heart"></i>
          </div>
          <h5>Acte de mariage</h5>
          <p>Enregistrez votre mariage civil ou demandez une copie de votre acte de mariage certifiée conforme.</p>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <small class="text-muted"><i class="fas fa-clock me-1"></i>Délai : 7 jours ouvrés</small>
            <small class="text-success fw-bold">Gratuit</small>
          </div>
          <a href="<?= APP_URL ?>/auth/login.php?next=mariage" class="btn btn-service">
            <i class="fas fa-file-alt me-1"></i>Faire une demande
          </a>
        </div>
      </div>

      <!-- Suivi de dossier -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon bg-casier">
            <i class="fas fa-search"></i>
          </div>
          <h5>Suivi de dossier</h5>
          <p>Suivez l'avancement de vos demandes en temps réel grâce à votre espace personnel sécurisé.</p>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <small class="text-muted"><i class="fas fa-bell me-1"></i>Notifications en temps réel</small>
          </div>
          <a href="<?= APP_URL ?>/auth/login.php?next=suivi" class="btn btn-service">
            <i class="fas fa-search me-1"></i>Suivre mon dossier
          </a>
        </div>
      </div>

      <!-- Téléchargement PDF -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon bg-autre">
            <i class="fas fa-file-pdf"></i>
          </div>
          <h5>Téléchargement PDF</h5>
          <p>Téléchargez vos actes validés directement en PDF depuis votre espace, disponibles immédiatement.</p>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <small class="text-muted"><i class="fas fa-download me-1"></i>Disponible après validation</small>
          </div>
          <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-service">
            <i class="fas fa-download me-1"></i>Accéder à mes actes
          </a>
        </div>
      </div>

      <!-- Espace agent -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon" style="background:#e8eaf6;color:#283593;">
            <i class="fas fa-user-tie"></i>
          </div>
          <h5>Espace Agent</h5>
          <p>Agents municipaux : accédez à votre tableau de bord pour traiter, valider et générer les actes officiels.</p>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <small class="text-muted"><i class="fas fa-lock me-1"></i>Accès réservé aux agents</small>
          </div>
          <a href="<?= APP_URL ?>/auth/login.php?role=agent" class="btn btn-service" style="background:#283593;">
            <i class="fas fa-sign-in-alt me-1"></i>Connexion agent
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
   COMMENT ÇA MARCHE
   ============================================================ -->
<section class="py-5" style="background:#fff;">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Comment faire votre demande ?</h2>
      <div class="divider-bleu"></div>
    </div>
    <div class="row g-4 text-center">
      <div class="col-md-3">
        <div style="width:64px;height:64px;background:var(--bleu-primaire);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.4rem;color:#fff;">1</div>
        <h6 style="font-weight:700;color:var(--bleu-fonce);">Créez votre compte</h6>
        <p style="font-size:.85rem;color:#718096;">Inscrivez-vous gratuitement avec votre email et vos informations personnelles.</p>
      </div>
      <div class="col-md-3">
        <div style="width:64px;height:64px;background:var(--bleu-accent);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.4rem;color:#fff;">2</div>
        <h6 style="font-weight:700;color:var(--bleu-fonce);">Remplissez le formulaire</h6>
        <p style="font-size:.85rem;color:#718096;">Sélectionnez le type d'acte souhaité et renseignez les informations requises.</p>
      </div>
      <div class="col-md-3">
        <div style="width:64px;height:64px;background:var(--vert-admin);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.4rem;color:#fff;">3</div>
        <h6 style="font-weight:700;color:var(--bleu-fonce);">Joignez vos pièces</h6>
        <p style="font-size:.85rem;color:#718096;">Téléversez les documents justificatifs demandés (CNI, certificat de naissance, etc.).</p>
      </div>
      <div class="col-md-3">
        <div style="width:64px;height:64px;background:#e65100;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.4rem;color:#fff;">4</div>
        <h6 style="font-weight:700;color:var(--bleu-fonce);">Téléchargez votre acte</h6>
        <p style="font-size:.85rem;color:#718096;">Une fois validé, téléchargez votre acte officiel en PDF depuis votre espace.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
   ACTUALITÉS
   ============================================================ -->
<section id="actualites" class="py-5" style="background:var(--gris-clair);">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Actualités municipales</h2>
      <div class="divider-bleu"></div>
    </div>
    <div class="row g-4">
      <div class="col-lg-4 col-md-6">
        <div class="news-card">
          <div class="news-img"><i class="fas fa-laptop-code"></i></div>
          <div class="news-body">
            <div class="news-cat">Innovation numérique</div>
            <div class="news-title">Lancement de la plateforme E-État Civil</div>
            <p style="font-size:.83rem;color:#718096;margin:.4rem 0 .8rem;">
              La mairie lance officiellement sa plateforme numérique pour simplifier l'accès aux services d'état civil.
            </p>
            <div class="news-date"><i class="fas fa-calendar me-1"></i><?= date('d/m/Y') ?></div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6">
        <div class="news-card">
          <div class="news-img"><i class="fas fa-calendar-alt"></i></div>
          <div class="news-body">
            <div class="news-cat">Horaires</div>
            <div class="news-title">Horaires des services d'état civil</div>
            <p style="font-size:.83rem;color:#718096;margin:.4rem 0 .8rem;">
              Les services d'état civil sont ouverts du lundi au vendredi, de 07h30 à 15h30. Les demandes en ligne restent disponibles 24h/24.
            </p>
            <div class="news-date"><i class="fas fa-calendar me-1"></i><?= date('d/m/Y') ?></div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6">
        <div class="news-card">
          <div class="news-img"><i class="fas fa-file-signature"></i></div>
          <div class="news-body">
            <div class="news-cat">Procédures</div>
            <div class="news-title">Documents requis pour vos demandes</div>
            <p style="font-size:.83rem;color:#718096;margin:.4rem 0 .8rem;">
              Consultez la liste complète des pièces justificatives nécessaires pour chaque type de demande d'acte d'état civil.
            </p>
            <div class="news-date"><i class="fas fa-calendar me-1"></i><?= date('d/m/Y') ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
   CONTACT
   ============================================================ -->
<section id="contact" class="py-5" style="background:#fff;">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-6">
        <h2 class="section-title">Nous contacter</h2>
        <div class="divider-bleu" style="margin:0 0 1.5rem;"></div>
        <p style="color:#718096;margin-bottom:2rem;">
          Pour toute question sur vos démarches ou pour obtenir de l'aide,
          n'hésitez pas à nous contacter.
        </p>
        <div class="d-flex flex-column gap-3">
          <div class="d-flex align-items-center gap-3 p-3 rounded" style="background:var(--gris-clair);">
            <div style="width:44px;height:44px;background:var(--bleu-primaire);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;">
              <i class="fas fa-map-marker-alt"></i>
            </div>
            <div>
              <div style="font-weight:600;font-size:.9rem;color:var(--bleu-fonce);">Adresse</div>
              <div style="font-size:.85rem;color:#718096;"><?= htmlspecialchars(getSystemParam('adresse_mairie')) ?></div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-3 p-3 rounded" style="background:var(--gris-clair);">
            <div style="width:44px;height:44px;background:var(--vert-admin);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;">
              <i class="fas fa-phone"></i>
            </div>
            <div>
              <div style="font-weight:600;font-size:.9rem;color:var(--bleu-fonce);">Téléphone</div>
              <div style="font-size:.85rem;color:#718096;"><?= htmlspecialchars(getSystemParam('telephone_mairie')) ?></div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-3 p-3 rounded" style="background:var(--gris-clair);">
            <div style="width:44px;height:44px;background:#e65100;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;">
              <i class="fas fa-envelope"></i>
            </div>
            <div>
              <div style="font-weight:600;font-size:.9rem;color:var(--bleu-fonce);">Email</div>
              <div style="font-size:.85rem;color:#718096;"><?= htmlspecialchars(getSystemParam('email_mairie')) ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="form-card">
          <div class="form-card-header">
            <h4><i class="fas fa-paper-plane me-2 text-primary"></i>Envoyer un message</h4>
          </div>
          <form data-validate action="<?= APP_URL ?>/api/contact.php" method="POST">
            <?= csrfField() ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Nom complet <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="nom" required placeholder="Votre nom">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="email" required placeholder="votre@email.cm">
              </div>
              <div class="col-12">
                <label class="form-label">Objet <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="objet" required placeholder="Objet de votre message">
              </div>
              <div class="col-12">
                <label class="form-label">Message <span class="text-danger">*</span></label>
                <textarea class="form-control" name="message" rows="4" required
                          placeholder="Décrivez votre demande ou question…"></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary w-100">
                  <i class="fas fa-paper-plane me-2"></i>Envoyer le message
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/views/partials/footer.php'; ?>
