<?php
ini_set('display_errors', '0');
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';

$body           = json_decode(file_get_contents('php://input'), true);
$user_id        = (int)($body['user_id']        ?? 0);
$reservation_id = (int)($body['reservation_id'] ?? 0);
$place_id       = (int)($body['place_id']       ?? 0);

if (!$user_id || !$reservation_id || !$place_id) {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

try {
    // Verify ownership — status can be active OR upcoming
    $stmt = $pdo->prepare("
        SELECT id FROM reservations 
        WHERE id = ? AND user_id = ? AND status IN ('active','upcoming')
    ");
    $stmt->execute([$reservation_id, $user_id]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Reservation not found or already completed']);
        exit;
    }

    $pdo->beginTransaction();

    $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?")
        ->execute([$reservation_id]);

    $pdo->prepare("UPDATE parking_places SET status = 'libre' WHERE id = ?")
        ->execute([$place_id]);

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>