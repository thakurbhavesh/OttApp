<?php
ob_start();
header('Content-Type: application/json');
include 'config.php';

$action = $_POST['action'] ?? '';

if ($action == 'add_single_content') {
    $content_id = (int)$_POST['content_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $thumbnail_url = mysqli_real_escape_string($conn, $_POST['thumbnail_url']);
    $video_url = mysqli_real_escape_string($conn, $_POST['video_url']);
    $length = (int)$_POST['length'];
    $release_date = $_POST['release_date'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Check if content already has entries (determine if it's single or multi)
    $check_query = "SELECT COUNT(*) as count, season_number FROM manage_selected WHERE content_id = ? GROUP BY season_number";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $content_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $has_entries = $result->num_rows > 0;

    if ($result->num_rows == 0) {
        $query = "INSERT INTO manage_selected (content_id, season_number, episode_number, title, description, thumbnail_url, video_url, length, release_date, status) 
                  VALUES (?, 0, 0, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issssiss", $content_id, $title, $description, $thumbnail_url, $video_url, $length, $release_date, $status);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Single content added successfully']);
            header('Location: ../admin/manage_selected_content.php');
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add single content: ' . $conn->error]);
        }
    } else {
        $row = $result->fetch_assoc();
        if ($row['season_number'] == 0) {
            echo json_encode(['status' => 'error', 'message' => 'Content already exists as single content, cannot add more']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Content already exists as multi content, cannot add single']);
        }
    }
    $stmt->close();
}

if ($action == 'save_multi_content') {
    $content_id = (int)$_POST['content_id'];
    $season_number = (int)$_POST['season_number'];
    $episodes = $_POST['episodes'] ?? [];

    // Check if content already has entries
    $check_query = "SELECT COUNT(*) as count, season_number FROM manage_selected WHERE content_id = ? GROUP BY season_number";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $content_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $has_entries = $result->num_rows > 0;

    if ($result->num_rows == 0 || ($result->num_rows > 0 && $result->fetch_assoc()['season_number'] > 0)) {
        if (!empty($episodes)) {
            foreach ($episodes as $episode) {
                $episode_number = (int)$episode['episode_number'];
                $episode_title = mysqli_real_escape_string($conn, $episode['episode_title']);
                $description = mysqli_real_escape_string($conn, $episode['description']);
                $thumbnail_url = mysqli_real_escape_string($conn, $episode['thumbnail_url']);
                $video_url = mysqli_real_escape_string($conn, $episode['video_url']);
                $length = (int)$episode['length'];
                $release_date = $episode['release_date'];
                $status = mysqli_real_escape_string($conn, $episode['status']);
                if (isset($episode['id']) && $episode['id'] > 0) {
                    $id = (int)$episode['id'];
                    $query = "UPDATE manage_selected SET 
                                season_number = ?, episode_number = ?, title = ?, description = ?, thumbnail_url = ?, video_url = ?, length = ?, release_date = ?, status = ? 
                              WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("iissssissi", $season_number, $episode_number, $episode_title, $description, $thumbnail_url, $video_url, $length, $release_date, $status, $id);
                } else {
                    $query = "INSERT INTO manage_selected (content_id, season_number, episode_number, title, description, thumbnail_url, video_url, length, release_date, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("iiissssiss", $content_id, $season_number, $episode_number, $episode_title, $description, $thumbnail_url, $video_url, $length, $release_date, $status);
                }
                if (!$stmt->execute()) {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to save episode: ' . $conn->error]);
                    exit;
                }
            }
            echo json_encode(['status' => 'success', 'message' => 'Episodes saved successfully']);
            header('Location: ../admin/manage_selected_content.php');
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No episodes provided']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Content already exists as single content, cannot add multi']);
    }
    $stmt->close();
}

if ($action == 'edit_content') {
    $id = (int)$_POST['id'] ?? (int)$_POST['episodes'][0]['id'];
    $content_id = (int)$_POST['content_id'];
    $season_number = isset($_POST['season_number']) ? (int)$_POST['season_number'] : 0;
    $episode_number = isset($_POST['episodes']) ? (int)$_POST['episodes'][0]['episode_number'] : 0;
    $title = isset($_POST['title']) ? mysqli_real_escape_string($conn, $_POST['title']) : mysqli_real_escape_string($conn, $_POST['episodes'][0]['episode_title']);
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : mysqli_real_escape_string($conn, $_POST['episodes'][0]['description']);
    $thumbnail_url = isset($_POST['thumbnail_url']) ? mysqli_real_escape_string($conn, $_POST['thumbnail_url']) : mysqli_real_escape_string($conn, $_POST['episodes'][0]['thumbnail_url']);
    $video_url = isset($_POST['video_url']) ? mysqli_real_escape_string($conn, $_POST['video_url']) : mysqli_real_escape_string($conn, $_POST['episodes'][0]['video_url']);
    $length = isset($_POST['length']) ? (int)$_POST['length'] : (int)$_POST['episodes'][0]['length'];
    $release_date = isset($_POST['release_date']) ? $_POST['release_date'] : $_POST['episodes'][0]['release_date'];
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : mysqli_real_escape_string($conn, $_POST['episodes'][0]['status']);

    // Validate and check existing content type
    $check_query = "SELECT season_number FROM manage_selected WHERE content_id = ? LIMIT 1";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $content_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_season = $result->num_rows > 0 ? $result->fetch_assoc()['season_number'] : null;

    if ($result->num_rows == 0 || ($existing_season == 0 && $season_number == 0) || ($existing_season > 0 && $season_number > 0)) {
        $query = "UPDATE manage_selected SET 
                    content_id = ?, 
                    season_number = ?, 
                    episode_number = ?, 
                    title = ?, 
                    description = ?, 
                    thumbnail_url = ?, 
                    video_url = ?, 
                    length = ?, 
                    release_date = ?, 
                    status = ? 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiissssissi", $content_id, $season_number, $episode_number, $title, $description, $thumbnail_url, $video_url, $length, $release_date, $status, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Content updated successfully']);
            header('Location: ../admin/manage_selected_content.php');
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update content: ' . $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Cannot change content type (single to multi or vice versa)']);
    }
    $stmt->close();
}

if ($action == 'get_content') {
    $content_id = (int)$_POST['content_id'];
    $season_number = isset($_POST['season_number']) ? (int)$_POST['season_number'] : null;
    $query = "SELECT ms.*, c.title as content_title, mc.name as main_category_name, cat.name as category_name 
              FROM manage_selected ms 
              JOIN content c ON ms.content_id = c.content_id 
              JOIN categories cat ON c.category_id = cat.category_id 
              JOIN main_categories mc ON cat.main_category_id = mc.category_id 
              WHERE ms.content_id = ?";
    if ($season_number !== null) {
        $query .= " AND ms.season_number = ?";
    }
    $stmt = $conn->prepare($query);
    if ($season_number !== null) {
        $stmt->bind_param("ii", $content_id, $season_number);
    } else {
        $stmt->bind_param("i", $content_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row; // Include description for edit form
    }
    echo json_encode(['status' => 'success', 'data' => $items]);
    $stmt->close();
}

if ($action == 'delete_content') {
    $id = (int)$_POST['id'];
    $query = "DELETE FROM manage_selected WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Content deleted successfully']);
        header('Location: ../admin/manage_selected_content.php');
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete content']);
    }
    $stmt->close();
}

if ($action == 'save_cast_crew') {
    $content_id = (int)$_POST['content_id'];
    $cast_crew = $_POST['cast_crew'] ?? [];

    $check_query = "SELECT content_id FROM content WHERE content_id = ? AND status = 'active'";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $content_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0 && !empty($cast_crew)) {
        foreach ($cast_crew as $entry) {
            $cast_crew_id = isset($entry['cast_crew_id']) && $entry['cast_crew_id'] > 0 ? (int)$entry['cast_crew_id'] : null;
            $name = mysqli_real_escape_string($conn, $entry['name']);
            $role = mysqli_real_escape_string($conn, $entry['role']);
            $image = mysqli_real_escape_string($conn, $entry['image'] ?? '');

            if ($cast_crew_id) {
                $query = "UPDATE cast_crew SET name = ?, role = ?, image = ? WHERE cast_crew_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssi", $name, $role, $image, $cast_crew_id);
            } else {
                $query = "INSERT INTO cast_crew (content_id, name, role, image) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("isss", $content_id, $name, $role, $image);
            }
            if (!$stmt->execute()) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save cast/crew: ' . $conn->error]);
                exit;
            }
        }
        echo json_encode(['status' => 'success', 'message' => 'Cast/Crew saved successfully']);
        header('Location: ../admin/manage_cast_crew.php?content_id=' . $content_id);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid content ID or no cast/crew provided']);
    }
    $stmt->close();
}

if ($action == 'get_cast_crew') {
    $content_id = (int)$_POST['content_id'];
    $query = "SELECT * FROM cast_crew WHERE content_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $content_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $items]);
    $stmt->close();
}

if ($action == 'delete_cast_crew') {
    $cast_crew_id = (int)$_POST['cast_crew_id'];
    $query = "DELETE FROM cast_crew WHERE cast_crew_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $cast_crew_id);
    if ($stmt->execute()) {
        $result = $conn->query("SELECT content_id FROM cast_crew WHERE cast_crew_id = $cast_crew_id LIMIT 1");
        $content_id = $result ? $result->fetch_assoc()['content_id'] : null;
        echo json_encode(['status' => 'success', 'message' => 'Cast/Crew deleted successfully']);
        header('Location: ../admin/manage_cast_crew.php?content_id=' . ($content_id ?: 0));
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete cast/crew']);
    }
    $stmt->close();
}

ob_end_flush();
?>