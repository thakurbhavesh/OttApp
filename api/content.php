<?php
ob_start();
header('Content-Type: application/json');
include 'config.php';

$action = $_POST['action'] ?? '';

// Debug function to log data
function debug_log($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'debug.log');
}

// Function to parse duration (e.g., "2 hrs 30 min" to minutes)


if ($action == 'add_content') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category_id = (int)$_POST['category_id'];
    $language_id = (int)$_POST['language_id'];
    $preference_id = (int)$_POST['preference_id'];
    $thumbnail_url = mysqli_real_escape_string($conn, $_POST['thumbnail_url']);
    $trailer_url = mysqli_real_escape_string($conn, $_POST['trailer_url']);
    $duration_str = $_POST['duration'] ?? '';
    $duration = $duration_str;
    $release_date_input = $_POST['release_date'] ?? '';
    $release_date = !empty($release_date_input) ? date('Y-m-d', strtotime($release_date_input)) : date('Y-m-d');
    $status = 'active';
    $plan = mysqli_real_escape_string($conn, $_POST['plan']);
    $industry = mysqli_real_escape_string($conn, $_POST['industry']);

    // Debug log
    debug_log("Add Content - POST Data: " . print_r($_POST, true));
    debug_log("Processed duration: $duration minutes");
    debug_log("Processed release_date: $release_date");

    // Validate category_id, language_id, and preference_id
    $check_query = "SELECT category_id FROM categories WHERE category_id = ? AND status = 'active'";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $language_check = $conn->query("SELECT language_id FROM languages WHERE language_id = $language_id AND status = 'active'");
    $preference_check = $conn->query("SELECT preference_id FROM content_preferences WHERE preference_id = $preference_id AND status = 1");

    if ($result->num_rows > 0 && $language_check->num_rows > 0 && $preference_check->num_rows > 0) {
        $query = "INSERT INTO content (title, description, category_id, language_id, preference_id, thumbnail_url, trailer_url, duration, release_date, status, plan, industry) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            debug_log("Prepare failed: " . $conn->error);
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("ssiiisssssss", $title, $description, $category_id, $language_id, $preference_id, $thumbnail_url, $trailer_url, $duration, $release_date, $status, $plan, $industry);
        if ($stmt->execute()) {
            debug_log("Content added successfully with release_date: $release_date");
            header('Location: ../admin/manage_content.php');
            exit;
        } else {
            debug_log("Execute failed: " . $stmt->error);
            echo json_encode(['status' => 'error', 'message' => 'Failed to add content: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or inactive category, language, or preference']);
    }
    $stmt->close();
}

