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

// Function to fetch valid IDs from a table
function fetch_valid_ids($conn, $table, $id_column, $status_column = null) {
    $query = "SELECT $id_column FROM $table";
    if ($status_column) {
        $query .= " WHERE $status_column = 'active'";
    }
    $result = $conn->query($query);
    if ($result === false) {
        throw new Exception("Failed to fetch IDs from $table: " . $conn->error);
    }
    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int)$row[$id_column];
    }
    $result->free();
    return $ids;
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
$language_id = isset($_GET['language_id']) ? (int)sanitize_input($_GET['language_id']) : null;
$preference_id = isset($_GET['preference_id']) ? (int)sanitize_input($_GET['preference_id']) : null;
$category_id = isset($_GET['category_id']) ? (int)sanitize_input($_GET['category_id']) : null;
$main_category_id = isset($_GET['main_category_id']) ? (int)sanitize_input($_GET['main_category_id']) : null;

// Validate status
$valid_statuses = ['active', 'inactive'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid status. Use "active" or "inactive".']);
    exit;
}

// Fetch valid IDs dynamically from database
try {
    $valid_languages = fetch_valid_ids($conn, 'languages', 'language_id', 'status');
    $valid_preferences = fetch_valid_ids($conn, 'content_preferences', 'preference_id', 'status');
    $valid_categories = fetch_valid_ids($conn, 'categories', 'category_id', 'status');
    $valid_main_categories = fetch_valid_ids($conn, 'main_categories', 'category_id', 'status');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
    exit;
}

// Validate IDs
$invalid_params = [];
if ($language_id && !in_array($language_id, $valid_languages)) $invalid_params[] = "language_id";
if ($preference_id && !in_array($preference_id, $valid_preferences)) $invalid_params[] = "preference_id";
if ($category_id && !in_array($category_id, $valid_categories)) $invalid_params[] = "category_id";
if ($main_category_id && !in_array($main_category_id, $valid_main_categories)) $invalid_params[] = "main_category_id";

if (!empty($invalid_params)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid ' . implode(', ', $invalid_params) . '. Check valid IDs.']);
    exit;
}

// Build query with filters and select only requested fields
$query = "SELECT 
    c.content_id, c.title, c.thumbnail_url, c.trailer_url,
    l.name AS language_name,
    cp.preference_name,
    cat.name AS category_name,
    mc.name AS main_category_name,
    cc.cast_crew_id, cc.name AS cast_crew_name, cc.role, cc.image AS cast_crew_image,
    ms.season_number, ms.episode_number, ms.title AS episode_title, ms.description AS episode_description,
    ms.thumbnail_url AS episode_thumbnail_url, ms.video_url AS episode_video_url, ms.length AS episode_length,
    ms.release_date AS episode_release_date, ms.status AS episode_status
FROM content c
LEFT JOIN languages l ON c.language_id = l.language_id
LEFT JOIN content_preferences cp ON c.preference_id = cp.preference_id
LEFT JOIN categories cat ON c.category_id = cat.category_id
LEFT JOIN main_categories mc ON cat.main_category_id = mc.category_id
LEFT JOIN cast_crew cc ON c.content_id = cc.content_id
LEFT JOIN manage_selected ms ON c.content_id = ms.content_id AND ms.status = 'active'
WHERE c.status = ?";
$params = [$status];
$types = "s";

if ($language_id) {
    $query .= " AND c.language_id = ?";
    $params[] = $language_id;
    $types .= "i";
}
if ($preference_id) {
    $query .= " AND c.preference_id = ?";
    $params[] = $preference_id;
    $types .= "i";
}
if ($category_id) {
    $query .= " AND c.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}
if ($main_category_id) {
    $query .= " AND cat.main_category_id = ?";
    $params[] = $main_category_id;
    $types .= "i";
}

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
        $content_id = $row['content_id'];
        if (!isset($contents[$content_id])) {
            $contents[$content_id] = [
                'title' => $row['title'],
                'thumbnail_url' => $row['thumbnail_url'],
                'trailer_url' => $row['trailer_url'],
                'language_name' => $row['language_name'],
                'preference_name' => $row['preference_name'],
                'category_name' => $row['category_name'],
                'main_category_name' => $row['main_category_name'],
                'cast_crew' => [],
                'episodes' => []
            ];
        }

        // Add cast_crew if exists
        if ($row['cast_crew_id']) {
            $contents[$content_id]['cast_crew'][] = [
                'cast_crew_id' => $row['cast_crew_id'],
                'name' => $row['cast_crew_name'],
                'role' => $row['role'],
                'image' => $row['cast_crew_image']
            ];
        }

        // Add episode if exists (only active episodes due to query filter)
        if ($row['episode_title']) {
            $contents[$content_id]['episodes'][] = [
                'season_number' => $row['season_number'],
                'episode_number' => $row['episode_number'],
                'title' => $row['episode_title'],
                'description' => $row['episode_description'],
                'thumbnail_url' => $row['episode_thumbnail_url'],
                'video_url' => $row['episode_video_url'],
                'length' => $row['episode_length'],
                'release_date' => $row['episode_release_date'],
                'status' => $row['episode_status']
            ];
        }
    }

    $contents = array_values($contents); // Reset array keys

    if (empty($contents)) {
        echo json_encode(['status' => 'success', 'message' => 'No content found with the selected filters', 'data' => []], JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['status' => 'success', 'data' => $contents], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
    exit;
}

$stmt->close();
$conn->close();
?>