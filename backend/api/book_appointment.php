<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté en tant que patient']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$docteur_id = $data['docteur_id'] ?? '';
$date_rdv = $data['date_rdv'] ?? '';
$heure_rdv = $data['heure_rdv'] ?? '';
$motif = $data['motif'] ?? '';

if (empty($docteur_id) || empty($date_rdv) || empty($heure_rdv)) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit();
}

// Vérifier si le créneau est disponible
$stmt = $pdo->prepare("SELECT id FROM rendezvous WHERE docteur_id = ? AND date_rdv = ? AND heure_rdv = ? AND statut != 'annule'");
$stmt->execute([$docteur_id, $date_rdv, $heure_rdv]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'Ce créneau est déjà pris']);
    exit();
}

// Prendre le rendez-vous
try {
    $stmt = $pdo->prepare("INSERT INTO rendezvous (patient_id, docteur_id, date_rdv, heure_rdv, motif) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $docteur_id, $date_rdv, $heure_rdv, $motif]);
    
    echo json_encode(['success' => true, 'message' => 'Rendez-vous pris avec succès']);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}
?>