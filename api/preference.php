<?php
include 'config.php';

$action = $_POST['action'];

if ($action == 'add_preference') {
    $preference_name = $conn->real_escape_string($_POST['preference_name']);
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $status = 1; // Default for new preference
    $query = "INSERT INTO content_preferences (preference_name, description, status) VALUES ('$preference_name', '$description', $status)";
    if ($conn->query($query)) {
        header('Location: ../admin/manage_content_preferences.php?success=Preference added');
    } else {
        header('Location: ../admin/manage_content_preferences.php?error=Failed to add preference');
    }
}

if ($action == 'update_preference') {
    $preference_id = (int)$_POST['preference_id'];
    $preference_name = $conn->real_escape_string($_POST['preference_name']);
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $status = (int)$_POST['status'];
    $query = "UPDATE content_preferences SET preference_name = '$preference_name', description = '$description', status = $status WHERE preference_id = $preference_id";
    if ($conn->query($query)) {
        header('Location: ../admin/manage_content_preferences.php?success=Preference updated');
    } else {
        header('Location: ../admin/manage_content_preferences.php?error=Failed to update preference');
    }
}

if ($action == 'delete_preference') {
    $preference_id = (int)$_POST['preference_id'];
    $query = "DELETE FROM content_preferences WHERE preference_id = $preference_id";
    if ($conn->query($query)) {
        header('Location: ../admin/manage_content_preferences.php?success=Preference deleted');
    } else {
        header('Location: ../admin/manage_content_preferences.php?error=Failed to delete preference');
    }
}

if ($action == 'toggle_status') {
    $preference_id = (int)$_POST['preference_id'];
    $current_status = (int)$_POST['current_status'];
    $new_status = $current_status == 1 ? 0 : 1;
    $query = "UPDATE content_preferences SET status = $new_status WHERE preference_id = $preference_id";
    if ($conn->query($query)) {
        header('Location: ../admin/manage_content_preferences.php?success=Status updated');
    } else {
        header('Location: ../admin/manage_content_preferences.php?error=Failed to update status');
    }
}
?>