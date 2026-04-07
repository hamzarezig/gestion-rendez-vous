<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$nom = trim($data['nom'] ?? '');
$prenom = trim($data['prenom'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$role = $data['role'] ?? 'patient';

// Validation
if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères']);
    exit();
}

// Vérifier si l'email existe déjà
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé']);
    exit();
}

// Hasher le mot de passe
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();
    
    // Insérer l'utilisateur
    $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nom, $prenom, $email, $hashedPassword, $role]);
    $userId = $pdo->lastInsertId();
    
    // Si c'est un docteur, ajouter ses infos
    if ($role === 'docteur') {
        $specialite = $data['specialite'] ?? '';
        $telephone = $data['telephone'] ?? '';
        $adresse = $data['adresse'] ?? '';
        $prix = $data['prix'] ?? 0;
        
        $stmt = $pdo->prepare("INSERT INTO docteurs (user_id, specialite, telephone, cabinet_adresse, consultation_price) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $specialite, $telephone, $adresse, $prix]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Inscription réussie !']);
    
} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}
?>