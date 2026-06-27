<?php
/**
 * En-tête HTML commun — E-Gouvernance État Civil
 * @param string $title   Titre de la page (optionnel)
 * @param string $section Section courante (public|citoyen|agent|admin)
 */
$pageTitle   = isset($pageTitle)   ? $pageTitle . ' — E-État Civil' : 'E-Gouvernance État Civil';
$pageSection = isset($pageSection) ? $pageSection : 'public';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Plateforme numérique de gestion de l'État Civil municipal — Services en ligne sécurisés">
  <meta name="theme-color" content="#003f8a">
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/favicon.svg">

  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
        crossorigin="anonymous">

  <!-- Font Awesome 6 -->
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Style principal -->
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">

  <?php if (isset($extraCss)): ?>
    <?= $extraCss ?>
  <?php endif; ?>

  <style>
    body { font-family: 'Inter', 'Segoe UI', sans-serif; }
  </style>
</head>
<body class="<?= 'section-' . $pageSection ?>">

<!-- Overlay sidebar mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<?php
// Afficher le message flash s'il existe
$flash = getFlash();
if ($flash): ?>
<div class="flash-alert alert alert-<?= $flash['type'] ?> alert-dismissible d-flex align-items-center gap-2"
     role="alert">
  <?php
  $icons = ['success'=>'fa-check-circle','danger'=>'fa-exclamation-circle',
            'warning'=>'fa-exclamation-triangle','info'=>'fa-info-circle'];
  $icon  = $icons[$flash['type']] ?? 'fa-info-circle';
  ?>
  <i class="fas <?= $icon ?>"></i>
  <div><?= htmlspecialchars($flash['message']) ?></div>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
</div>
<?php endif; ?>
