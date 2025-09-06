<?php
// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow CORS for testing (adjust in production)
// In production, restrict to specific origins, e.g.:
// header('Access-Control-Allow-Origin: https://yourdomain.com');
// header('Access-Control-Allow-Methods: GET');
// header('Access-Control-Allow-Headers: X-API-KEY');

include '../api/config.php';

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function for basic rate limiting (file-based for localhost)
function is_rate_limited($ip, $limit = 100, $window = 3600) {
    $file = 'rate_limit_' . md5($ip) . '.txt';
    $current_time = time();
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if ($data['time'] > $current_time - $window) {
            if ($data['count'] >= $limit) {
                return true; // Rate limit exceeded
            }
            $data['count']++;
        } else {
            $data = ['time' => $current_time, 'count' => 1];
        }
    } else {
        $data = ['time' => $current_time, 'count' => 1];
    }
    
    file_put_contents($file, json_encode($data));
    return false;
}

// API key validation
$api_key = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : (isset($_GET['api_key']) ? $_GET['api_key'] : null);
$valid_api_key = 'your_secure_api_key'; // Store in config.php or environment variable in production
if (!$api_key || $api_key !== $valid_api_key) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing API key']);
    exit;
}

// Rate limiting check
$client_ip = $_SERVER['REMOTE_ADDR'];
if (is_rate_limited($client_ip)) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Rate limit exceeded. Try again later.']);
    exit;
}

// Get and sanitize parameters
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : 'active';
$plan = isset($_GET['plan']) ? sanitize_input($_GET['plan']) : null;

// Validate status
$valid_statuses = ['active', 'inactive'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid status. Use "active" or "inactive".']);
    exit;
}

// Validate plan (optional, based on sample data: free, paid)
$valid_plans = ['free', 'paid'];
if ($plan && !in_array(strtolower($plan), $valid_plans)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid plan. Use "free" or "paid".']);
    exit;
}

// Build query with filters
$query = "SELECT content_id, title, description, category_id, thumbnail_url, video_url, duration, release_date, created_at, status, content_type, language_id, preference_id, trailer_url, banner, top_shows, binge_worthy, bollywood_binge, dubbed_in_hindi, plan, industry 
          FROM content 
          WHERE status = ?";
$params = [$status];
$types = "s";

if ($plan) {
    $query .= " AND plan = ?";
    $params[] = strtolower($plan);
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
        echo json_encode(['status' => 'success', 'message' => 'No content found for the selected plan', 'data' => []]);
    } else {
        echo json_encode(['status' => 'success', 'data' => $contents]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>