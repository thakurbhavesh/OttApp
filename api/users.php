<?php
header('Content-Type: application/json');
include 'config.php';

$response = ['status' => 'error', 'message' => 'Invalid action'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $changed_by = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

    try {
        $conn->begin_transaction();

        switch ($action) {
            case 'add_user':
                $username = mysqli_real_escape_string($conn, $_POST['username']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $subscription_status = mysqli_real_escape_string($conn, $_POST['subscription_status']);
                $status = mysqli_real_escape_string($conn, $_POST['status']);
                $created_at = date('Y-m-d H:i:s');

                $stmt = $conn->prepare("INSERT INTO users (username, email, subscription_status, status, created_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $username, $email, $subscription_status, $status, $created_at);
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    // Log initial subscription (no end date or price yet)
                    $stmt_history = $conn->prepare("INSERT INTO subscription_history (user_id, subscription_status, changed_by, notes) VALUES (?, ?, ?, 'Initial subscription')");
                    $stmt_history->bind_param("iss", $user_id, $subscription_status, $changed_by);
                    $stmt_history->execute();
                    $stmt_history->close();
                    $response = ['status' => 'success', 'message' => 'User added successfully'];
                } else {
                    $response['message'] = 'Failed to add user';
                }
                $stmt->close();
                break;

            case 'update_user':
                $user_id = (int)$_POST['user_id'];
                $username = mysqli_real_escape_string($conn, $_POST['username']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $subscription_status = mysqli_real_escape_string($conn, $_POST['subscription_status']);
                $status = mysqli_real_escape_string($conn, $_POST['status']);

                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, subscription_status = ?, status = ? WHERE user_id = ?");
                $stmt->bind_param("ssssi", $username, $email, $subscription_status, $status, $user_id);
                if ($stmt->execute()) {
                    // Log subscription change if status changed
                    $stmt_current = $conn->prepare("SELECT subscription_status FROM users WHERE user_id = ?");
                    $stmt_current->bind_param("i", $user_id);
                    $stmt_current->execute();
                    $current = $stmt_current->get_result()->fetch_assoc();
                    $stmt_current->close();

                    if ($current['subscription_status'] != $subscription_status) {
                        $stmt_history = $conn->prepare("INSERT INTO subscription_history (user_id, subscription_status, changed_by, notes) VALUES (?, ?, ?, 'Subscription updated')");
                        $stmt_history->bind_param("iss", $user_id, $subscription_status, $changed_by);
                        $stmt_history->execute();
                        $stmt_history->close();
                    }
                    $response = ['status' => 'success', 'message' => 'User updated successfully'];
                } else {
                    $response['message'] = 'Failed to update user';
                }
                $stmt->close();
                break;

            case 'delete_user':
                $user_id = (int)$_POST['user_id'];
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $response = ['status' => 'success', 'message' => 'User deleted successfully'];
                } else {
                    $response['message'] = 'Failed to delete user';
                }
                $stmt->close();
                break;

            case 'toggle_status':
                $user_id = (int)$_POST['user_id'];
                $current_status = $_POST['current_status'];
                $new_status = $current_status == 'active' ? 'inactive' : 'active';

                $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
                $stmt->bind_param("si", $new_status, $user_id);
                if ($stmt->execute()) {
                    $response = ['status' => 'success', 'message' => 'Status toggled successfully'];
                } else {
                    $response['message'] = 'Failed to toggle status';
                }
                $stmt->close();
                break;
        }

        if ($response['status'] === 'success') {
            $conn->commit();
            header('Location: ../admin/manage_users.php?message=' . urlencode($response['message']));
            exit;
        } else {
            $conn->rollback();
        }
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response);
$conn->close();
?>