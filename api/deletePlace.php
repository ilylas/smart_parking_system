<?php
/**
 * POST /api/deletePlace.php — admin only
 * FIX: display_errors OFF.
 */
ini_set('display_errors', '0');

require __DIR__ . "/../config/db.php";
require __DIR__ . "/../config/auth.php";
header("Content-Type: application/json");

requireAdmin();

try {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];
    $id   = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) throw new Exception("Invalid place ID");

    // Block if active reservations exist
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM reservations
        WHERE place_id = ? AND end_time > NOW() AND status = 'active'
    ");
    $stmt->execute([$id]);
    if ((int) $stmt->fetchColumn() > 0) {
        throw new Exception("Cannot delete a spot with active reservations");
    }

    $stmt = $pdo->prepare("DELETE FROM parking_places WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) throw new Exception("Spot not found");

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
