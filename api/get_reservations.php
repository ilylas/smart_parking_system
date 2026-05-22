<?php
/**
 * GET /api/get_reservations.php — admin only
 * FIX: display_errors OFF. FIX: parameterised status filter.
 */
ini_set('display_errors', '0');

require __DIR__ . "/../config/db.php";
require __DIR__ . "/../config/auth.php";
header("Content-Type: application/json");

requireAdmin();

try {
    $page   = max(1, (int)($_GET['page']  ?? 1));
    $limit  = min(50, (int)($_GET['limit'] ?? 20));
    $offset = ($page - 1) * $limit;
    $status = $_GET['status'] ?? null;
    $valid  = ['active', 'completed', 'cancelled'];

    // Build query safely
    if ($status && in_array($status, $valid, true)) {
        $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE status = ?");
        $totalStmt->execute([$status]);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT r.id, r.start_time, r.end_time, r.price, r.status, r.created_at,
                   u.name AS user_name, u.email AS user_email, pp.numero AS place_numero
            FROM reservations r
            JOIN users u          ON u.id  = r.user_id
            JOIN parking_places pp ON pp.id = r.place_id
            WHERE r.status = ?
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$status, $limit, $offset]);
    } else {
        $total = (int) $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT r.id, r.start_time, r.end_time, r.price, r.status, r.created_at,
                   u.name AS user_name, u.email AS user_email, pp.numero AS place_numero
            FROM reservations r
            JOIN users u          ON u.id  = r.user_id
            JOIN parking_places pp ON pp.id = r.place_id
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
    }

    echo json_encode([
        "success"      => true,
        "reservations" => $stmt->fetchAll(),
        "meta"         => [
            "total" => $total,
            "page"  => $page,
            "limit" => $limit,
            "pages" => (int) ceil($total / $limit),
        ],
    ]);

} catch (Exception $e) {
    error_log("get_reservations error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Server error"]);
}
