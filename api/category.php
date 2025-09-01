<?php
header('Content-Type: application/json');
include 'config.php';

$action = $_POST['action'] ?? '';

if ($action == 'add_category') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $main_category_id = (int)$_POST['main_category_id'];
    $query = "INSERT INTO categories (name, main_category_id) VALUES ('$name', $main_category_id)";
    if ($conn->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Category added']);
        header('Location: ../admin/main_categories.php');

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add category']);
    }
}

if ($action == 'update_category') {
    $category_id = (int)$_POST['category_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $main_category_id = (int)$_POST['main_category_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $query = "UPDATE categories SET name = '$name', main_category_id = $main_category_id, status = '$status' WHERE category_id = $category_id";
    if ($conn->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Category updated']);
        header('Location: ../admin/main_categories.php');

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update category']);
    }
}


?>