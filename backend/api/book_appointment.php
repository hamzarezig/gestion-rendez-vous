<?php
require_once '../config/database.php';
require_once '../services/NotificationService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']); exit();
}

$data       = json_decode(file_get_contents('php://input'), true);
$docteur_id = intval($data['docteur_id'] ?? 0);
$date_rdv   = trim($data['date_rdv']   ?? '');
$heure_rdv  = trim($data['heure_rdv']  ?? '');
$motif      = trim($data['motif']      ?? '');
$guest_nom    = trim($data['guest_nom']    ?? '');
$guest_prenom = trim($data['guest_prenom'] ?? '');
$guest_email  = trim($data['guest_email']  ?? '');
$guest_tel    = trim($data['guest_tel']    ?? '');

if (!$docteur_id || !$date_rdv || !$heure_rdv) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires sont requis']); exit();
}

$patient_id = null;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'patient') {
    $patient_id = $_SESSION['user_id'];
} else {
    if (empty($guest_nom) || empty($guest_prenom) || empty($guest_email)) {
        echo json_encode(['success' => false, 'message' => 'Prénom, nom et email requis']); exit();
    }
    if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email invalide']); exit();
    }
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$guest_email]);
    $existing = $stmt->fetch();
    if ($existing) {
        $patient_id = $existing['id'];
    } else {
        $tmp = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password, role) VALUES (?,?,?,?,'patient')");
        $stmt->execute([$guest_nom, $guest_prenom, $guest_email, $tmp]);
        $patient_id = $pdo->lastInsertId();
    }
}

$chk = $pdo->prepare("SELECT id FROM rendezvous WHERE docteur_id=? AND date_rdv=? AND heure_rdv=? AND statut!='annule'");
$chk->execute([$docteur_id, $date_rdv, $heure_rdv]);
if ($chk->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'Ce créneau est déjà pris']); exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO rendezvous (patient_id, docteur_id, date_rdv, heure_rdv, motif) VALUES (?,?,?,?,?)");
    $stmt->execute([$patient_id, $docteur_id, $date_rdv, $heure_rdv, $motif]);
    $rdvId = $pdo->lastInsertId();
    NotificationService::sendConfirmation($pdo, $rdvId, 'reservation');
    echo json_encode(['success' => true, 'message' => 'Rendez-vous confirmé avec succès !']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}
?>
