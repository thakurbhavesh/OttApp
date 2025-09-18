<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');

include '../api/config.php';

$api_key = "your_secure_api_key";
$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $received_api_key = $input['api_key'] ?? '';
    $user_id = $input['user_id'] ?? '';
    $content_id = $input['content_id'] ?? '';
    $action = $input['action'] ?? '';

    if ($received_api_key !== $api_key) {
        $response['message'] = 'Invalid API key';
    } elseif (!$user_id || !$content_id || !$action) {
        $response['message'] = 'Missing required parameters';
    } else {
        $user_id = filter_var($user_id, FILTER_VALIDATE_INT);
        $content_id = filter_var($content_id, FILTER_VALIDATE_INT);
        $action = filter_var($action, FILTER_SANITIZE_STRING);

        if ($user_id === false || $content_id === false || !in_array($action, ['like', 'unlike', 'watchlist_add', 'watchlist_remove', 'check'])) {
            $response['message'] = 'Invalid input data';
        } else {
            if ($action === 'check') {
                $check_sql = "SELECT type FROM user_preferences WHERE user_id = ? AND content_id = ?";
                $stmt_check = $conn->prepare($check_sql);
                $stmt_check->bind_param("ii", $user_id, $content_id);
                $stmt_check->execute();
                $result = $stmt_check->get_result();
                $preferences = [];
                while ($row = $result->fetch_assoc()) {
                    $preferences[$row['type']] = true;
                }
                $response = [
                    'status' => 'success',
                    'data' => [
                        'like' => isset($preferences['like']),
                        'watchlist' => isset($preferences['watchlist']),
                    ],
                ];
                $stmt_check->close();
            } else {
                $type = ($action === 'like' || $action === 'unlike') ? 'like' : 'watchlist';

                $conn->begin_transaction();

                try {
                    if ($action === 'like' || $action === 'watchlist_add') {
                        $check_sql = "SELECT id FROM user_preferences WHERE user_id = ? AND content_id = ? AND type = ?";
                        $stmt_check = $conn->prepare($check_sql);
                        $stmt_check->bind_param("iis", $user_id, $content_id, $type);
                        $stmt_check->execute();
                        $result = $stmt_check->get_result();

                        if ($result->num_rows > 0) {
                            $response['message'] = 'Already ' . ($type === 'like' ? 'liked' : 'in watchlist');
                        } else {
                            $insert_sql = "INSERT INTO user_preferences (user_id, content_id, type) VALUES (?, ?, ?)";
                            $stmt_insert = $conn->prepare($insert_sql);
                            $stmt_insert->bind_param("iis", $user_id, $content_id, $type);
                            $stmt_insert->execute();
                            $response['message'] = 'Successfully ' . ($type === 'like' ? 'liked' : 'added to watchlist');
                        }
                        $stmt_check->close();
                    } elseif ($action === 'unlike' || $action === 'watchlist_remove') {
                        $delete_sql = "DELETE FROM user_preferences WHERE user_id = ? AND content_id = ? AND type = ?";
                        $stmt_delete = $conn->prepare($delete_sql);
                        $stmt_delete->bind_param("iis", $user_id, $content_id, $type);
                        $stmt_delete->execute();
                        $response['message'] = ($stmt_delete->affected_rows > 0)
                            ? 'Successfully ' . ($type === 'like' ? 'unliked' : 'removed from watchlist')
                            : 'No ' . ($type === 'like' ? 'like' : 'watchlist') . ' to remove';
                        $stmt_delete->close();
                    }

                    $conn->commit();
                    $response['status'] = 'success';
                } catch (Exception $e) {
                    $conn->rollback();
                    $response['message'] = 'Transaction failed: ' . $e->getMessage();
                }
            }
        }
    }
} else {
    $response['message'] = 'Method not allowed';
}

echo json_encode($response);
$conn->close();
?>