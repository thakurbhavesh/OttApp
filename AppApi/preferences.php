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
$preference_id = isset($_GET['preference_id']) ? (int)sanitize_input($_GET['preference_id']) : null;

// Validate status
$valid_statuses = ['active', 'inactive'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status. Use "active" or "inactive".']);
    exit;
}

// Validate preference_id (based on your content_preferences table: 1=Adult, 2=Kids, 3=Family, 4=Teen, 5=General)
$valid_preferences = [1, 2, 3, 4, 5];
if ($preference_id && !in_array($preference_id, $valid_preferences)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid preference ID. Use 1 (Adult), 2 (Kids), 3 (Family), 4 (Teen), or 5 (General).']);
    exit;
}

// Build query with filters
$query = "SELECT c.content_id, c.title, c.description, c.category_id, c.thumbnail_url, c.video_url, c.duration, c.release_date, c.created_at, c.status, c.content_type, c.language_id, c.preference_id, c.trailer_url, c.banner, c.top_shows, c.binge_worthy, c.bollywood_binge, c.dubbed_in_hindi, c.plan, c.industry, cp.preference_name 
          FROM content c 
          LEFT JOIN content_preferences cp ON c.preference_id = cp.preference_id 
          WHERE c.status = ?";
$params = [$status];
$types = "s";

if ($preference_id) {
    $query .= " AND c.preference_id = ?";
    $params[] = $preference_id;
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
        echo json_encode(['status' => 'success', 'message' => 'No content found for the selected preference', 'data' => []]);
    } else {
        echo json_encode(['status' => 'success', 'data' => $contents]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>