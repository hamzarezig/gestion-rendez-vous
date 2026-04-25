<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$search = trim($_GET['q'] ?? '');
$specialite = trim($_GET['specialite'] ?? '');
$localisation = trim($_GET['localisation'] ?? '');

// Build dynamic query
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(u.nom LIKE ? OR u.prenom LIKE ? OR CONCAT(u.prenom, ' ', u.nom) LIKE ? OR CONCAT(u.nom, ' ', u.prenom) LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($specialite)) {
    $conditions[] = "d.specialite LIKE ?";
    $params[] = "%$specialite%";
}

if (!empty($localisation)) {
    $conditions[] = "d.cabinet_adresse LIKE ?";
    $params[] = "%$localisation%";
}

$where = !empty($conditions) ? 'AND ' . implode(' AND ', $conditions) : '';

$sql = "
    SELECT 
        u.id as user_id,
        u.nom,
        u.prenom,
        u.email,
        d.id as docteur_id,
        d.specialite,
        d.telephone,
        d.cabinet_adresse,
        d.consultation_price,
        d.description
    FROM users u 
    JOIN docteurs d ON u.id = d.user_id 
    WHERE u.role = 'docteur'
    $where
    ORDER BY u.nom ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$docteurs = $stmt->fetchAll();

// Get distinct specialties for filter dropdown
$specStmt = $pdo->query("SELECT DISTINCT specialite FROM docteurs ORDER BY specialite ASC");
$specialites = $specStmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'success' => true,
    'data' => $docteurs,
    'specialites' => $specialites,
    'total' => count($docteurs)
]);
?>
