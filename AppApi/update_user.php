<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include '../api/config.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["status"=>"error","error"=>"No JSON data received"]);
    exit;
}

$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
if ($user_id <= 0) {
    echo json_encode(["status"=>"error","error"=>"Missing or invalid user_id"]);
    exit;
}

// Unique checks
$unique_fields = ['username', 'email', 'phone'];
foreach ($unique_fields as $field) {
    if (!empty($data[$field])) {
        $val = $conn->real_escape_string($data[$field]);
        $check = $conn->query("SELECT user_id FROM users WHERE $field = '$val' AND user_id != $user_id");
        if ($check && $check->num_rows > 0) {
            echo json_encode(["status"=>"error","error"=> ucfirst($field)." already exists"]);
            exit;
        }
    }
}

// Allowed columns to update
$fields = ['username','name','email','phone','gender','dob','coins','subscription_status','plan_type','subscription_end_date','status'];
$updates = [];

foreach ($fields as $f) {
    if (array_key_exists($f, $data)) {
        $val = $conn->real_escape_string($data[$f]);
        $updates[] = "$f = '$val'";
    }
}

if (empty($updates)) {
    echo json_encode(["status"=>"error","error"=>"No valid fields to update"]);
    exit;
}

$sql = "UPDATE users SET ".implode(', ', $updates)." WHERE user_id = $user_id";

if ($conn->query($sql)) {
    echo json_encode(["status"=>"success","message"=>"Profile updated successfully"]);
} else {
    echo json_encode(["status"=>"error","error"=>$conn->error]);
}
?>
