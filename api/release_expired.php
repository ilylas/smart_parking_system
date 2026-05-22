<?php
ini_set('display_errors', '0');
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';

try {
    // Find expired active reservations
    $stmt = $pdo->query("
        SELECT id, place_id 
        FROM reservations 
        WHERE end_time <= NOW() 
        AND status = 'active'
    ");
    $expired = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    foreach ($expired as $res) {
        $pdo->prepare("UPDATE parking_places SET status = 'libre' WHERE id = ?")
            ->execute([$res['place_id']]);

        $pdo->prepare("UPDATE reservations SET status = 'completed' WHERE id = ?")
            ->execute([$res['id']]);

        $count++;
    }

    echo json_encode(['success' => true, 'released' => $count]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>