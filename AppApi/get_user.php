<?php
include '../api/config.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0; // sanitize

$sql = "SELECT * FROM users WHERE user_id = $user_id";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo json_encode($result->fetch_assoc());
} else {
    echo json_encode(["error" => "User not found"]);
}
?>
