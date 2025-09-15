<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../api/config.php';

// Read raw input
$input = file_get_contents('php://input');
error_log("Raw input received: " . $input); // Log for debugging

if (empty($input)) {
    echo json_encode(['status' => 'error', 'error' => 'No input data received']);
    exit;
}

// Decode JSON
$data = json_decode($input, true);

// Check for JSON decode error
if (json_last_error() !== JSON_ERROR_NONE) {
    $error = json_last_error_msg();
    error_log("JSON decode error: " . $error);
    echo json_encode(['status' => 'error', 'error' => 'Invalid JSON: ' . $error]);
    exit;
}

error_log("Decoded data: " . print_r($data, true)); // Log decoded data

if (!isset($data['user_id']) || !isset($data['plan_id']) || !isset($data['plan_type']) || !isset($data['price']) || !isset($data['subscription_end_date'])) {
    echo json_encode(['status' => 'error', 'error' => 'Missing required fields']);
    exit;
}

$user_id = intval($data['user_id']);
$plan_id = intval($data['plan_id']);
$plan_type = $data['plan_type'];
$price = floatval($data['price']);
$subscription_status = 'paid';
$subscription_end_date = $data['subscription_end_date'];

// Validate subscription_end_date format (basic check)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $subscription_end_date)) {
    echo json_encode(['status' => 'error', 'error' => 'Invalid subscription end date format']);
    exit;
}

// Start transaction
mysqli_autocommit($conn, false);

try {
    // Update users table
    $update_user_sql = "UPDATE users SET 
                        subscription_status = ?, 
                        plan_type = ?, 
                        subscription_end_date = ?,
                        coins = COALESCE(coins, 0) + 100
                        WHERE user_id = ?";
    
    $update_stmt = mysqli_prepare($conn, $update_user_sql);
    if (!$update_stmt) {
        throw new Exception('Prepare failed for update: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($update_stmt, 'sssi', $subscription_status, $plan_type, $subscription_end_date, $user_id);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception('Failed to update user subscription: ' . mysqli_stmt_error($update_stmt));
    }
    
    // Insert into subscription_history
    $history_sql = "INSERT INTO subscription_history (user_id, plan_id, subscription_status, price, subscription_end_date, change_date, changed_by, notes) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 'system', 'New subscription purchased')";
    
    $history_stmt = mysqli_prepare($conn, $history_sql);
    if (!$history_stmt) {
        throw new Exception('Prepare failed for history: ' . mysqli_error($conn));
    }
    // Corrected bind_param: i (user_id), i (plan_id), s (status), d (price), s (end_date)
    mysqli_stmt_bind_param($history_stmt, 'iisds', $user_id, $plan_id, $subscription_status, $price, $subscription_end_date);
    
    if (!mysqli_stmt_execute($history_stmt)) {
        throw new Exception('Failed to insert subscription history: ' . mysqli_stmt_error($history_stmt));
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode(['status' => 'success', 'message' => 'Subscription activated successfully']);
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    error_log("Subscription error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
} finally {
    if (isset($update_stmt)) {
        mysqli_stmt_close($update_stmt);
    }
    if (isset($history_stmt)) {
        mysqli_stmt_close($history_stmt);
    }
    mysqli_autocommit($conn, true);
}

mysqli_close($conn);
?>