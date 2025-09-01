<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include '../api/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/header.php'; ?>
    <title>Manage Main Categories</title>
    <style>
        .status-active { color: green!important; font-weight: bold; }
        .status-inactive { color: red!important; font-weight: bold; }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container-fluid">
    <h1 class="mt-4">Manage Main Categories</h1>

    <!-- Add Category Button -->
    <a href="main_categories.php?form=add" class="btn btn-primary mb-3">
        <i class="fas fa-plus me-2"></i>Add Main Category
    </a>

    <?php
    $show_form = isset($_GET['form']) && in_array($_GET['form'], ['add','edit']);
    $edit_mode = $show_form && $_GET['form'] == 'edit' && isset($_GET['id']);

    if ($edit_mode) {
        $cat_id = (int)$_GET['id'];
        $res = $conn->query("SELECT * FROM main_categories WHERE category_id=$cat_id");
        $category = $res->fetch_assoc();
        if (!$category) { 
            echo "<div class='alert alert-danger'>Category not found.</div>";
            $show_form = false;
            $edit_mode = false;
        }
    }

    if ($show_form) { ?>
        <!-- Category Form -->
        <form method="post" action="../api/maincategory.php" class="p-4 shadow-sm bg-white rounded">
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_category' : 'add_category'; ?>">
            <?php if ($edit_mode) { ?>
                <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
            <?php } ?>
            <div class="mb-3">
                <label class="form-label">Category Name</label>
                <input type="text" class="form-control" name="name" value="<?php echo $edit_mode ? htmlspecialchars($category['name']) : ''; ?>" required>
            </div>
            <?php if ($edit_mode) { ?>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo $category['status']=='active'?'selected':''; ?>>Active</option>
                        <option value="inactive" <?php echo $category['status']=='inactive'?'selected':''; ?>>Inactive</option>
                    </select>
                </div>
            <?php } ?>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-<?php echo $edit_mode?'edit':'plus'; ?>"></i>
                <?php echo $edit_mode?'Update Category':'Add Category'; ?>
            </button>
            <a href="main_categories.php" class="btn btn-secondary">Cancel</a>
        </form>
    <?php } ?>

    <!-- Category Table -->
    <h2 class="mt-4">All Categories</h2>
    <table class="table table-bordered" id="allTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Status</th>
                <th width="20%">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $res = $conn->query("SELECT * FROM main_categories ORDER BY category_id DESC");
            while($row = $res->fetch_assoc()) {
                $status_class = $row['status']=='active' ? 'status-active' : 'status-inactive';
                echo "<tr>
                    <td>".htmlspecialchars($row['name'])."</td>
                    <td class='$status_class'>".$row['status']."</td>
                    <td>
                        <a href='main_categories.php?form=edit&id={$row['category_id']}' class='btn btn-sm btn-warning'><i class='fas fa-edit'></i></a>

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
