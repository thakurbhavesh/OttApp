<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow CORS for testing (adjust in production)

include '../api/config.php';

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Get and sanitize parameters
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : 'active';

// Validate status
$valid_statuses = ['active', 'inactive'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status. Use "active" or "inactive".']);
    exit;
}

// Current date and time
$current_date = date('Y-m-d H:i:s');

// Build query to get new releases
$query = "SELECT content_id, title, description, category_id, thumbnail_url, video_url, duration, release_date, created_at, status, content_type, language_id, preference_id, trailer_url, banner, top_shows, binge_worthy, bollywood_binge, dubbed_in_hindi, plan, industry 
          FROM content 
          WHERE status = ? AND release_date >= ?";
$params = [$status, $current_date];
$types = "ss";

// Prepare and execute query
try {
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $new_releases = [];
    while ($row = $result->fetch_assoc()) {
        $new_releases[] = $row;
    }

    if (empty($new_releases)) {
        echo json_encode(['status' => 'success', 'message' => 'No new releases found', 'data' => []]);
    } else {
        echo json_encode(['status' => 'success', 'data' => $new_releases]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>