<?php
// search_content.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../api/config.php';

// Sanitize input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// API key validation
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['api_key'] ?? null);
$valid_api_key = 'your_secure_api_key'; // Replace with actual key
if (!$api_key || $api_key !== $valid_api_key) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing API key']);
    exit;
}

$status = sanitize_input($_GET['status'] ?? 'active');
$q = sanitize_input($_GET['q'] ?? ''); // Search query

if (strlen($q) < 2) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Search query must be at least 2 characters']);
    exit;
}

// Build search query (search in title, main_category_name, category_name, language_name, industry)
$query = "
    SELECT 
        c.content_id,
        c.title,
        c.thumbnail_url,
        c.trailer_url,
        c.plan AS plan_type,
        c.industry,
        l.name AS language_name,
        cp.preference_name,
        cat.name AS category_name,
        mc.name AS main_category_name
    FROM content c
    LEFT JOIN languages l ON c.language_id = l.language_id
    LEFT JOIN content_preferences cp ON c.preference_id = cp.preference_id
    LEFT JOIN categories cat ON c.category_id = cat.category_id
    LEFT JOIN main_categories mc ON cat.main_category_id = mc.category_id
    WHERE c.status = ? 
    AND (
        c.title LIKE ? OR 
        mc.name LIKE ? OR 
        cat.name LIKE ? OR 
        l.name LIKE ? OR 
        c.industry LIKE ?
    )
    LIMIT 50
";

$searchTerm = '%' . $q . '%';
$params = [$status, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
$types = 'ssssss';

try {
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $contents = [];
    while ($row = $result->fetch_assoc()) {
        $contents[] = [
            'content_id' => $row['content_id'],
            'title' => $row['title'] ?? '',
            'thumbnail_url' => $row['thumbnail_url'] ?? null,
            'trailer_url' => $row['trailer_url'] ?? null,
            'plan_type' => $row['plan_type'] ?? 'free',
            'industry' => $row['industry'] ?? '',
            'language_name' => $row['language_name'] ?? '',
            'preference_name' => $row['preference_name'] ?? '',
            'category_name' => $row['category_name'] ?? '',
            'main_category_name' => $row['main_category_name'] ?? 'Other',
            'episodes' => [], // Episodes not fetched in search for performance; fetch in ViewDetails
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $contents,
        'message' => empty($contents) ? 'No results found' : null,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Search failed: ' . $e->getMessage(),
    ], JSON_PRETTY_PRINT);
} finally {
    $conn->close();
}
?>