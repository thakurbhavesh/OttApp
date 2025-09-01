<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action == 'add_language') {
        $name = $conn->real_escape_string($_POST['name']);
        $conn->query("INSERT INTO languages (name) VALUES ('$name')");
        header("Location: ../admin/manage_languages.php");
        exit;
    }

    if ($action == 'update_language') {
        $id = (int)$_POST['language_id'];
        $name = $conn->real_escape_string($_POST['name']);
        $status = $_POST['status'];
        $conn->query("UPDATE languages SET name='$name', status='$status' WHERE language_id=$id");
        header("Location: ../admin/manage_languages.php");
        exit;
    }

    if ($action == 'delete_language') {
        $id = (int)$_POST['language_id'];
        $conn->query("DELETE FROM languages WHERE language_id=$id");
        header("Location: ../admin/manage_languages.php");
        exit;
    }

    if ($action == 'toggle_status') {
        $id = (int)$_POST['language_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status == 'active' ? 'inactive' : 'active';
        $conn->query("UPDATE languages SET status='$new_status' WHERE language_id=$id");
        header("Location: ../admin/manage_languages.php");
        exit;
    }
}
?>