if ($action == 'update_content') {
    $content_id = (int)$_POST['content_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category_id = (int)$_POST['category_id'];
    $language_id = (int)$_POST['language_id'];
    $preference_id = (int)$_POST['preference_id'];
    $thumbnail_url = mysqli_real_escape_string($conn, $_POST['thumbnail_url']);
    $trailer_url = mysqli_real_escape_string($conn, $_POST['trailer_url']);
    $duration_str = $_POST['duration'] ?? '';
    $duration = $duration_str;
    $release_date_input = $_POST['release_date'] ?? '';
    $release_date = !empty($release_date_input) ? date('Y-m-d', strtotime($release_date_input)) : date('Y-m-d');
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $plan = mysqli_real_escape_string($conn, $_POST['plan']);
    $industry = mysqli_real_escape_string($conn, $_POST['industry']);

    // Debug log
    debug_log("Update Content - POST Data: " . print_r($_POST, true));
    debug_log("Processed duration: $duration minutes");
    debug_log("Processed release_date: $release_date");

    // Validate category_id, language_id, and preference_id
    $check_query = "SELECT category_id FROM categories WHERE category_id = ? AND status = 'active'";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $language_check = $conn->query("SELECT language_id FROM languages WHERE language_id = $language_id AND status = 'active'");
    $preference_check = $conn->query("SELECT preference_id FROM content_preferences WHERE preference_id = $preference_id AND status = 1");

    if ($result->num_rows > 0 && $language_check->num_rows > 0 && $preference_check->num_rows > 0) {
        $query = "UPDATE content SET 
                    title = ?, 
                    description = ?, 
                    category_id = ?, 
                    language_id = ?, 
                    preference_id = ?, 
                    thumbnail_url = ?, 
                    trailer_url = ?, 
                    duration = ?, 
                    release_date = ?, 
                    status = ?, 
                    plan = ?, 
                    industry = ? 
                  WHERE content_id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            debug_log("Prepare failed: " . $conn->error);
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("ssiiisssssssi", $title, $description, $category_id, $language_id, $preference_id, $thumbnail_url, $trailer_url, $duration, $release_date, $status, $plan, $industry, $content_id);
        if ($stmt->execute()) {
            debug_log("Content updated successfully with release_date: $release_date");
            header('Location: ../admin/manage_content.php');
            exit;
        } else {
            debug_log("Execute failed: " . $stmt->error);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update content: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or inactive category, language, or preference']);
    }
    $stmt->close();
}

if ($action == 'get_content') {
    $query = "SELECT c.*, cat.name as category_name, l.name as language_name, cp.preference_name 
              FROM content c 
              JOIN categories cat ON c.category_id = cat.category_id 
              LEFT JOIN languages l ON c.language_id = l.language_id 
              LEFT JOIN content_preferences cp ON c.preference_id = cp.preference_id";
    $result = $conn->query($query);
    $content = [];
    while ($row = $result->fetch_assoc()) {
        $content[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $content]);
}


if ($action == 'delete_content') {
    $content_id = (int)$_POST['content_id'];
    $query = "DELETE FROM content WHERE content_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $content_id);
    if ($stmt->execute()) {
        header('Location: ../admin/manage_content.php');
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete content']);
    }
    $stmt->close();
}

if ($action == 'toggle_status') {
    $content_id = (int)$_POST['content_id'];
    $current_status = $_POST['current_status'];
    $new_status = $current_status == 'active' ? 'inactive' : 'active';

    $query = "UPDATE content SET status = ? WHERE content_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $new_status, $content_id);
    if ($stmt->execute()) {
        header('Location: ../admin/manage_content.php');
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to toggle status']);
    }
    $stmt->close();
}

if ($action == 'toggle_banner') {
    $content_id = (int)$_POST['content_id'];
    $current_banner = (int)$_POST['current_banner'];
    $new_banner = $current_banner === 1 ? 0 : 1; // Toggle between 0 and 1

    $query = "UPDATE content SET banner = ? WHERE content_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $new_banner, $content_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Banner status updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update banner status: ' . $conn->error]);
    }
    $stmt->close();
}

// New toggle_top_shows action
if ($action == 'toggle_top_shows') {
    $content_id = (int)$_POST['content_id'];
    $new_top_shows = (int)$_POST['new_top_shows'];

    $query = "UPDATE content SET top_shows = ? WHERE content_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $new_top_shows, $content_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Top Shows status updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update Top Shows status: ' . $conn->error]);
    }
    $stmt->close();
}


// New toggle_binge_worthy action
if ($action == 'toggle_binge_worthy') {
    $content_id = (int)$_POST['content_id'];
    $new_binge_worthy = (int)$_POST['new_binge_worthy'];

    $query = "UPDATE content SET binge_worthy = ? WHERE content_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $new_binge_worthy, $content_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Binge Worthy status updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update Binge Worthy status: ' . $conn->error]);
    }
    $stmt->close();
}

// New toggle_bollywood_binge action
if ($action == 'toggle_bollywood_binge') {
    $content_id = (int)$_POST['content_id'];
    $new_bollywood_binge = (int)$_POST['new_bollywood_binge'];

    $query = "UPDATE content SET bollywood_binge = ? WHERE content_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $new_bollywood_binge, $content_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Bollywood Binge status updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update Bollywood Binge status: ' . $conn->error]);
    }
    $stmt->close();
}

// New toggle_dubbed_in_hindi action
if ($action == 'toggle_dubbed_in_hindi') {
    $content_id = (int)$_POST['content_id'];
    $current_dubbed_in_hindi = (int)($_POST['current_dubbed_in_hindi'] ?? 0);
    $new_dubbed_in_hindi = isset($_POST['new_dubbed_in_hindi']) ? (int)$_POST['new_dubbed_in_hindi'] : ($current_dubbed_in_hindi === 1 ? 0 : 1);

    $query = "UPDATE content SET dubbed_in_hindi = ? WHERE content_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $new_dubbed_in_hindi, $content_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Dubbed In Hindi status updated successfully']);
        header('location: ../admin/manage_dubbed.php');
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update Dubbed In Hindi status: ' . $conn->error]);
    }
    $stmt->close();
}

ob_end_flush();
?>