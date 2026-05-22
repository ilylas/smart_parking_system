<?php
/**
 * POST /api/addPlace.php — admin only
 * FIX: display_errors OFF.
 */
ini_set('display_errors', '0');

require __DIR__ . "/../config/db.php";
require __DIR__ . "/../config/auth.php";
header("Content-Type: application/json");

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

try {
    $data     = json_decode(file_get_contents("php://input"), true) ?? [];
    $numero   = trim($data['numero']   ?? '');
    $priorite = filter_var($data['priorite'] ?? 1, FILTER_VALIDATE_INT);

    if (!$numero)                           throw new Exception("Spot number is required");
    if ($priorite === false || $priorite < 1) throw new Exception("Invalid priority");

    $stmt = $pdo->prepare("SELECT id FROM parking_places WHERE numero = ?");
    $stmt->execute([$numero]);
    if ($stmt->fetch()) throw new Exception("Spot number already exists");

    $pdo->prepare("INSERT INTO parking_places (numero, status, priorite) VALUES (?, 'libre', ?)")
        ->execute([$numero, $priorite]);

    echo json_encode(["success" => true, "id" => $pdo->lastInsertId()]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
