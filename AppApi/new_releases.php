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
$limit = isset($_GET['limit']) ? (int)sanitize_input($_GET['limit']) : 10; // Default limit
$page = isset($_GET['page']) ? (int)sanitize_input($_GET['page']) : 1; // Default page
$offset = ($page - 1) * $limit;

// Validate status
$valid_statuses = ['active', 'inactive'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid status. Use "active" or "inactive".']);
    exit;
}

// Validate limit
if ($limit < 1 || $limit > 100) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Limit must be between 1 and 100.']);
    exit;
}

// Current date and time
$current_date = date('Y-m-d H:i:s'); // e.g., "2025-09-07 00:08:00" based on current time

// Count total records for pagination
$count_query = "SELECT COUNT(*) AS total 
                FROM content 
                WHERE status = ? AND release_date >= ?";
$count_params = [$status, $current_date];
$count_types = "ss";

try {
    $count_stmt = $conn->prepare($count_query);
    if ($count_stmt === false) {
        throw new Exception("Prepare failed for count query: " . $conn->error);
    }
    $count_stmt->bind_param($count_types, ...$count_params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    $conn->close();
    exit;
}

// Build query to get new releases with joins
$query = "SELECT 
    c.content_id, 
    c.title, 
    c.description, 
    c.thumbnail_url, 
    c.video_url, 
    c.duration, 
    c.release_date, 
    c.created_at, 
    c.status, 
    c.content_type, 
    c.plan AS plan_type, 
    c.industry, 
    l.name AS language, 
    cp.preference_name AS preference, 
    cat.name AS category, 
    mc.name AS main_category 
FROM content c 
LEFT JOIN languages l ON c.language_id = l.language_id 
LEFT JOIN content_preferences cp ON c.preference_id = cp.preference_id 
LEFT JOIN categories cat ON c.category_id = cat.category_id 
LEFT JOIN main_categories mc ON cat.main_category_id = mc.category_id 
WHERE c.status = ? AND c.release_date >= ?";
$params = [$status, $current_date];
$types = "ss";

$query .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Prepare and execute query
$stmt = null;
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
        $new_releases[] = [
            'content_id' => $row['content_id'],
            'title' => $row['title'],
            'description' => $row['description'] ?? '',
            'thumbnail_url' => $row['thumbnail_url'] ?? null,
            'video_url' => $row['video_url'] ?? null,
            'duration' => $row['duration'] ?? null,
            'release_date' => $row['release_date'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'status' => $row['status'],
            'content_type' => $row['content_type'] ?? null,
            'plan_type' => $row['plan_type'],
            'industry' => $row['industry'] ?? 'Unknown',
            'language' => $row['language'] ?? 'Unknown',
            'preference' => $row['preference'] ?? 'Unknown',
            'category' => $row['category'] ?? 'Unknown',
            'main_category' => $row['main_category'] ?? 'Unknown'
        ];
    }

    // Prepare pagination metadata
    $total_pages = ceil($total_records / $limit);
    $response = [
        'status' => 'success',
        'data' => $new_releases,
        'pagination' => [
            'total_records' => $total_records,
            'current_page' => $page,
            'total_pages' => $total_pages,
            'limit' => $limit
        ]
    ];

    if (empty($new_releases)) {
        $response['message'] = 'No new releases found';
    }

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} finally {
    // Close statement and connection if they exist
    if ($stmt !== null) {
        $stmt->close();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>