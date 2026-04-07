<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$stmt = $pdo->query("
    SELECT 
        u.id as user_id,
        u.nom,
        u.prenom,
        u.email,
        d.id as docteur_id,
        d.specialite,
        d.telephone,
        d.cabinet_adresse,
        d.consultation_price
    FROM users u 
    JOIN docteurs d ON u.id = d.user_id 
    WHERE u.role = 'docteur'
");

$docteurs = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $docteurs]);
?>