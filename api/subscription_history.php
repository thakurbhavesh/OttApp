<?php
header('Content-Type: application/json');
include 'config.php';

$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];

    try {
        $stmt = $conn->prepare("SELECT * FROM subscription_history WHERE user_id = ? ORDER BY change_date DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $history = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if ($history) {
            $response = ['status' => 'success', 'data' => $history];
        } else {
            $response = ['status' => 'success', 'data' => [], 'message' => 'No history found'];
        }
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response);
$conn->close();
?>