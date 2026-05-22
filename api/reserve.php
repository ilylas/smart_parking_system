<?php
ini_set('display_errors', '0');
header('Content-Type: application/json');

require __DIR__ . '/../config/db.php';

$body     = json_decode(file_get_contents('php://input'), true);
$user_id  = (int)($body['user_id']  ?? 0);
$place_id = (int)($body['place_id'] ?? 0);
$start    = $body['start_time'] ?? '';
$end      = $body['end_time']   ?? '';
$price    = (float)($body['price'] ?? 0);

if (!$user_id || !$place_id || !$start || !$end) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Check spot is still libre
    $stmt = $pdo->prepare("SELECT status FROM parking_places WHERE id = ?");
    $stmt->execute([$place_id]);
    $place = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$place) {
        echo json_encode(['success' => false, 'message' => 'Spot not found']);
        exit;
    }
    if ($place['status'] !== 'libre') {
        echo json_encode(['success' => false, 'message' => 'Spot is no longer available']);
        exit;
    }

    $pdo->beginTransaction();

    // Insert with status column
    $stmt = $pdo->prepare("
        INSERT INTO reservations (user_id, place_id, start_time, end_time, price, status)
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$user_id, $place_id, $start, $end, $price]);
    $reservation_id = $pdo->lastInsertId();

    // Mark spot occupied
    $pdo->prepare("
        UPDATE parking_places SET status = 'occupied', last_used = NOW() WHERE id = ?
    ")->execute([$place_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'reservation_id' => $reservation_id]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('reserve.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>