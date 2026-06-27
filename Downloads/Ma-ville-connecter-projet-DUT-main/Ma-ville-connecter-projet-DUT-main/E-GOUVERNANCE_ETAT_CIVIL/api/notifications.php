<?php
/**
 * API Notifications — E-Gouvernance État Civil
 * Endpoints AJAX pour la gestion des notifications
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié', 'count' => 0]);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // Compter les notifications non lues
    case 'count':
        $count = countUnreadNotifications($userId);
        echo json_encode(['count' => $count, 'success' => true]);
        break;

    // Lire une notification et rediriger
    case 'read':
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            dbQuery("UPDATE notifications SET lu=1 WHERE id=? AND user_id=?", [$id, $userId]);
        }
        $redirect = $_GET['redirect'] ?? '/citoyen/dashboard.php';
        // Répondre avec redirect pour navigation
        header('Content-Type: text/html');
        header('Location: ' . APP_URL . $redirect);
        exit;

    // Marquer toutes comme lues
    case 'read_all':
        dbQuery("UPDATE notifications SET lu=1 WHERE user_id=?", [$userId]);
        echo json_encode(['success' => true, 'message' => 'Toutes les notifications marquées comme lues.']);
        break;

    // Lister les notifications récentes
    case 'list':
        $limit = min((int)($_GET['limit'] ?? 10), 50);
        $notifs = dbQuery("
            SELECT id, titre, message, type, lien, lu,
                   created_at,
                   TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_ago
            FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ", [$userId, $limit])->fetchAll();

        foreach ($notifs as &$n) {
            $min = (int)$n['minutes_ago'];
            if ($min < 1)       $n['temps'] = "À l'instant";
            elseif ($min < 60)  $n['temps'] = "$min min";
            elseif ($min < 1440) $n['temps'] = floor($min/60)." h";
            else                 $n['temps'] = formatDate($n['created_at']);
        }

        echo json_encode(['success' => true, 'notifications' => $notifs, 'total' => count($notifs)]);
        break;

    // Supprimer une notification
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            dbQuery("DELETE FROM notifications WHERE id=? AND user_id=?", [$id, $userId]);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'ID manquant']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Action invalide']);
        break;
}
