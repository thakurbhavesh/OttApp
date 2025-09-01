<?php
ob_start();
session_start();
include 'config.php';

$action = $_POST['action'] ?? '';

if ($action == 'add_media') {
    $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
    $url = mysqli_real_escape_string($conn, $_POST['url'] ?? '');
    $status = 'active'; // Default status for new media
    $created_at = date('Y-m-d H:i:s'); // Current date/time (e.g., 2025-08-02 22:15:00 IST)

    if (empty($title)) {
        $_SESSION['error'] = 'Title is required.';
        header('Location: ../admin/upload_media.php');
        exit;
    }

    $file_path = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = basename($_FILES['file']['name']);
        $target_file = $upload_dir . uniqid() . '_' . $file_name;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $file_path = str_replace('../', '', $target_file); // Store relative path
        } else {
            $_SESSION['error'] = 'Failed to upload file.';
            header('Location: ../admin/upload_media.php');
            exit;
        }
    }

    $query = "INSERT INTO media (title, url, file_path, status, created_at) 
              VALUES ('$title', '$url', '$file_path', '$status', '$created_at')";
    if ($conn->query($query)) {
        header('Location: ../admin/upload_media.php');
        exit;
    } else {
        if ($file_path && file_exists($upload_dir . $file_path)) {
            unlink($upload_dir . $file_path); // Cleanup on failure
        }
        $_SESSION['error'] = 'Failed to add media.';
        header('Location: ../admin/upload_media.php');
        exit;
    }
}

if ($action == 'update_media') {
    $media_id = (int)$_POST['media_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $url = mysqli_real_escape_string($conn, $_POST['url']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $file_path = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = basename($_FILES['file']['name']);
        $target_file = $upload_dir . uniqid() . '_' . $file_name;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $file_path = str_replace('../', '', $target_file);
            // Delete old file if it exists
            $result = $conn->query("SELECT file_path FROM media WHERE media_id = $media_id");
            $old_media = $result->fetch_assoc();
            if ($old_media['file_path'] && file_exists($upload_dir . $old_media['file_path'])) {
                unlink($upload_dir . $old_media['file_path']);
            }
        } else {
            $_SESSION['error'] = 'Failed to upload new file.';
            header('Location: ../admin/upload_media.php');
            exit;
        }
    }

    $query = "UPDATE media SET title = '$title', url = '$url', status = '$status'" .
             ($file_path ? ", file_path = '$file_path'" : "") .
             " WHERE media_id = $media_id";
    if ($conn->query($query)) {
        header('Location: ../admin/upload_media.php');
        exit;
    } else {
        if ($file_path && file_exists($upload_dir . $file_path)) {
            unlink($upload_dir . $file_path); // Cleanup on failure
        }
        $_SESSION['error'] = 'Failed to update media.';
        header('Location: ../admin/upload_media.php');
        exit;
    }
}

if ($action == 'delete_media') {
    $media_id = (int)$_POST['media_id'];
    $result = $conn->query("SELECT file_path FROM media WHERE media_id = $media_id");
    $media = $result->fetch_assoc();
    $query = "DELETE FROM media WHERE media_id = $media_id";
    if ($conn->query($query)) {
        $upload_dir = '../uploads/';
        if ($media['file_path'] && file_exists($upload_dir . $media['file_path'])) {
            unlink($upload_dir . $media['file_path']);
        }
        header('Location: ../admin/upload_media.php');
        exit;
    } else {
        $_SESSION['error'] = 'Failed to delete media.';
        header('Location: ../admin/upload_media.php');
        exit;
    }
}

if ($action == 'toggle_status') {
    $media_id = (int)$_POST['media_id'];
    $current_status = $_POST['current_status'];
    $new_status = $current_status == 'active' ? 'inactive' : 'active';

    $query = "UPDATE media SET status = '$new_status' WHERE media_id = $media_id";
    if ($conn->query($query)) {
        header('Location: ../admin/upload_media.php');
        exit;
    } else {
        $_SESSION['error'] = 'Failed to toggle status.';
        header('Location: ../admin/upload_media.php');
        exit;
    }
}

ob_end_flush();
?>