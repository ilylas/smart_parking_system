<?php
/**
 * GET /api/getPlaces.php
 * FIX: display_errors OFF.
 */
ini_set('display_errors', '0');

require __DIR__ . "/../config/db.php";
require __DIR__ . "/../config/auth.php";
header("Content-Type: application/json");

requireAuth();

try {
    $status = $_GET['status'] ?? null;
    $valid  = ['libre', 'occupied'];

    if ($status && !in_array($status, $valid, true)) {
        echo json_encode(["success" => false, "message" => "Invalid status filter"]);
        exit;
    }

    if ($status) {
        $stmt = $pdo->prepare("SELECT * FROM parking_places WHERE status = ? ORDER BY priorite ASC");
        $stmt->execute([$status]);
    } else {
        $stmt = $pdo->query("SELECT * FROM parking_places ORDER BY priorite ASC");
    }

    $places   = $stmt->fetchAll();
    $total    = count($places);
    $occupied = count(array_filter($places, fn($p) => $p['status'] === 'occupied'));

    echo json_encode([
        "success" => true,
        "places"  => $places,
        "meta"    => ["total" => $total, "occupied" => $occupied, "free" => $total - $occupied],
    ]);

} catch (Exception $e) {
    error_log("getPlaces error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Server error"]);
}
