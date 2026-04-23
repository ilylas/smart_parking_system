<?php
/**
 * POST /api/login_process.php
 * FIX: display_errors OFF.
 * FIX: Session started and user stored server-side.
 */
ini_set('display_errors', '0');

require __DIR__ . "/../config/db.php";
header("Content-Type: application/json");

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

try {
    $data     = json_decode(file_get_contents("php://input"), true) ?? [];
    $email    = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $data['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(["success" => false, "message" => "Email and password are required"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'] ?? 'user';

        echo json_encode([
            "success" => true,
            "user" => [
                "id"   => $user['id'],
                "name" => $user['name'],
                "role" => $user['role'] ?? 'user',
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid credentials"]);
    }

} catch (Exception $e) {
    error_log("login error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Server error"]);
}
