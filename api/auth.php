<?php
ob_start(); // Start output buffering
header('Content-Type: application/json');
include 'config.php';

$action = $_POST['action'] ?? '';

if ($action == 'register') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        ob_end_flush();
        exit;
    }

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT * FROM auth_users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username or email already exists']);
        $stmt->close();
        ob_end_flush();
        exit;
    }
    $stmt->close();

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO auth_users (username, email, password, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
    $stmt->bind_param("sss", $username, $email, $password_hash);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User registered successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed']);
    }
    $stmt->close();
}

if ($action == 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        ob_end_flush();
        exit;
    }

    // Check user
    $stmt = $conn->prepare("SELECT * FROM auth_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password']) && $user['status'] == 'active') {
            session_start();
            $_SESSION['user_id'] = $user['auth_id']; // Use auth_id instead of user_id
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            echo json_encode(['status' => 'success', 'message' => 'Login successful']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid password or inactive account']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
    $stmt->close();
}

if ($action == 'logout') {
    session_start();
    session_unset();
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'Logged out']);
    header('Location: ../admin/login.php');
    ob_end_flush();
    exit;
}

$conn->close();
ob_end_flush(); // Flush the output buffer
?>