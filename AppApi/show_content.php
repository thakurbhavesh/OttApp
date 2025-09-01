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
$banner = isset($_GET['banner']) ? (int)sanitize_input($_GET['banner']) : null;
$top_shows = isset($_GET['top_shows']) ? (int)sanitize_input($_GET['top_shows']) : null;
$binge_worthy = isset($_GET['binge_worthy']) ? (int)sanitize_input($_GET['binge_worthy']) : null;
$bollywood_binge = isset($_GET['bollywood_binge']) ? (int)sanitize_input($_GET['bollywood_binge']) : null;
$dubbed_in_hindi = isset($_GET['dubbed_in_hindi']) ? (int)sanitize_input($_GET['dubbed_in_hindi']) : null;

// Validate status
$valid_statuses = ['active', 'inactive'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status. Use "active" or "inactive".']);
    exit;
}

// Build query with filters
$query = "SELECT content_id, title, description, category_id, thumbnail_url, video_url, duration, release_date, created_at, status, content_type, language_id, preference_id, trailer_url, banner, top_shows, binge_worthy, bollywood_binge, dubbed_in_hindi, plan, industry 
          FROM content 
          WHERE status = ?";
$params = [$status];
$types = "s";

if ($banner !== null) {
    $query .= " AND banner = ?";
    $params[] = $banner;
    $types .= "i";
}

if ($top_shows !== null) {
    $query .= " AND top_shows = ?";
    $params[] = $top_shows;
    $types .= "i";
}

if ($binge_worthy !== null) {
    $query .= " AND binge_worthy = ?";
    $params[] = $binge_worthy;
    $types .= "i";
}

if ($bollywood_binge !== null) {
    $query .= " AND bollywood_binge = ?";
    $params[] = $bollywood_binge;
    $types .= "i";
}

if ($dubbed_in_hindi !== null) {
    $query .= " AND dubbed_in_hindi = ?";
    $params[] = $dubbed_in_hindi;
    $types .= "i";
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
        echo json_encode(['status' => 'success', 'message' => 'No content found', 'data' => []]);
    } else {
        echo json_encode(['status' => 'success', 'data' => $contents]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>