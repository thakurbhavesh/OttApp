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
$content_id = isset($_GET['content_id']) ? (int)sanitize_input($_GET['content_id']) : null;
$user_id = isset($_GET['user_id']) ? (int)sanitize_input($_GET['user_id']) : null;

// Validate required parameters
if (!$content_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'content_id is required.']);
    exit;
}

// Build query to fetch content, manage_selected (only active records), and cast_crew
$query = "SELECT 
    c.content_id, c.title, c.description, c.category_id, c.thumbnail_url, c.duration, c.release_date, c.created_at, c.status, c.content_type, c.language_id, c.preference_id, c.trailer_url, c.banner, c.top_shows, c.binge_worthy, c.bollywood_binge, c.dubbed_in_hindi, c.plan, c.industry,
    mc.name AS main_category_name, cat.name AS category_name, l.name AS language_name, cp.preference_name,
    ms.id AS manage_selected_id, ms.season_number, ms.episode_number, ms.title AS episode_title, ms.description AS episode_description, ms.thumbnail_url AS episode_thumbnail_url, ms.video_url AS episode_video_url, ms.length, ms.release_date AS episode_release_date, ms.status AS episode_status, ms.created_at AS episode_created_at,
    cc.name AS cast_crew_name, cc.role
FROM content c
LEFT JOIN categories cat ON c.category_id = cat.category_id
LEFT JOIN main_categories mc ON cat.main_category_id = mc.category_id
LEFT JOIN languages l ON c.language_id = l.language_id
LEFT JOIN content_preferences cp ON c.preference_id = cp.preference_id
LEFT JOIN manage_selected ms ON c.content_id = ms.content_id AND ms.status = 'active'
LEFT JOIN cast_crew cc ON c.content_id = cc.content_id
WHERE c.content_id = ?";

$params = [$content_id];
$types = "i";

try {
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $content_data = null;
    $manage_selected_data = [];
    $cast_crew_data = [];

    while ($row = $result->fetch_assoc()) {
        if ($content_data === null) {
            $content_data = [
                'content_id' => $row['content_id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'category_id' => $row['category_id'],
                'thumbnail_url' => $row['thumbnail_url'],
                'duration' => $row['duration'],
                'release_date' => $row['release_date'],
                'created_at' => $row['created_at'],
                'status' => $row['status'],
                'content_type' => $row['content_type'],
                'language_id' => $row['language_id'],
                'preference_id' => $row['preference_id'],
                'trailer_url' => $row['trailer_url'],
                'banner' => $row['banner'],
                'top_shows' => $row['top_shows'],
                'binge_worthy' => $row['binge_worthy'],
                'bollywood_binge' => $row['bollywood_binge'],
                'dubbed_in_hindi' => $row['dubbed_in_hindi'],
                'plan' => $row['plan'],
                'industry' => $row['industry'],
                'main_category_name' => $row['main_category_name'],
                'category_name' => $row['category_name'],
                'language_name' => $row['language_name'],
                'preference_name' => $row['preference_name'],
                'manage_selected' => [],
                'cast_crew' => []
            ];
        }
        // Aggregate manage_selected data (only active records are included due to query filter)
        if ($row['manage_selected_id']) {
            $manage_selected_data[$row['manage_selected_id']] = [
                'id' => $row['manage_selected_id'],
                'season_number' => $row['season_number'],
                'episode_number' => $row['episode_number'],
                'title' => $row['episode_title'],
                'description' => $row['episode_description'],
                'thumbnail_url' => $row['episode_thumbnail_url'],
                'video_url' => $row['episode_video_url'],
                'length' => $row['length'],
                'release_date' => $row['episode_release_date'],
                'status' => $row['episode_status'],
                'created_at' => $row['episode_created_at']
            ];
        }
        // Aggregate cast_crew data
        if ($row['cast_crew_name'] && $row['role']) {
            $cast_crew_data[] = [
                'name' => $row['cast_crew_name'],
                'role' => $row['role']
            ];
        }
    }

    if ($content_data) {
        $content_data['manage_selected'] = array_values($manage_selected_data);
        $content_data['cast_crew'] = $cast_crew_data;
        echo json_encode(['status' => 'success', 'data' => $content_data]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Content not found.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>