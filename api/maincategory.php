<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action == 'add_category') {
        $name = $conn->real_escape_string($_POST['name']);
        $conn->query("INSERT INTO main_categories (name) VALUES ('$name')");
        header("Location: ../admin/main_categories.php");
        exit;
    }

    if ($action == 'update_category') {
        $id = (int)$_POST['category_id'];
        $name = $conn->real_escape_string($_POST['name']);
        $status = $_POST['status'];
        $conn->query("UPDATE main_categories SET name='$name', status='$status' WHERE category_id=$id");
        header("Location: ../admin/main_categories.php");
        exit;
    }

    if ($action == 'delete_category') {
        $id = (int)$_POST['category_id'];
        $conn->query("DELETE FROM main_categories WHERE category_id=$id");
        header("Location: ../admin/main_categories.php");
        exit;
    }

    if ($action == 'toggle_status') {
        $id = (int)$_POST['category_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status == 'active' ? 'inactive' : 'active';
        $conn->query("UPDATE main_categories SET status='$new_status' WHERE category_id=$id");
        header("Location: ../admin/main_categories.php");
        exit;
    }
}
?>
