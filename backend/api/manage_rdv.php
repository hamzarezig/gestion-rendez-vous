<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']); exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$role   = $_SESSION['role'];
$userId = $_SESSION['user_id'];

// ── POST: Add manual appointment (doctor/secretary only) ──────────────────
if ($method === 'POST') {
    if ($role !== 'docteur') {
        echo json_encode(['success' => false, 'message' => 'Réservé aux médecins']); exit();
    }

    $stmt = $pdo->prepare("SELECT id FROM docteurs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $doc = $stmt->fetch();
    $docteur_id = $doc['id'];

    $data       = json_decode(file_get_contents('php://input'), true);
    $patient_id = intval($data['patient_id'] ?? 0);
    $date_rdv   = $data['date_rdv'] ?? '';
    $heure_rdv  = $data['heure_rdv'] ?? '';
    $motif      = $data['motif'] ?? '';
    $statut     = $data['statut'] ?? 'confirme';

    if (!$patient_id || !$date_rdv || !$heure_rdv) {
        echo json_encode(['success' => false, 'message' => 'Champs requis manquants']); exit();
    }

    // Check slot
    $chk = $pdo->prepare("SELECT id FROM rendezvous WHERE docteur_id=? AND date_rdv=? AND heure_rdv=? AND statut!='annule'");
    $chk->execute([$docteur_id, $date_rdv, $heure_rdv]);
    if ($chk->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Créneau déjà pris']); exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO rendezvous (patient_id, docteur_id, date_rdv, heure_rdv, motif, statut) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$patient_id, $docteur_id, $date_rdv, $heure_rdv, $motif, $statut]);
        echo json_encode(['success' => true, 'message' => 'Rendez-vous ajouté', 'id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

// ── PUT: Edit appointment ─────────────────────────────────────────────────
} elseif ($method === 'PUT') {
    $data  = json_decode(file_get_contents('php://input'), true);
    $id    = intval($data['id'] ?? 0);

    // Fetch existing appointment to check ownership
    $rdv = $pdo->prepare("SELECT r.*, d.user_id AS doc_user_id FROM rendezvous r JOIN docteurs d ON r.docteur_id=d.id WHERE r.id=?");
    $rdv->execute([$id]);
    $rdv = $rdv->fetch();

    if (!$rdv) { echo json_encode(['success' => false, 'message' => 'RDV introuvable']); exit(); }

    $isOwnerDoc     = ($role === 'docteur' && $rdv['doc_user_id'] == $userId);
    $isOwnerPatient = ($role === 'patient' && $rdv['patient_id'] == $userId);

    if (!$isOwnerDoc && !$isOwnerPatient) {
        echo json_encode(['success' => false, 'message' => 'Accès refusé']); exit();
    }

    $date_rdv  = $data['date_rdv']  ?? $rdv['date_rdv'];
    $heure_rdv = $data['heure_rdv'] ?? $rdv['heure_rdv'];
    $motif     = $data['motif']     ?? $rdv['motif'];
    $statut    = $data['statut']    ?? $rdv['statut'];

    // If patient, they can only change date/heure/motif, not statut (except annule)
    if ($isOwnerPatient && $statut !== 'annule' && $statut !== $rdv['statut']) {
        $statut = $rdv['statut'];
    }

    // Check new slot is free (if date/heure changed)
    if ($date_rdv !== $rdv['date_rdv'] || $heure_rdv !== $rdv['heure_rdv']) {
        $chk = $pdo->prepare("SELECT id FROM rendezvous WHERE docteur_id=? AND date_rdv=? AND heure_rdv=? AND statut!='annule' AND id!=?");
        $chk->execute([$rdv['docteur_id'], $date_rdv, $heure_rdv, $id]);
        if ($chk->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Ce créneau est déjà pris']); exit();
        }
    }

    $stmt = $pdo->prepare("UPDATE rendezvous SET date_rdv=?, heure_rdv=?, motif=?, statut=? WHERE id=?");
    $stmt->execute([$date_rdv, $heure_rdv, $motif, $statut, $id]);
    echo json_encode(['success' => true, 'message' => 'Rendez-vous mis à jour']);

// ── DELETE: Cancel appointment ────────────────────────────────────────────
} elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = intval($data['id'] ?? 0);

    $rdv = $pdo->prepare("SELECT r.*, d.user_id AS doc_user_id FROM rendezvous r JOIN docteurs d ON r.docteur_id=d.id WHERE r.id=?");
    $rdv->execute([$id]);
    $rdv = $rdv->fetch();

    if (!$rdv) { echo json_encode(['success' => false, 'message' => 'RDV introuvable']); exit(); }

    $isOwnerDoc     = ($role === 'docteur' && $rdv['doc_user_id'] == $userId);
    $isOwnerPatient = ($role === 'patient' && $rdv['patient_id'] == $userId);

    if (!$isOwnerDoc && !$isOwnerPatient) {
        echo json_encode(['success' => false, 'message' => 'Accès refusé']); exit();
    }

    $stmt = $pdo->prepare("UPDATE rendezvous SET statut='annule' WHERE id=?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Rendez-vous annulé']);
}
?>
