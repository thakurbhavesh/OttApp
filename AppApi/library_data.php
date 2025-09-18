<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include '../api/config.php';

function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// --- API key check ---
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['api_key'] ?? null);
$valid_api_key = 'your_secure_api_key';
if (!$api_key || $api_key !== $valid_api_key) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing API key']);
    exit;
}

// --- Parameters ---
$user_id = isset($_GET['user_id']) ? (int)sanitize($_GET['user_id']) : 0;
$status  = isset($_GET['status'])  ? sanitize($_GET['status'])      : 'active';
$type    = isset($_GET['type'])    ? strtolower(sanitize($_GET['type'])) : 'all'; 
// type can be: all | like | watchlist

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid user_id']);
    exit;
}
if (!in_array($status, ['active','inactive'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
    exit;
}

// --- Build query ---
$sql = "
    SELECT 
        c.content_id,
        c.title,
        c.description,
        c.thumbnail_url,
        c.video_url,
        c.duration,
        c.release_date,
        c.created_at,
        c.status,
        up.type AS preference_type,
        up.created_at AS added_at
    FROM user_preferences up
    JOIN content c ON c.content_id = up.content_id
    WHERE up.user_id = ?
      AND c.status = ?
";

// filter by type only if not 'all'
$params = [$user_id, $status];
$types  = 'is';

if ($type !== 'all') {
    $sql .= " AND up.type = ?";
    $params[] = $type;
    $types   .= 's';
}

$sql .= " ORDER BY up.created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = [
            'content_id'    => $row['content_id'],
            'title'         => $row['title'],
            'description'   => $row['description'],
            'thumbnail_url' => $row['thumbnail_url'],
            'video_url'     => $row['video_url'],
            'duration'      => $row['duration'],
            'release_date'  => $row['release_date'],
            'created_at'    => $row['created_at'],
            'preference_type' => $row['preference_type'],
            'added_at'      => $row['added_at']
        ];
    }

    echo json_encode(['status'=>'success','data'=>$data], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Database error: '.$e->getMessage()]);
}
?>
