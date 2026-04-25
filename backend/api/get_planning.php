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

$debut = $_GET['debut'] ?? date('Y-m-01');
$fin   = $_GET['fin']   ?? date('Y-m-t');

$stmt = $pdo->prepare("
    SELECT r.*, u.nom AS patient_nom, u.prenom AS patient_prenom, u.email AS patient_email
    FROM rendezvous r
    JOIN users u ON r.patient_id = u.id
    WHERE r.docteur_id = ? AND r.date_rdv BETWEEN ? AND ?
    ORDER BY r.date_rdv ASC, r.heure_rdv ASC
");
$stmt->execute([$docteur_id, $debut, $fin]);
$rdvs = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $rdvs]);
?>
