<?php
header('Content-Type: application/json');

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/auth.php';

requireAuth();

try {
    $total = (int) $pdo->query("SELECT COUNT(*) FROM parking_places")->fetchColumn();

    $occupied = (int) $pdo->query("
        SELECT COUNT(DISTINCT place_id)
        FROM reservations
        WHERE NOW() BETWEEN start_time AND end_time
    ")->fetchColumn();

    $free = $total - $occupied;

    $stmt = $pdo->query("
        SELECT DATE(start_time) AS day, COUNT(*) AS count
        FROM reservations
        WHERE start_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(start_time)
        ORDER BY day ASC
    ");
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ai = null;
    if (function_exists('getAIPayload')) {
        try {
            $ai = getAIPayload($pdo);
        } catch (Throwable $e) {
            error_log("AI error: " . $e->getMessage());
        }
    }

    echo json_encode([
        'success'  => true,
        'total'    => $total,
        'occupied' => $occupied,
        'free'     => $free,
        'history'  => $history,
        'ai'       => $ai,
    ]);

} catch (Throwable $e) {
    error_log("stats.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}