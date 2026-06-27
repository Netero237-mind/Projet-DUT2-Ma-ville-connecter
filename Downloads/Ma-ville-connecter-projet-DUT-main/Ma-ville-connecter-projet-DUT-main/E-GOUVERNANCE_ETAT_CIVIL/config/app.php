<?php
/**
 * Configuration générale de l'application
 * E-Gouvernance État Civil Municipal
 */

// Démarrage sécurisé de la session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path'     => '/',
        'secure'   => false, // Passer à true en HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ============================================================
// SÉCURITÉ — Protection CSRF
// ============================================================
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || 
        (isset($_SESSION['csrf_expiry']) && time() > $_SESSION['csrf_expiry'])) {
        $_SESSION['csrf_token']  = bin2hex(random_bytes(32));
        $_SESSION['csrf_expiry'] = time() + CSRF_TOKEN_EXPIRY;
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $token) &&
           (!isset($_SESSION['csrf_expiry']) || time() <= $_SESSION['csrf_expiry']);
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

// ============================================================
// AUTHENTIFICATION
// ============================================================
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser(): ?array
{
    if (!isLoggedIn()) return null;
    
    // Vérifier le timeout de session
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_destroy();
        return null;
    }
    $_SESSION['last_activity'] = time();
    
    return [
        'id'     => $_SESSION['user_id'],
        'nom'    => $_SESSION['user_nom'] ?? '',
        'prenom' => $_SESSION['user_prenom'] ?? '',
        'email'  => $_SESSION['user_email'] ?? '',
        'role'   => $_SESSION['user_role'] ?? '',
        'photo'  => $_SESSION['user_photo'] ?? null,
    ];
}

function requireLogin(string $redirectTo = '/index.php'): void
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect($redirectTo . '?action=login&msg=session_expired');
    }
}

function requireRole(string|array $roles): void
{
    requireLogin();
    $userRole = $_SESSION['user_role'] ?? '';
    $allowed  = is_array($roles) ? $roles : [$roles];
    
    if (!in_array($userRole, $allowed)) {
        redirect('/index.php?error=access_denied');
    }
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    redirect('/index.php?msg=logged_out');
}

// ============================================================
// UTILITAIRES
// ============================================================
function redirect(string $url): void
{
    if (!headers_sent()) {
        header("Location: " . APP_URL . $url);
    } else {
        echo "<script>window.location.href='" . APP_URL . $url . "';</script>";
    }
    exit;
}

function sanitize(string $input): string
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function sanitizeAll(array $data): array
{
    return array_map(fn($v) => is_string($v) ? sanitize($v) : $v, $data);
}

function generateReference(string $type): string
{
    $prefix = match(strtolower($type)) {
        'naissance' => 'NAI',
        'deces'     => 'DEC',
        'mariage'   => 'MAR',
        default     => 'REF',
    };
    $year = date('Y');
    
    try {
        $stmt = dbQuery(
            "SELECT COUNT(*) as total FROM demandes WHERE type_acte = ? AND YEAR(created_at) = ?",
            [$type, $year]
        );
        $count  = $stmt->fetch()['total'] + 1;
        $padded = str_pad($count, 5, '0', STR_PAD_LEFT);
        return "REF-{$year}-{$prefix}-{$padded}";
    } catch (Exception $e) {
        return "REF-{$year}-{$prefix}-" . rand(10000, 99999);
    }
}

function generateNumeroActe(string $type, string $ville = 'DLA'): string
{
    $prefix = match(strtolower($type)) {
        'naissance' => 'NAI',
        'deces'     => 'DEC',
        'mariage'   => 'MAR',
        default     => 'ACT',
    };
    $year  = date('Y');
    $count = rand(100, 999);
    return "{$prefix}-{$ville}-{$year}-{$count}";
}

