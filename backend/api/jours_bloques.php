<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'docteur') {
    echo json_encode(['success' => false, 'message' => 'Accès refusé']); exit();
}

$stmt = $pdo->prepare("SELECT id FROM docteurs WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doc = $stmt->fetch();
if (!$doc) { echo json_encode(['success' => false, 'message' => 'Médecin introuvable']); exit(); }
$docteur_id = $doc['id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM jours_bloques WHERE docteur_id = ? ORDER BY date_bloquee");
    $stmt->execute([$docteur_id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $date = $data['date'] ?? '';
    $demi = $data['demi_journee'] ?? 'journee_entiere';
    $motif = $data['motif'] ?? '';

    if (empty($date)) { echo json_encode(['success' => false, 'message' => 'Date requise']); exit(); }

    try {
        $stmt = $pdo->prepare("INSERT INTO jours_bloques (docteur_id, date_bloquee, demi_journee, motif) VALUES (?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE motif = VALUES(motif)");
        $stmt->execute([$docteur_id, $date, $demi, $motif]);
        echo json_encode(['success' => true, 'message' => 'Jour bloqué']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM jours_bloques WHERE id = ? AND docteur_id = ?");
    $stmt->execute([$id, $docteur_id]);
    echo json_encode(['success' => true, 'message' => 'Jour débloqué']);
}
?>
