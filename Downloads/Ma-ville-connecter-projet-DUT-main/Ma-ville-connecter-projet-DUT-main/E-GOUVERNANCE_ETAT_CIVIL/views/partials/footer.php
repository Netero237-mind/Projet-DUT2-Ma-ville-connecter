<!-- ============================================================
   FOOTER — E-Gouvernance État Civil
   ============================================================ -->
<footer class="footer-main mt-auto">
  <div class="container">
    <div class="row g-4">

      <!-- Colonne 1 : Mairie -->
      <div class="col-lg-4 col-md-6">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div style="width:40px;height:40px;background:rgba(255,255,255,.15);border-radius:10px;
                      display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff;">
            <i class="fas fa-landmark"></i>
          </div>
          <div>
            <div style="font-weight:700;color:#fff;font-size:.95rem;">
              <?= htmlspecialchars(getSystemParam('nom_mairie', 'Mairie')) ?>
            </div>
            <div style="font-size:.72rem;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.8px;">
              Plateforme E-État Civil
            </div>
          </div>
        </div>
        <p style="font-size:.84rem;line-height:1.7;">
          Service numérique de gestion de l'État Civil municipal.
          Accédez à vos documents officiels en ligne, simplement et sécurisément.
        </p>
        <div class="d-flex gap-2 mt-3">
          <a href="#" class="btn btn-sm" style="background:rgba(255,255,255,.1);color:#fff;border-radius:8px;">
            <i class="fab fa-facebook-f"></i>
          </a>
          <a href="#" class="btn btn-sm" style="background:rgba(255,255,255,.1);color:#fff;border-radius:8px;">
            <i class="fab fa-twitter"></i>
          </a>
          <a href="#" class="btn btn-sm" style="background:rgba(255,255,255,.1);color:#fff;border-radius:8px;">
            <i class="fab fa-whatsapp"></i>
          </a>
        </div>
      </div>

      <!-- Colonne 2 : Services -->
      <div class="col-lg-2 col-md-6">
        <h5>Services</h5>
        <ul>
          <li><a href="<?= APP_URL ?>/citoyen/demande-naissance.php">Acte de naissance</a></li>
          <li><a href="<?= APP_URL ?>/citoyen/demande-deces.php">Acte de décès</a></li>
          <li><a href="<?= APP_URL ?>/citoyen/demande-mariage.php">Acte de mariage</a></li>
          <li><a href="<?= APP_URL ?>/citoyen/mes-demandes.php">Suivi de dossier</a></li>
          <li><a href="<?= APP_URL ?>/index.php#contact">Contact</a></li>
        </ul>
      </div>

      <!-- Colonne 3 : Liens utiles -->
      <div class="col-lg-2 col-md-6">
        <h5>Liens utiles</h5>
        <ul>
          <li><a href="<?= APP_URL ?>/auth/register.php">Créer un compte</a></li>
          <li><a href="<?= APP_URL ?>/auth/login.php">Connexion</a></li>
          <li><a href="<?= APP_URL ?>/index.php#actualites">Actualités</a></li>
          <li><a href="#">Mentions légales</a></li>
          <li><a href="#">Politique de confidentialité</a></li>
        </ul>
      </div>

      <!-- Colonne 4 : Contact -->
      <div class="col-lg-4 col-md-6">
        <h5>Nous contacter</h5>
        <ul class="list-unstyled" style="font-size:.84rem;">
          <li class="mb-2">
            <i class="fas fa-map-marker-alt me-2" style="color:#90caf9;width:16px;text-align:center;"></i>
            <?= htmlspecialchars(getSystemParam('adresse_mairie', 'Hôtel de Ville')) ?>
          </li>
          <li class="mb-2">
            <i class="fas fa-phone me-2" style="color:#90caf9;width:16px;text-align:center;"></i>
            <?= htmlspecialchars(getSystemParam('telephone_mairie', '')) ?>
          </li>
          <li class="mb-2">
            <i class="fas fa-envelope me-2" style="color:#90caf9;width:16px;text-align:center;"></i>
            <?= htmlspecialchars(getSystemParam('email_mairie', '')) ?>
          </li>
          <li class="mb-2">
            <i class="fas fa-globe me-2" style="color:#90caf9;width:16px;text-align:center;"></i>
            <?= htmlspecialchars(getSystemParam('site_web', '')) ?>
          </li>
          <li class="mt-3">
            <i class="fas fa-clock me-2" style="color:#90caf9;width:16px;text-align:center;"></i>
            Lun — Ven : 07h30 – 15h30
          </li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom">
      <p style="margin:0;">
        &copy; <?= date('Y') ?>
        <?= htmlspecialchars(getSystemParam('nom_mairie', 'Mairie')) ?> — Tous droits réservés.
        Plateforme E-Gouvernance État Civil v1.0.0
      </p>
    </div>
  </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>

<!-- Application JS -->
<script src="<?= APP_URL ?>/assets/js/app.js"></script>

<?php if (isset($extraJs)): ?>
  <?= $extraJs ?>
<?php endif; ?>

</body>
</html>
