<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>✅ PHP fonctionne : " . phpversion() . "</h2>";

// Test connexion DB
try {
    $pdo = new PDO('mysql:host=localhost;dbname=e_gouvernance_etat_civil', 'root', '');
    echo "<h3 style='color:green'>✅ Base de données connectée !</h3>";
    
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<p>Utilisateurs en base : <strong>$count</strong></p>";
    
    $users = $pdo->query("SELECT email, statut FROM users")->fetchAll();
    echo "<table border='1' cellpadding='5'><tr><th>Email</th><th>Statut</th></tr>";
    foreach ($users as $u) {
        echo "<tr><td>{$u['email']}</td><td>{$u['statut']}</td></tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<h3 style='color:red'>❌ Erreur DB : " . $e->getMessage() . "</h3>";
}
?>
