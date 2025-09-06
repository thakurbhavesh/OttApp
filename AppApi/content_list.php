<?php
// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow all origins for localhost testing
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
        throw new Exception("Failed to fetch IDs from $table");
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
$valid_api_key = 'BhaveshSingh'; // Store in config file or environment variable in production
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

// Fetch valid IDs dynamically from database
try {
    $valid_languages = fetch_valid_ids($conn, 'languages', 'language_id', 'status');
    $valid_preferences = fetch_valid_ids($conn, 'content_preferences', 'preference_id', 'status');
    $valid_categories = fetch_valid_ids($conn, 'categories', 'category_id', 'status');
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

if (!empty($invalid_params)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid ' . implode(', ', $invalid_params) . '. Check valid IDs.']);
    exit;
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) AS total FROM content WHERE status = ?";
$count_params = [$status];
$count_types = "s";

if ($language_id) {
    $count_query .= " AND language_id = ?";
    $count_params[] = $language_id;
    $count_types .= "i";
}
if ($preference_id) {
    $count_query .= " AND preference_id = ?";
    $count_params[] = $preference_id;
    $count_types .= "i";
}
if ($category_id) {
    $count_query .= " AND category_id = ?";
    $count_params[] = $category_id;
    $count_types .= "i";
}

try {
    $count_stmt = $conn->prepare($count_query);
    if ($count_stmt === false) {
        throw new Exception("Prepare failed for count query");
    }
    $count_stmt->bind_param($count_types, ...$count_params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
    exit;
}

// Build main query with filters, joins for category and main category, and pagination
$query = "SELECT 
    c.title, c.plan AS plan_type, c.industry, c.release_date, c.status,
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

$query .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

try {
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Prepare failed");
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $contents = [];
    while ($row = $result->fetch_assoc()) {
        $contents[] = [
            'title' => $row['title'],
            'main_category' => $row['main_category'],
            'category' => $row['category'],
            'language' => $row['language'],
            'preference' => $row['preference'],
            'plan_type' => $row['plan_type'],
            'industry' => $row['industry'],
            'release_date' => $row['release_date'],
            'status' => $row['status']
        ];
    }

    // Prepare pagination metadata
    $total_pages = ceil($total_records / $limit);
    $response = [
        'status' => 'success',
        'data' => $contents,
        'pagination' => [
            'total_records' => $total_records,
            'current_page' => $page,
            'total_pages' => $total_pages,
            'limit' => $limit
        ]
    ];

    if (empty($contents)) {
        $response['message'] = 'No content found with the selected filters';
    }

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}

$stmt->close();
$conn->close();
?>