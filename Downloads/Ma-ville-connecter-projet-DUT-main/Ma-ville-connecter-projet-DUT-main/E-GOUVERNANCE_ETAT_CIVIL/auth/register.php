<?php
/**
 * Page d'inscription — E-Gouvernance État Civil
 * Redirige vers login.php avec l'onglet inscription actif
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

if (isLoggedIn()) {
    redirect('/' . ($_SESSION['user_role'] ?? 'citoyen') . '/dashboard.php');
}

// Rediriger vers login avec l'onglet inscription ouvert
redirect('/auth/login.php?tab=register');
