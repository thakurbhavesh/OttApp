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

    // Check if content already has entries
    $check_query = "SELECT season_number FROM manage_selected WHERE content_id = ? LIMIT 1";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $content_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // No existing content, allow adding as single
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
    $check_query = "SELECT season_number FROM manage_selected WHERE content_id = ? LIMIT 1";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $content_id);
    $stmt->execute();
    $result = $stmt->get_result();

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

if ($action == 'delete_selected_content') {
    $selected_content_id = (int)$_POST['selected_content_id'];
    $conn->query("DELETE FROM episodes WHERE selected_content_id = $selected_content_id");
    $query = "DELETE FROM selected_content WHERE selected_content_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $selected_content_id);
    if ($stmt->execute()) {
        header('Location: ../admin/manage_selected_content.php');
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete selected content']);
    }
    $stmt->close();
}

ob_end_flush();
?>