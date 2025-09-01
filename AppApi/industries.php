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
$industry = isset($_GET['industry']) ? sanitize_input($_GET['industry']) : null;

// Validate status
$valid_statuses = ['active', 'inactive'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status. Use "active" or "inactive".']);
    exit;
}

// Validate industry (optional, based on sample data: Hollywood, Bollywood, Punjabi)
$valid_industries = ['Hollywood', 'Bollywood', 'Punjabi'];
if ($industry && !in_array(ucwords(strtolower($industry)), $valid_industries)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid industry. Use "Hollywood", "Bollywood", or "Punjabi".']);
    exit;
}

// Build query with filters
$query = "SELECT content_id, title, description, category_id, thumbnail_url, video_url, duration, release_date, created_at, status, content_type, language_id, preference_id, trailer_url, banner, top_shows, binge_worthy, bollywood_binge, dubbed_in_hindi, plan, industry 
          FROM content 
          WHERE status = ?";
$params = [$status];
$types = "s";

if ($industry) {
    $query .= " AND industry = ?";
    $params[] = ucwords(strtolower($industry)); // Standardize case (e.g., "hollywood" -> "Hollywood")
    $types .= "s";
}

// Prepare and execute query
try {
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $contents = [];
    while ($row = $result->fetch_assoc()) {
        $contents[] = $row;
    }

    if (empty($contents)) {
        echo json_encode(['status' => 'success', 'message' => 'No content found for the selected industry', 'data' => []]);
    } else {
        echo json_encode(['status' => 'success', 'data' => $contents]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>