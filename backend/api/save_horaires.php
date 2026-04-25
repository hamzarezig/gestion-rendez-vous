<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']); exit();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'docteur') {
    echo json_encode(['success' => false, 'message' => 'Accès refusé']); exit();
}

// Get docteur_id for this user
$stmt = $pdo->prepare("SELECT id FROM docteurs WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doc = $stmt->fetch();
if (!$doc) { echo json_encode(['success' => false, 'message' => 'Médecin introuvable']); exit(); }
$docteur_id = $doc['id'];

$data = json_decode(file_get_contents('php://input'), true);
$horaires = $data['horaires'] ?? [];

try {
    $pdo->beginTransaction();
    // Delete existing schedules for this doctor
    $pdo->prepare("DELETE FROM horaires_travail WHERE docteur_id = ?")->execute([$docteur_id]);

    $stmt = $pdo->prepare("
        INSERT INTO horaires_travail (docteur_id, jour_semaine, heure_debut, heure_fin, pause_debut, pause_fin, actif)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($horaires as $h) {
        if (empty($h['actif'])) continue;
        $stmt->execute([
            $docteur_id,
            intval($h['jour_semaine']),
            $h['heure_debut'],
            $h['heure_fin'],
            $h['pause_debut'] ?: null,
            $h['pause_fin']   ?: null,
            1
        ]);
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Horaires enregistrés avec succès']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}
?>
