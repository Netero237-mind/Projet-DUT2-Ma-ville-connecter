<?php
/**
 * Téléchargement de l'acte généré — Citoyen / Agent / Admin
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

requireLogin('/auth/login.php');
$user   = getCurrentUser();
$userId = $user['id'];

$id = (int)($_GET['id'] ?? 0);
if (!$id) { flash('danger', 'Identifiant manquant.'); redirect('/' . $user['role'] . '/mes-demandes.php'); }

// Vérification d'accès selon le rôle
if ($user['role'] === 'citoyen') {
    $demande = dbQuery("SELECT * FROM demandes WHERE id=? AND user_id=? AND statut='valide'", [$id, $userId])->fetch();
} else {
    // Agent et admin ont accès à tous les actes validés
    $demande = dbQuery("SELECT * FROM demandes WHERE id=? AND statut='valide'", [$id])->fetch();
}

if (!$demande || empty($demande['acte_pdf_path'])) {
    flash('danger', 'Acte non disponible ou non autorisé.');
    redirect('/' . $user['role'] . '/mes-demandes.php');
}

$filePath = APP_ROOT . '/' . $demande['acte_pdf_path'];

if (!file_exists($filePath)) {
    flash('danger', "Le fichier de l'acte est introuvable sur le serveur. Contactez l'administration.");
    redirect('/' . $user['role'] . '/mes-demandes.php');
}

// Journal de téléchargement
logAction($userId, 'telechargement_acte', "Téléchargement acte demande #{$id}", 'demandes', $id);

$ext      = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimeType = $ext === 'pdf' ? 'application/pdf' : 'text/html; charset=UTF-8';
$filename = 'Acte_' . $demande['numero_reference'] . '.' . $ext;

// Envoyer le fichier
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('X-Content-Type-Options: nosniff');

readfile($filePath);
exit;
