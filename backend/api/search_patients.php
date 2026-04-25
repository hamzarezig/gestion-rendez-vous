<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'docteur') {
    echo json_encode(['success' => false, 'message' => 'Accès refusé']); exit();
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode(['success' => true, 'data' => []]); exit(); }

$like = "%$q%";
$stmt = $pdo->prepare("
    SELECT id, nom, prenom, email FROM users 
    WHERE role = 'patient' AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)
    LIMIT 10
");
$stmt->execute([$like, $like, $like]);
echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
?>
