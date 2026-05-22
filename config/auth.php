<?php

function requireAuth(): array
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        header("Content-Type: application/json");
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }

    return [
        'user_id' => $_SESSION['user_id'],
        'name'    => $_SESSION['name'] ?? '',
        'role'    => $_SESSION['role'] ?? 'user',
    ];
}

function requireAdmin(): array
{
    $user = requireAuth();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        header("Content-Type: application/json");
        echo json_encode(["success" => false, "message" => "Forbidden"]);
        exit;
    }
    return $user;
}
