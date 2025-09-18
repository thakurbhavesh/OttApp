<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
function is_rate_limited($ip, $limit = 1000, $window = 3600) {
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
$banner = isset($_GET['banner']) ? (int)sanitize_input($_GET['banner']) : null;
$top_shows = isset($_GET['top_shows']) ? (int)sanitize_input($_GET['top_shows']) : null;
$binge_worthy = isset($_GET['binge_worthy']) ? (int)sanitize_input($_GET['binge_worthy']) : null;
$bollywood_binge = isset($_GET['bollywood_binge']) ? (int)sanitize_input($_GET['bollywood_binge']) : null;
$dubbed_in_hindi = isset($_GET['dubbed_in_hindi']) ? (int)sanitize_input($_GET['dubbed_in_hindi']) : null;
$all_in_one_podcast = isset($_GET['all_in_one_podcast']) ? (int)sanitize_input($_GET['all_in_one_podcast']) : null;
$top_web_series = isset($_GET['top_web_series']) ? (int)sanitize_input($_GET['top_web_series']) : null;
$top_short_films = isset($_GET['top_short_films']) ? (int)sanitize_input($_GET['top_short_films']) : null;
$catchme_tv_originals = isset($_GET['catchme_tv_originals']) ? (int)sanitize_input($_GET['catchme_tv_originals']) : null;
$daily_shops = isset($_GET['daily_shops']) ? (int)sanitize_input($_GET['daily_shops']) : null;
$villain_baba_show = isset($_GET['villain_baba_show']) ? (int)sanitize_input($_GET['villain_baba_show']) : null;
$no1_vertical_shows = isset($_GET['no1_vertical_shows']) ? (int)sanitize_input($_GET['no1_vertical_shows']) : null;
$satrak_raho = isset($_GET['satrak_raho']) ? (int)sanitize_input($_GET['satrak_raho']) : null;
$bhojpuri_films = isset($_GET['bhojpuri']) ? (int)sanitize_input($_GET['bhojpuri']) : null; // Updated to match column name

// Validate status
$valid_statuses = ['active', 'inactive'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid status. Use "active" or "inactive".']);
    exit;
}

// Build query with filters and joins
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
WHERE c.status = ?";
$params = [$status];
$types = "s";

if ($banner !== null) {
    $query .= " AND c.banner = ?";
    $params[] = $banner;
    $types .= "i";
}

if ($top_shows !== null) {
    $query .= " AND c.top_shows = ?";
    $params[] = $top_shows;
    $types .= "i";
}

if ($binge_worthy !== null) {
    $query .= " AND c.binge_worthy = ?";
    $params[] = $binge_worthy;
    $types .= "i";
}

if ($bollywood_binge !== null) {
    $query .= " AND c.bollywood_binge = ?";
    $params[] = $bollywood_binge;
    $types .= "i";
}

if ($dubbed_in_hindi !== null) {
    $query .= " AND c.dubbed_in_hindi = ?";
    $params[] = $dubbed_in_hindi;
    $types .= "i";
}

if ($all_in_one_podcast !== null) {
    $query .= " AND c.all_in_one_podcast = ?";
    $params[] = $all_in_one_podcast;
    $types .= "i";
}

if ($top_web_series !== null) {
    $query .= " AND c.top_web_series = ?";
    $params[] = $top_web_series;
    $types .= "i";
}

if ($top_short_films !== null) {
    $query .= " AND c.top_short_films = ?";
    $params[] = $top_short_films;
    $types .= "i";
}

if ($catchme_tv_originals !== null) {
    $query .= " AND c.catchme_tv_originals = ?";
    $params[] = $catchme_tv_originals;
    $types .= "i";
}

if ($daily_shops !== null) {
    $query .= " AND c.daily_shops = ?";
    $params[] = $daily_shops;
    $types .= "i";
}

if ($villain_baba_show !== null) {
    $query .= " AND c.villain_baba_show = ?";
    $params[] = $villain_baba_show;
    $types .= "i";
}

if ($no1_vertical_shows !== null) {
    $query .= " AND c.no1_vertical_shows = ?";
    $params[] = $no1_vertical_shows;
    $types .= "i";
}

if ($satrak_raho !== null) {
    $query .= " AND c.satrak_raho = ?";
    $params[] = $satrak_raho;
    $types .= "i";
}

if ($bhojpuri_films !== null) {
    $query .= " AND c.bhojpuri_films = ?";
    $params[] = $bhojpuri_films;
    $types .= "i";
}

// Prepare and execute query
$stmt = null;
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
        $contents[] = [
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

    if (empty($contents)) {
        echo json_encode(['status' => 'success', 'message' => 'No content found', 'data' => []]);
    } else {
        echo json_encode(['status' => 'success', 'data' => $contents], JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    http_response_code(500);
    $error_message = 'Database error: ' . $e->getMessage();
    file_put_contents('api_errors.log', date('Y-m-d H:i:s') . ' - ' . $error_message . "\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => $error_message]);
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