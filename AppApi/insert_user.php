<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../api/config.php';

// read JSON or form data
$data = json_decode(file_get_contents("php://input"), true) ?: $_POST;

// Generate unique username if not provided
$username = !empty($data['username']) ? $data['username'] : 'user_'.uniqid();

// Default coins = 100
$coins = 100;

$stmt = $conn->prepare(
  "INSERT INTO users (username, coins) VALUES (?, ?) 
   ON DUPLICATE KEY UPDATE username=username"
);
$stmt->bind_param("si", $username, $coins);

if ($stmt->execute()) {
    echo json_encode([
        "status"   => "success",
        "username" => $username,
        "user_id"  => $conn->insert_id
    ]);
} else {
    echo json_encode(["status"=>"error","error"=>$conn->error]);
}
