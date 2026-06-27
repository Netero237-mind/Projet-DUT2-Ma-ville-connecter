<?php
/**
 * Script de génération des hashes de mots de passe pour les utilisateurs de démo
 * Exécuter UNE SEULE FOIS après import de la base de données
 * Usage : php database/hash_passwords.php
 */

$passwords = [
    'Admin@2024'    => 'admin@mairie.cm',
    'Agent@2024'    => 'agent@mairie.cm et agent2@mairie.cm',
    'Citoyen@2024'  => 'citoyen@example.cm et amina.biyong@gmail.com',
];

echo "=== Hashes générés pour la base de données ===\n\n";

foreach ($passwords as $pwd => $compte) {
    $hash = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);
    echo "Compte : $compte\n";
    echo "Mot de passe : $pwd\n";
    echo "Hash : $hash\n\n";
}

// Connexion à la base et mise à jour automatique
$host   = 'localhost';
$dbname = 'e_gouvernance_etat_civil';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $updates = [
        ['email' => 'admin@mairie.cm',          'pwd' => 'Admin@2024'],
        ['email' => 'agent@mairie.cm',           'pwd' => 'Agent@2024'],
        ['email' => 'agent2@mairie.cm',          'pwd' => 'Agent@2024'],
        ['email' => 'citoyen@example.cm',        'pwd' => 'Citoyen@2024'],
        ['email' => 'amina.biyong@gmail.com',    'pwd' => 'Citoyen@2024'],
    ];

    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");

    foreach ($updates as $u) {
        $hash = password_hash($u['pwd'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt->execute([$hash, $u['email']]);
        echo "✅ Mise à jour : {$u['email']}\n";
    }

    echo "\n✅ Tous les mots de passe ont été mis à jour avec succès !\n";
    echo "\n=== Comptes de démo ===\n";
    echo "Admin      : admin@mairie.cm / Admin@2024\n";
    echo "Agent 1    : agent@mairie.cm / Agent@2024\n";
    echo "Agent 2    : agent2@mairie.cm / Agent@2024\n";
    echo "Citoyen 1  : citoyen@example.cm / Citoyen@2024\n";
    echo "Citoyen 2  : amina.biyong@gmail.com / Citoyen@2024\n";

} catch (PDOException $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage() . "\n";
    echo "Vérifiez votre configuration dans config/database.php\n";
}
