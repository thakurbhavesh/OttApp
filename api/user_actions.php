<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// CSRF validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']));
}

// Validate action
$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid action'];

if ($action === 'toggle_status' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("SELECT status FROM auth_users WHERE auth_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $new_status = ($result['status'] === 'active') ? 'inactive' : 'active';
    $stmt->close();

    $stmt = $conn->prepare("UPDATE auth_users SET status = ? WHERE auth_id = ?");
    $stmt->bind_param("si", $new_status, $id);
    if ($stmt->execute()) {
        $response = ['status' => 'success', 'message' => 'Status updated'];
    } else {
        $response = ['status' => 'error', 'message' => 'Failed to update status'];
    }
    $stmt->close();
} elseif ($action === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    // Prevent self-deletion
    if ($id === $_SESSION['user_id']) {
        $response = ['status' => 'error', 'message' => 'Cannot delete your own account'];
    } else {
        $stmt = $conn->prepare("DELETE FROM auth_users WHERE auth_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'User deleted'];
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to delete user'];
        }
        $stmt->close();
    }
} elseif ($action === 'edit' && isset($_POST['id'], $_POST['username'], $_POST['email'], $_POST['role'])) {
    $id = (int)$_POST['id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'] === 'Admin' ? 'Admin' : 'User';

    // Validate inputs
    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $response = ['status' => 'error', 'message' => 'Invalid username'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = ['status' => 'error', 'message' => 'Invalid email'];
    } else {
        // Check for duplicate username/email (excluding current user)
        $stmt = $conn->prepare("SELECT auth_id FROM auth_users WHERE (username = ? OR email = ?) AND auth_id != ?");
        $stmt->bind_param("ssi", $username, $email, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $response = ['status' => 'error', 'message' => 'Username or email already exists'];
        } else {
            $stmt = $conn->prepare("UPDATE auth_users SET username = ?, email = ?, role = ? WHERE auth_id = ?");
            $stmt->bind_param("sssi", $username, $email, $role, $id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'User updated'];
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to update user'];
            }
        }
        $stmt->close();
    }
}

echo json_encode($response);
?>