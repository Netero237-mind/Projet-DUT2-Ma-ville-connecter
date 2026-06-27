<?php
/**
 * Contrôleur d'authentification — E-Gouvernance État Civil
 * Gère : connexion, inscription, déconnexion
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

class AuthController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ============================================================
    // CONNEXION
    // ============================================================
    public function login(string $email, string $password): array
    {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'Veuillez renseigner tous les champs.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Adresse email invalide.'];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT u.*, r.nom as role_nom
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.email = ? AND u.statut = 'actif'
                LIMIT 1
            ");
            $stmt->execute([strtolower(trim($email))]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                logAction(null, 'echec_connexion', "Tentative échouée pour : $email", 'users', null);
                return ['success' => false, 'error' => 'Email ou mot de passe incorrect.'];
            }

            // Créer la session
            session_regenerate_id(true);
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['user_nom']    = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_email']  = $user['email'];
            $_SESSION['user_role']   = $user['role_nom'];
            $_SESSION['user_photo']  = $user['photo_profil'];
            $_SESSION['last_activity'] = time();

            // Mettre à jour la dernière connexion
            $this->db->prepare("UPDATE users SET derniere_connexion = NOW() WHERE id = ?")
                     ->execute([$user['id']]);

            logAction($user['id'], 'connexion', 'Connexion réussie', 'users', $user['id']);

            // Redirection selon le rôle
            $redirect = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);

            if ($redirect) return ['success' => true, 'redirect' => $redirect];

            return match($user['role_nom']) {
                'admin'   => ['success' => true, 'redirect' => APP_URL . '/admin/dashboard.php'],
                'agent'   => ['success' => true, 'redirect' => APP_URL . '/agent/dashboard.php'],
                default   => ['success' => true, 'redirect' => APP_URL . '/citoyen/dashboard.php'],
            };

        } catch (PDOException $e) {
            error_log('[AuthController::login] ' . $e->getMessage());
            return ['success' => false, 'error' => 'Une erreur est survenue. Réessayez.'];
        }
    }

    // ============================================================
    // INSCRIPTION
    // ============================================================
    public function register(array $data): array
    {
        $required = ['nom', 'prenom', 'email', 'password', 'password_confirm', 'telephone'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => 'Veuillez remplir tous les champs obligatoires.'];
            }
        }

        $email = strtolower(trim($data['email']));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Adresse email invalide.'];
        }

        if (strlen($data['password']) < 8) {
            return ['success' => false, 'error' => 'Le mot de passe doit contenir au moins 8 caractères.'];
        }

        if ($data['password'] !== $data['password_confirm']) {
            return ['success' => false, 'error' => 'Les mots de passe ne correspondent pas.'];
        }

        try {
            // Vérifier que l'email n'existe pas
            $check = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                return ['success' => false, 'error' => 'Cette adresse email est déjà utilisée.'];
            }

            // Rôle citoyen = 3
            $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

            $this->db->beginTransaction();

            $stmtUser = $this->db->prepare("
                INSERT INTO users (role_id, nom, prenom, email, password, telephone, adresse, statut, email_verifie)
                VALUES (3, ?, ?, ?, ?, ?, ?, 'actif', 1)
            ");
            $stmtUser->execute([
                strtoupper(trim($data['nom'])),
                ucwords(strtolower(trim($data['prenom']))),
                $email,
                $hash,
                trim($data['telephone'] ?? ''),
                trim($data['adresse'] ?? ''),
            ]);

            $userId = $this->db->lastInsertId();

            // Créer le profil citoyen
            $stmtCitoyen = $this->db->prepare("
                INSERT INTO citoyens (user_id, nationalite) VALUES (?, 'Camerounaise')
            ");
            $stmtCitoyen->execute([$userId]);

            $this->db->commit();

            logAction($userId, 'inscription', 'Nouveau compte citoyen créé', 'users', $userId);

            // Notification de bienvenue
            addNotification($userId,
                'Bienvenue sur E-État Civil !',
                'Votre compte a bien été créé. Vous pouvez maintenant déposer vos demandes d\'actes d\'état civil en ligne.',
                'succes',
                '/citoyen/dashboard.php'
            );

            return ['success' => true, 'user_id' => $userId,
                    'redirect' => APP_URL . '/auth/login.php?msg=registered'];

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('[AuthController::register] ' . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur lors de l\'inscription. Réessayez.'];
        }
    }

    // ============================================================
    // DÉCONNEXION
    // ============================================================
    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            logAction($userId, 'deconnexion', 'Déconnexion du compte', 'users', $userId);
        }
        logout();
    }
}

// ============================================================
// TRAITEMENT DES REQUÊTES POST
// ============================================================
$auth   = new AuthController();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Token de sécurité invalide. Veuillez réessayer.');
        redirect('/auth/login.php');
    }

    switch ($action) {
        case 'login':
            $result = $auth->login(
                trim($_POST['email'] ?? ''),
                $_POST['password'] ?? ''
            );
            if ($result['success']) {
                redirect(str_replace(APP_URL, '', $result['redirect']));
            } else {
                flash('danger', $result['error']);
                redirect('/auth/login.php');
            }
            break;

        case 'register':
            $result = $auth->register($_POST);
            if ($result['success']) {
                flash('success', 'Compte créé avec succès ! Connectez-vous pour accéder à vos services.');
                redirect('/auth/login.php?msg=registered');
            } else {
                flash('danger', $result['error']);
                redirect('/auth/register.php');
            }
            break;
    }
}

if ($action === 'logout') {
    $auth->logout();
}
