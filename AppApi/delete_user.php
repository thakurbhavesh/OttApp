<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    include '../api/config.php';
    
    // Verify connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    // Read input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    $user_id = intval($data['user_id'] ?? 0);
    
    if ($user_id <= 0) {
        throw new Exception('Invalid user ID');
    }
    
    // Check if updated_at column exists (optional: remove if you added it)
    $col_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'updated_at'");
    $has_updated_at = mysqli_num_rows($col_check) > 0;
    
    $sql = "UPDATE users SET status = 'inactive'" . ($has_updated_at ? ", updated_at = NOW()" : "") . " WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Update failed: ' . mysqli_stmt_error($stmt));
    }
    
    $affected = mysqli_stmt_affected_rows($stmt);
    if ($affected === 0) {
        throw new Exception('User not found');
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Account deactivated']);
    
} catch (Exception $e) {
    error_log("Delete User Error: " . $e->getMessage()); // Log to server error log
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) mysqli_stmt_close($stmt);
    if (isset($conn)) mysqli_close($conn);
    ob_end_flush(); // Ensure no buffered HTML leaks
}
?>