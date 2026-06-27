<?php
/**
 * Déconnexion — E-Gouvernance État Civil
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

$userId = $_SESSION['user_id'] ?? null;
if ($userId) {
    logAction($userId, 'deconnexion', 'Déconnexion du compte', 'users', $userId);
}
logout();
