<?php
header('Content-Type: application/json');
include 'config.php';

$action = $_POST['action'] ?? '';

if ($action == 'add_category') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $main_category_id = isset($_POST['main_category_id']) ? (int)$_POST['main_category_id'] : 0;

    // Validate main_category_id exists in main_categories
    $check_query = "SELECT category_id FROM main_categories WHERE category_id = $main_category_id";
    $result = $conn->query($check_query);
    if ($result && $result->num_rows > 0) {
        $query = "INSERT INTO categories (name, main_category_id) VALUES ('$name', $main_category_id)";
        if ($conn->query($query)) {
            echo json_encode(['status' => 'success', 'message' => 'Category added']);
            header('Location: ../admin/manage_categories.php');
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add category: ' . $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid main_category_id']);
    }
}

if ($action == 'update_category') {
    $category_id = (int)$_POST['category_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Fetch current main_category_id from the database
    $query = "SELECT main_category_id FROM categories WHERE category_id = $category_id";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $category = $result->fetch_assoc();
        $main_category_id = $category['main_category_id'];

        // Validate main_category_id (if not null)
        if ($main_category_id !== null) {
            $check_query = "SELECT category_id FROM main_categories WHERE category_id = $main_category_id";
            $check_result = $conn->query($check_query);
            if (!$check_result || $check_result->num_rows == 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid main_category_id']);
                exit;
            }
        }

        // Update category without changing main_category_id
        $query = "UPDATE categories SET name = '$name', status = '$status' WHERE category_id = $category_id";
        if ($conn->query($query)) {
            echo json_encode(['status' => 'success', 'message' => 'Category updated']);
            header('Location: ../admin/manage_categories.php');
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update category: ' . $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Category not found']);
    }
}

if ($action == 'delete_category') {
    $category_id = (int)$_POST['category_id'];
    $query = "DELETE FROM categories WHERE category_id = $category_id";
    if ($conn->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Category deleted']);
        header('Location: ../admin/manage_categories.php');
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete category: ' . $conn->error]);
    }
}

if ($action == 'toggle_status') {
    $category_id = (int)$_POST['category_id'];
    $current_status = mysqli_real_escape_string($conn, $_POST['current_status']);
    $new_status = $current_status == 'active' ? 'inactive' : 'active';
    $query = "UPDATE categories SET status = '$new_status' WHERE category_id = $category_id";
    if ($conn->query($query)) {
        echo json_encode(['status' => 'success', 'message' => 'Status toggled']);
        header('Location: ../admin/manage_categories.php');
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to toggle status: ' . $conn->error]);
    }
}

$conn->close();
?>