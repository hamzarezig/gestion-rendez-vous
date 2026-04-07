<?php
require_once '../../backend/config/database.php';

$email = 'marie.dupont@email.com';
$password = 'test123';

echo "<h1>Test de connexion direct</h1>";
echo "<p>Email: $email</p>";
echo "<p>Mot de passe: $password</p>";

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    echo "<p>✅ Utilisateur trouvé: " . $user['prenom'] . " " . $user['nom'] . "</p>";
    
    if (password_verify($password, $user['password'])) {
        echo "<p style='color:green'>✅ Mot de passe correct !</p>";
        
        // Démarrer session
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        echo "<p>Session démarrée ! <a href='dashboard.php'>Aller au tableau de bord</a></p>";
        
    } else {
        echo "<p style='color:red'>❌ Mot de passe incorrect !</p>";
    }
} else {
    echo "<p style='color:red'>❌ Utilisateur non trouvé !</p>";
}
?>