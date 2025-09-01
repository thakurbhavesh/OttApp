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
    <title>Manage Languages</title>
    <style>
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; font-weight: bold; }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container-fluid">
    <h1 class="mt-4">Manage Languages</h1>

    <!-- Add Language Button -->
    <a href="manage_languages.php?form=add" class="btn btn-primary mb-3">
        <i class="fas fa-plus me-2"></i>Add Language
    </a>

    <?php
    $show_form = isset($_GET['form']) && in_array($_GET['form'], ['add','edit']);
    $edit_mode = $show_form && $_GET['form'] == 'edit' && isset($_GET['id']);

    if ($edit_mode) {
        $lang_id = (int)$_GET['id'];
        $res = $conn->query("SELECT * FROM languages WHERE language_id=$lang_id");
        $language = $res->fetch_assoc();
        if (!$language) { 
            echo "<div class='alert alert-danger'>Language not found.</div>";
            $show_form = false;
            $edit_mode = false;
        }
    }

    if ($show_form) { ?>
        <!-- Language Form -->
        <form method="post" action="../api/language.php" class="p-4 shadow-sm bg-white rounded">
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_language' : 'add_language'; ?>">
            <?php if ($edit_mode) { ?>
                <input type="hidden" name="language_id" value="<?php echo $language['language_id']; ?>">
            <?php } ?>
            <div class="mb-3">
                <label class="form-label">Language Name</label>
                <input type="text" class="form-control" name="name" value="<?php echo $edit_mode ? htmlspecialchars($language['name']) : ''; ?>" required>
            </div>
            <?php if ($edit_mode) { ?>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo $language['status']=='active'?'selected':''; ?>>Active</option>
                        <option value="inactive" <?php echo $language['status']=='inactive'?'selected':''; ?>>Inactive</option>
                    </select>
                </div>
            <?php } ?>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-<?php echo $edit_mode?'edit':'plus'; ?>"></i>
                <?php echo $edit_mode?'Update Language':'Add Language'; ?>
            </button>
            <a href="manage_languages.php" class="btn btn-secondary">Cancel</a>
        </form>
    <?php } ?>

    <!-- Languages Table -->
    <h2 class="mt-4">All Languages</h2>
    <table class="table table-bordered" id="languageTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Status</th>
                <th width="20%">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $res = $conn->query("SELECT * FROM languages ORDER BY language_id DESC");
            while($row = $res->fetch_assoc()) {
                $status_class = $row['status']=='active' ? 'status-active' : 'status-inactive';
                echo "<tr>
                    <td>".htmlspecialchars($row['name'])."</td>
                    <td class='$status_class'>".$row['status']."</td>
                    <td>
                        <a href='manage_languages.php?form=edit&id={$row['language_id']}' class='btn btn-sm btn-warning'><i class='fas fa-edit'></i></a>
                        
                        <form method='post' action='../api/language.php' style='display:inline-block;'>
                            <input type='hidden' name='action' value='delete_language'>
                            <input type='hidden' name='language_id' value='{$row['language_id']}'>
                            <button type='submit' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>
                        </form>

                        <form method='post' action='../api/language.php' style='display:inline-block;'>
                            <input type='hidden' name='action' value='toggle_status'>
                            <input type='hidden' name='language_id' value='{$row['language_id']}'>
                            <input type='hidden' name='current_status' value='{$row['status']}'>
                            <button type='submit' class='btn btn-sm btn-".($row['status']=='active'?'secondary':'success')."'>
                                <i class='fas fa-".($row['status']=='active'?'pause':'play')."'></i>
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
