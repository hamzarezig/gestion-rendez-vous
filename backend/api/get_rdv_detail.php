<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']); exit();
}

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT r.*, d.id AS docteur_id FROM rendezvous r
    JOIN docteurs d ON r.docteur_id = d.id
    WHERE r.id = ? AND r.patient_id = ?
");
$stmt->execute([$id, $_SESSION['user_id']]);
$rdv = $stmt->fetch();

if (!$rdv) {
    echo json_encode(['success' => false, 'message' => 'RDV introuvable']); exit();
}
echo json_encode(['success' => true, 'data' => $rdv]);
?>
