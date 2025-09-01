<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php');
    exit;
}
include '../api/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/header.php'; ?>
    <title>Manage Categories</title>
    <style>
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container-fluid">
        <h1 class="mt-4">Manage Categories</h1>
        
        <?php
        $show_form = isset($_GET['form']) && in_array($_GET['form'], ['add', 'edit']);
        $edit_mode = $show_form && $_GET['form'] == 'edit' && isset($_GET['id']);
        
        if ($edit_mode) {
            $category_id = (int)$_GET['id'];
            $result = $conn->query("SELECT * FROM categories WHERE category_id = $category_id");
            $category = $result->fetch_assoc();
            if (!$category) {
                echo "<div class='alert alert-danger'>Category not found.</div>";
                $show_form = false;
                $edit_mode = false;
            }
        }
        ?>

        <!-- Add/Edit Form -->
        <?php if ($show_form) { ?>
            <form method="post" action="../api/categories.php" class="p-4 shadow-sm rounded bg-white">
                <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_category' : 'add_category'; ?>">
                <?php if ($edit_mode) { ?>
                    <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                <?php } ?>
                <div class="mb-3">
                    <label class="form-label">Main Category</label>
                    <select class="form-control" name="main_category_id" id="main_category_id" required <?php echo $edit_mode ? 'disabled' : ''; ?>>
                        <?php
                        $result = $conn->query("SELECT * FROM main_categories WHERE status = 'active'");
                        while ($row = $result->fetch_assoc()) {
                            $selected = $edit_mode && $row['category_id'] == $category['main_category_id'] ? 'selected' : '';
                            echo "<option value='{$row['category_id']}' $selected>{$row['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="<?php echo $edit_mode ? htmlspecialchars($category['name']) : ''; ?>" required>
                </div>
                <?php if ($edit_mode) { ?>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status" required>
                            <option value="active" <?php echo $category['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $category['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                <?php } ?>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-<?php echo $edit_mode ? 'edit' : 'plus'; ?> me-2"></i>
                    <?php echo $edit_mode ? 'Update Category' : 'Add Category'; ?>
                </button>
                <a href="manage_categories.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php } else { ?>
            <a href="manage_categories.php?form=add" class="btn btn-primary mb-3">
                <i class="fas fa-plus me-2"></i>Add Category
            </a>
        <?php } ?>

        <!-- Categories Table -->
        <h2 class="mt-5">All Categories</h2>
        <table id="categoriesTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>Main Category</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT c.*, mc.name as main_category_name FROM categories c JOIN main_categories mc ON c.main_category_id = mc.category_id");
                while ($row = $result->fetch_assoc()) {
                    $status_class = $row['status'] == 'active' ? 'status-active' : 'status-inactive';
                    echo "<tr>
                        <td>" . htmlspecialchars($row['main_category_name']) . "</td>
                        <td>" . htmlspecialchars($row['name']) . "</td>
                        <td class='$status_class'>" . htmlspecialchars($row['status']) . "</td>
                        <td>
                            <a href='manage_categories.php?form=edit&id={$row['category_id']}' class='btn btn-sm btn-warning'><i class='fas fa-edit'></i></a>
                            <form method='post' action='../api/categories.php' style='display:inline;'>
                                <input type='hidden' name='action' value='delete_category'>
                                <input type='hidden' name='category_id' value='{$row['category_id']}'>
                                <button type='submit' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>
                            </form>
                            <form method='post' action='../api/categories.php' style='display:inline;'>
                                <input type='hidden' name='action' value='toggle_status'>
                                <input type='hidden' name='category_id' value='{$row['category_id']}'>
                                <input type='hidden' name='current_status' value='{$row['status']}'>
                                <button type='submit' class='btn btn-sm btn-" . ($row['status'] == 'active' ? 'secondary' : 'success') . "'>
                                    <i class='fas fa-" . ($row['status'] == 'active' ? 'pause' : 'play') . "'></i>
                                </button>
                            </form>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>