function formatDate(string $date, string $format = 'd/m/Y'): string
{
    if (empty($date)) return '-';
    try {
        return (new DateTime($date))->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

function formatDateTime(string $datetime): string
{
    return formatDate($datetime, 'd/m/Y à H:i');
}

function timeAgo(string $datetime): string
{
    $time   = strtotime($datetime);
    $diff   = time() - $time;
    
    if ($diff < 60)     return "À l'instant";
    if ($diff < 3600)   return floor($diff / 60) . " min";
    if ($diff < 86400)  return floor($diff / 3600) . " h";
    if ($diff < 604800) return floor($diff / 86400) . " j";
    return formatDate($datetime);
}

function getStatutBadge(string $statut): string
{
    $badges = [
        'soumis'    => '<span class="badge bg-secondary"><i class="fas fa-clock me-1"></i>Soumis</span>',
        'en_cours'  => '<span class="badge bg-warning text-dark"><i class="fas fa-spinner me-1"></i>En cours</span>',
        'valide'    => '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Validé</span>',
        'rejete'    => '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Rejeté</span>',
        'archive'   => '<span class="badge bg-dark"><i class="fas fa-archive me-1"></i>Archivé</span>',
    ];
    return $badges[$statut] ?? '<span class="badge bg-secondary">' . htmlspecialchars($statut) . '</span>';
}

function getTypeActeLabel(string $type): string
{
    $labels = [
        'naissance'   => 'Acte de naissance',
        'deces'       => 'Acte de décès',
        'mariage'     => 'Acte de mariage',
        'casier'      => 'Casier judiciaire',
        'nationalite' => 'Certificat de nationalité',
        'autre'       => 'Autre acte',
    ];
    return $labels[$type] ?? ucfirst($type);
}

function getTypeActeIcon(string $type): string
{
    $icons = [
        'naissance'   => 'fas fa-baby',
        'deces'       => 'fas fa-cross',
        'mariage'     => 'fas fa-rings-wedding',
        'casier'      => 'fas fa-gavel',
        'nationalite' => 'fas fa-flag',
        'autre'       => 'fas fa-file-alt',
    ];
    return $icons[$type] ?? 'fas fa-file';
}

function uploadFile(array $file, string $subfolder = 'documents'): array
{
    $uploadDir = UPLOAD_PATH . $subfolder . '/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Erreur lors de l\'upload du fichier.'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'Le fichier dépasse la taille maximale de 5 Mo.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_TYPES)) {
        return ['success' => false, 'error' => 'Type de fichier non autorisé. Formats acceptés : PDF, JPG, PNG.'];
    }

    // Validation MIME type
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($mimeType, $allowedMimes)) {
        return ['success' => false, 'error' => 'Le contenu du fichier n\'est pas valide.'];
    }

    $newName = uniqid('doc_', true) . '_' . time() . '.' . $ext;
    $destPath = $uploadDir . $newName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'error' => 'Impossible de sauvegarder le fichier.'];
    }

    return [
        'success'      => true,
        'filename'     => $newName,
        'original'     => $file['name'],
        'path'         => $destPath,
        'url'          => UPLOAD_URL . $subfolder . '/' . $newName,
        'mime'         => $mimeType,
        'size'         => $file['size'],
    ];
}

function logAction(int $userId = null, string $action = '', string $description = '', string $entite = '', int $entiteId = null): void
{
    try {
        dbQuery(
            "INSERT INTO historique_actions (user_id, action, description, entite, entite_id, ip_address, user_agent) VALUES (?,?,?,?,?,?,?)",
            [
                $userId,
                $action,
                $description,
                $entite,
                $entiteId,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]
        );
    } catch (Exception $e) {
        error_log('[E-Gouvernance Log Error] ' . $e->getMessage());
    }
}

function addNotification(int $userId, string $titre, string $message, string $type = 'info', string $lien = null): void
{
    try {
        dbQuery(
            "INSERT INTO notifications (user_id, titre, message, type, lien) VALUES (?,?,?,?,?)",
            [$userId, $titre, $message, $type, $lien]
        );
    } catch (Exception $e) {
        error_log('[E-Gouvernance Notification Error] ' . $e->getMessage());
    }
}

function countUnreadNotifications(int $userId): int
{
    try {
        $stmt = dbQuery("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND lu = 0", [$userId]);
        return (int)$stmt->fetch()['cnt'];
    } catch (Exception $e) {
        return 0;
    }
}

function getSystemParam(string $key, string $default = ''): string
{
    static $params = null;
    if ($params === null) {
        try {
            $stmt   = dbQuery("SELECT cle, valeur FROM parametres_systeme");
            $params = [];
            while ($row = $stmt->fetch()) {
                $params[$row['cle']] = $row['valeur'];
            }
        } catch (Exception $e) {
            return $default;
        }
    }
    return $params[$key] ?? $default;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
