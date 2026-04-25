<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$docteur_id = intval($_GET['docteur_id'] ?? 0);
$date = $_GET['date'] ?? '';

if (!$docteur_id || !$date) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit();
}

// Validate date
$dateObj = DateTime::createFromFormat('Y-m-d', $date);
if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
    echo json_encode(['success' => false, 'message' => 'Date invalide']);
    exit();
}

// Get day of week (1=Monday...7=Sunday, matching DB)
$dayOfWeek = $dateObj->format('N'); // 1=Mon, 7=Sun

// Get working hours for this doctor on this day
$stmt = $pdo->prepare("
    SELECT * FROM horaires_travail 
    WHERE docteur_id = ? AND jour_semaine = ? AND actif = 1
");
$stmt->execute([$docteur_id, $dayOfWeek]);
$horaire = $stmt->fetch();

if (!$horaire) {
    echo json_encode(['success' => true, 'creneaux' => [], 'message' => 'Pas de consultation ce jour']);
    exit();
}

// Generate all 30-min slots within working hours, excluding lunch break
$slots = [];
$slotDuration = 30; // minutes

$heureDebut = new DateTime($horaire['heure_debut']);
$heureFin   = new DateTime($horaire['heure_fin']);
$pauseDebut = $horaire['pause_debut'] ? new DateTime($horaire['pause_debut']) : null;
$pauseFin   = $horaire['pause_fin']   ? new DateTime($horaire['pause_fin'])   : null;

$current = clone $heureDebut;
while ($current < $heureFin) {
    $slotEnd = clone $current;
    $slotEnd->modify("+$slotDuration minutes");

    // Skip if slot overlaps with pause
    $inPause = $pauseDebut && $pauseFin && $current >= $pauseDebut && $current < $pauseFin;

    if (!$inPause) {
        $slots[] = $current->format('H:i');
    }
    $current->modify("+$slotDuration minutes");
}

// Get already-booked slots for this doctor on this date
$stmt = $pdo->prepare("
    SELECT heure_rdv FROM rendezvous 
    WHERE docteur_id = ? AND date_rdv = ? AND statut != 'annule'
");
$stmt->execute([$docteur_id, $date]);
$bookedRaw = $stmt->fetchAll(PDO::FETCH_COLUMN);
$bookedSlots = array_map(fn($t) => substr($t, 0, 5), $bookedRaw);

// Mark availability
$creneaux = array_map(fn($slot) => [
    'heure' => $slot,
    'disponible' => !in_array($slot, $bookedSlots)
], $slots);

echo json_encode(['success' => true, 'creneaux' => $creneaux]);
?>
