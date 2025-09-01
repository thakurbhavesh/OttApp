<?php
ob_start();
header('Content-Type: application/json');
include 'config.php';

$action = $_POST['action'] ?? '';

if ($action == 'add_selected_content') {
    $content_id = (int)$_POST['content_id'];
    $selection_type = $_POST['selection_type'];
    
    // Validate content_id
    $stmt = $conn->prepare("SELECT content_id FROM content WHERE content_id = ? AND status = 'active'");
    $stmt->bind_param("i", $content_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or inactive content']);
        exit;
    }
    $stmt->close();

    if ($selection_type == 'single') {
        $title = mysqli_real_escape_string($conn, $_POST['single_title'] ?? '');
        $description = mysqli_real_escape_string($conn, $_POST['single_description'] ?? '');
        $thumbnail_url = mysqli_real_escape_string($conn, $_POST['single_thumbnail_url'] ?? '');
        $video_url = mysqli_real_escape_string($conn, $_POST['single_video_url'] ?? '');
        $release_date = !empty($_POST['single_release_date']) ? $_POST['single_release_date'] : null;

        $query = "INSERT INTO selected_content (content_id, selection_type, title, description, thumbnail_url, video_url, release_date) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issssss", $content_id, $selection_type, $title, $description, $thumbnail_url, $video_url, $release_date);
        if ($stmt->execute()) {
            header('Location: ../admin/manage_selected_content.php');
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add selected content: ' . $conn->error]);
        }
        $stmt->close();
    } else if ($selection_type == 'multi') {
        $query = "INSERT INTO selected_content (content_id, selection_type) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $content_id, $selection_type);
        if ($stmt->execute()) {
            $selected_content_id = $conn->insert_id;
            foreach ($_POST['episodes'] ?? [] as $episode) {
                $episode_number = (int)$episode['episode_number'];
                $ep_title = mysqli_real_escape_string($conn, $episode['title'] ?? '');
                $ep_description = mysqli_real_escape_string($conn, $episode['description'] ?? '');
                $ep_thumbnail_url = mysqli_real_escape_string($conn, $episode['thumbnail_url'] ?? '');
                $ep_video_url = mysqli_real_escape_string($conn, $episode['video_url'] ?? '');
                $ep_release_date = !empty($episode['release_date']) ? $episode['release_date'] : null;

                $episode_query = "INSERT INTO episodes (selected_content_id, episode_number, title, description, thumbnail_url, video_url, release_date) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)";
                $episode_stmt = $conn->prepare($episode_query);
                $episode_stmt->bind_param("iisssss", $selected_content_id, $episode_number, $ep_title, $ep_description, $ep_thumbnail_url, $ep_video_url, $ep_release_date);
                $episode_stmt->execute();
                $episode_stmt->close();
            }
            header('Location: ../admin/manage_selected_content.php');
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add selected content: ' . $conn->error]);
        }
        $stmt->close();
    }
}

if ($action == 'update_selected_content') {
    $selected_content_id = (int)$_POST['selected_content_id'];
    $content_id = (int)$_POST['content_id'];
    $selection_type = $_POST['selection_type'];

    // Validate content_id
    $stmt = $conn->prepare("SELECT content_id FROM content WHERE content_id = ? AND status = 'active'");
    $stmt->bind_param("i", $content_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or inactive content']);
        exit;
    }
    $stmt->close();

    if ($selection_type == 'single') {
        $title = mysqli_real_escape_string($conn, $_POST['single_title'] ?? '');
        $description = mysqli_real_escape_string($conn, $_POST['single_description'] ?? '');
        $thumbnail_url = mysqli_real_escape_string($conn, $_POST['single_thumbnail_url'] ?? '');
        $video_url = mysqli_real_escape_string($conn, $_POST['single_video_url'] ?? '');
        $release_date = !empty($_POST['single_release_date']) ? $_POST['single_release_date'] : null;

        // Clear any existing episodes
        $conn->query("DELETE FROM episodes WHERE selected_content_id = $selected_content_id");

        $query = "UPDATE selected_content SET 
                    content_id = ?, 
                    selection_type = ?, 
                    title = ?, 
                    description = ?, 
                    thumbnail_url = ?, 
                    video_url = ?, 
                    release_date = ? 
                  WHERE selected_content_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issssssi", $content_id, $selection_type, $title, $description, $thumbnail_url, $video_url, $release_date, $selected_content_id);
        if ($stmt->execute()) {
            header('Location: ../admin/manage_selected_content.php');
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update selected content: ' . $conn->error]);
        }
        $stmt->close();
    } else if ($selection_type == 'multi') {
        // Clear single selection fields
        $query = "UPDATE selected_content SET 
                    content_id = ?, 
                    selection_type = ?, 
                    title = NULL, 
                    description = NULL, 
                    thumbnail_url = NULL, 
                    video_url = NULL, 
                    release_date = NULL 
                  WHERE selected_content_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isi", $content_id, $selection_type, $selected_content_id);
        $stmt->execute();
        $stmt->close();

        // Get existing episode IDs
        $existing_episodes = $conn->query("SELECT episode_id FROM episodes WHERE selected_content_id = $selected_content_id")->fetch_all(MYSQLI_ASSOC);
        $existing_episode_ids = array_column($existing_episodes, 'episode_id');
        $submitted_episode_ids = [];

        foreach ($_POST['episodes'] ?? [] as $index => $episode) {
            $episode_number = (int)$episode['episode_number'];
            $ep_title = mysqli_real_escape_string($conn, $episode['title'] ?? '');
            $ep_description = mysqli_real_escape_string($conn, $episode['description'] ?? '');
            $ep_thumbnail_url = mysqli_real_escape_string($conn, $episode['thumbnail_url'] ?? '');
            $ep_video_url = mysqli_real_escape_string($conn, $episode['video_url'] ?? '');
            $ep_release_date = !empty($episode['release_date']) ? $episode['release_date'] : null;
            $episode_id = isset($episode['episode_id']) ? (int)$episode['episode_id'] : null;

            if ($episode_id) {
                // Update existing episode
                $episode_query = "UPDATE episodes SET 
                                episode_number = ?, 
                                title = ?, 
                                description = ?, 
                                thumbnail_url = ?, 
                                video_url = ?, 
                                release_date = ? 
                              WHERE episode_id = ?";
                $episode_stmt = $conn->prepare($episode_query);
                $episode_stmt->bind_param("isssssi", $episode_number, $ep_title, $ep_description, $ep_thumbnail_url, $ep_video_url, $ep_release_date, $episode_id);
                $episode_stmt->execute();
                $episode_stmt->close();
                $submitted_episode_ids[] = $episode_id;
            } else {
                // Insert new episode
                $episode_query = "INSERT INTO episodes (selected_content_id, episode_number, title, description, thumbnail_url, video_url, release_date) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)";
                $episode_stmt = $conn->prepare($episode_query);
                $episode_stmt->bind_param("iisssss", $selected_content_id, $episode_number, $ep_title, $ep_description, $ep_thumbnail_url, $ep_video_url, $ep_release_date);
                $episode_stmt->execute();
                $episode_stmt->close();
                $submitted_episode_ids[] = $conn->insert_id;
            }
        }

        // Delete removed episodes
        foreach ($existing_episode_ids as $existing_episode_id) {
            if (!in_array($existing_episode_id, $submitted_episode_ids)) {
                $conn->query("DELETE FROM episodes WHERE episode_id = $existing_episode_id");
            }
        }

        header('Location: ../admin/manage_selected_content.php');
        exit;
    }
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