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

$stmt = $pdo->prepare("SELECT * FROM horaires_travail WHERE docteur_id = ? ORDER BY jour_semaine");
$stmt->execute([$doc['id']]);
$horaires = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $horaires]);
?>
