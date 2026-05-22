<?php
ini_set('display_errors', '0');
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';

$user_id = (int)($_GET['user_id'] ?? 0);

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.start_time,
            r.end_time,
            r.price,
            r.status,
            r.created_at,
            p.numero,
            p.priorite,
            CASE 
                WHEN r.status = 'cancelled' THEN 'cancelled'
                WHEN r.end_time <= NOW()    THEN 'completed'
                WHEN r.start_time > NOW()   THEN 'upcoming'
                ELSE 'active'
            END AS computed_status
        FROM reservations r
        JOIN parking_places p ON r.place_id = p.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'bookings' => $bookings]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>