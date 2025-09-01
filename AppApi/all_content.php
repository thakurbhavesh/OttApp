<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow CORS for testing (adjust in production)

include '../api/config.php';

// Function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
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
    echo json_encode(['status' => 'error', 'message' => 'Invalid status. Use "active" or "inactive".']);
    exit;
}

// Validate IDs
$valid_languages = [1, 2, 3, 4, 5, 6, 7]; // Hindi, English, Punjabi, Kannada, Malayalam, Telugu, Bhojpuri
$valid_preferences = [1, 2, 3, 4, 5]; // Adult, Kids, Family, Teen, General
$valid_categories = [1, 2, 3, 4, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18]; // From categories table
$valid_main_categories = [1, 2, 3, 4, 5]; // Webseries, Tv Shows, Movies, Audio, Sports

$invalid_params = [];
if ($language_id && !in_array($language_id, $valid_languages)) $invalid_params[] = "language_id";
if ($preference_id && !in_array($preference_id, $valid_preferences)) $invalid_params[] = "preference_id";
if ($category_id && !in_array($category_id, $valid_categories)) $invalid_params[] = "category_id";
if ($main_category_id && !in_array($main_category_id, $valid_main_categories)) $invalid_params[] = "main_category_id";

if (!empty($invalid_params)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ' . implode(', ', $invalid_params) . '. Check valid IDs.']);
    exit;
}

// Build query with filters
$query = "SELECT 
    c.content_id, c.title, c.description, c.category_id, c.thumbnail_url, c.video_url, c.duration, c.release_date, c.created_at, c.status, c.content_type, c.language_id, c.preference_id, c.trailer_url, c.banner, c.top_shows, c.binge_worthy, c.bollywood_binge, c.dubbed_in_hindi, c.plan, c.industry,
    l.name AS language_name,
    cp.preference_name,
    cat.name AS category_name,
    mc.name AS main_category_name,
    cc.cast_crew_id, cc.name AS cast_name, cc.role AS cast_role, cc.image AS cast_image,
    ms.season_number, ms.episode_number, ms.title AS episode_title, ms.description AS episode_description, ms.thumbnail_url AS episode_thumbnail_url, ms.video_url AS episode_video_url, ms.length AS episode_length, ms.release_date AS episode_release_date, ms.status AS episode_status
    FROM content c
    LEFT JOIN languages l ON c.language_id = l.language_id
    LEFT JOIN content_preferences cp ON c.preference_id = cp.preference_id
    LEFT JOIN categories cat ON c.category_id = cat.category_id
    LEFT JOIN main_categories mc ON cat.category_id = mc.category_id
    LEFT JOIN cast_crew cc ON c.content_id = cc.content_id
    LEFT JOIN manage_selected ms ON c.content_id = ms.content_id
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
        $id = $row['content_id'];

        if (!isset($contents[$id])) {
            $contents[$id] = [
                'content_id' => $row['content_id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'category_id' => $row['category_id'],
                'thumbnail_url' => $row['thumbnail_url'],
                'video_url' => $row['video_url'],
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
                'language_name' => $row['language_name'],
                'preference_name' => $row['preference_name'],
                'category_name' => $row['category_name'],
                'main_category_name' => $row['main_category_name'],
                'cast_crew' => [],
                'episodes' => []
            ];
        }

        // Add cast if exists
        if (!empty($row['cast_id'])) {
            $contents[$id]['cast_crew'][] = [
                'cast_id' => $row['cast_id'],
                'name' => $row['cast_name'],
                'role' => $row['cast_role'],
                'image' => $row['cast_image']
            ];
        }

        // Add episode if exists
        if (!empty($row['episode_title'])) {
            $contents[$id]['episodes'][] = [
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

    $contents = array_values($contents); // reset keys

    if (empty($contents)) {
        echo json_encode(['status' => 'success', 'message' => 'No content found with the selected filters', 'data' => []], JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['status' => 'success', 'data' => $contents], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
