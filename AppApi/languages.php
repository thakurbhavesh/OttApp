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
$language_id = isset($_GET['language_id']) ? (int)sanitize_input($_GET['language_id']) : null;

// Validate status
$valid_statuses = ['active', 'inactive'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status. Use "active" or "inactive".']);
    exit;
}

// Validate language_id (based on your languages table: 1=Hindi, 2=English, 3=Punjabi, 4=Kannada, 5=Malayalam, 6=Telugu, 7=Bhojpuri)
$valid_languages = [1, 2, 3, 4, 5, 6, 7];
if ($language_id && !in_array($language_id, $valid_languages)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid language ID. Use 1 (Hindi), 2 (English), 3 (Punjabi), 4 (Kannada), 5 (Malayalam), 6 (Telugu), or 7 (Bhojpuri).']);
    exit;
}

// Build query with filters
$query = "SELECT c.content_id, c.title, c.description, c.category_id, c.thumbnail_url, c.video_url, c.duration, c.release_date, c.created_at, c.status, c.content_type, c.language_id, c.preference_id, c.trailer_url, c.banner, c.top_shows, c.binge_worthy, c.bollywood_binge, c.dubbed_in_hindi, c.plan, c.industry, l.name AS language_name 
          FROM content c 
          LEFT JOIN languages l ON c.language_id = l.language_id 
          WHERE c.status = ?";
$params = [$status];
$types = "s";

if ($language_id) {
    $query .= " AND c.language_id = ?";
    $params[] = $language_id;
    $types .= "i";
}

// Prepare and execute query
try {
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $contents = [];
    while ($row = $result->fetch_assoc()) {
        $contents[] = $row;
    }

    if (empty($contents)) {
        echo json_encode(['status' => 'success', 'message' => 'No content found for the selected language', 'data' => []]);
    } else {
        echo json_encode(['status' => 'success', 'data' => $contents]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>