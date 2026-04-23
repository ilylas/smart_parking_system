<?php
ini_set('display_errors', '0');

require __DIR__ . "/../config/db.php";
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

try {
    $data     = json_decode(file_get_contents("php://input"), true) ?? [];
    $name     = trim($data['name']     ?? '');
    $email    = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $data['password'] ?? '';

    if (!$name || !$email || !$password) {
        echo json_encode(["success" => false, "message" => "All fields are required"]);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(["success" => false, "message" => "Password must be at least 6 characters"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Email already registered"]);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'user', NOW())")
        ->execute([$name, $email, $hashed]);

    echo json_encode(["success" => true, "message" => "Account created successfully"]);

} catch (Exception $e) {
    error_log("register error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Server error"]);
}
