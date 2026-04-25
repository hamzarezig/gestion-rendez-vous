<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']); exit();
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: Fetch notifications ──────────────────────────────────────────────
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications WHERE user_id = ?
            ORDER BY created_at DESC LIMIT 50
        ");
        $stmt->execute([$userId]);
        $notifs = $stmt->fetchAll();

        $unread = array_filter($notifs, fn($n) => !$n['lu']);
        echo json_encode(['success' => true, 'data' => $notifs, 'unread_count' => count($unread)]);

    } elseif ($action === 'preferences') {
        // US-17: Get notification preferences
        $stmt = $pdo->prepare("SELECT notif_preferences FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $prefs = json_decode($user['notif_preferences'] ?? '{}', true) ?: [
            'email_reservation' => true,
            'email_annulation'  => true,
            'email_rappel'      => true,
            'app_reservation'   => true,
            'app_annulation'    => true
        ];
        echo json_encode(['success' => true, 'data' => $prefs]);
    }

// ── POST: Mark as read or update preferences ──────────────────────────────
} elseif ($method === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    if ($action === 'mark_read') {
        $id = intval($data['id'] ?? 0);
        if ($id) {
            $pdo->prepare("UPDATE notifications SET lu=1 WHERE id=? AND user_id=?")->execute([$id, $userId]);
        } else {
            $pdo->prepare("UPDATE notifications SET lu=1 WHERE user_id=?")->execute([$userId]);
        }
        echo json_encode(['success' => true]);

    } elseif ($action === 'save_preferences') {
        // US-17
        // Check if column exists, add if not (graceful migration)
        try {
            $pdo->query("SELECT notif_preferences FROM users LIMIT 1");
        } catch (Exception $e) {
            $pdo->exec("ALTER TABLE users ADD COLUMN notif_preferences TEXT DEFAULT NULL");
        }
        $prefs = json_encode($data['preferences'] ?? []);
        $pdo->prepare("UPDATE users SET notif_preferences=? WHERE id=?")->execute([$prefs, $userId]);
        echo json_encode(['success' => true, 'message' => 'Préférences enregistrées']);
    }
}
?>
