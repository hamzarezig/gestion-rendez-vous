<?php
require_once '../config/database.php';

header('Content-Type: application/json');

session_destroy();

echo json_encode(['success' => true, 'message' => 'Déconnexion réussie']);
?>