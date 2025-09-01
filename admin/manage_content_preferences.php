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
    <title>Manage Content Preferences</title>
    <style>
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; font-weight: bold; }
        .content { margin-left: 250px; padding: 20px; transition: all 0.3s; }
        @media (max-width: 768px) { .content { margin-left: 0; padding-top: 60px; } }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container-fluid">
    <h1 class="mt-4">Manage Content Preferences</h1>

    <!-- Add Preference Button -->
    <a href="manage_content_preferences.php?form=add" class="btn btn-primary mb-3">
        <i class="fas fa-plus me-2"></i>Add Preference
    </a>

    <?php
    $show_form = isset($_GET['form']) && in_array($_GET['form'], ['add', 'edit']);
    $edit_mode = $show_form && $_GET['form'] == 'edit' && isset($_GET['id']);

    if ($edit_mode) {
        $pref_id = (int)$_GET['id'];
        $res = $conn->query("SELECT * FROM content_preferences WHERE preference_id = $pref_id");
        $preference = $res->fetch_assoc();
        if (!$preference) { 
            echo "<div class='alert alert-danger'>Preference not found.</div>";
            $show_form = false;
            $edit_mode = false;
        }
    }
    ?>

    <!-- Preference Form -->
    <?php if ($show_form) { ?>
        <form method="post" action="../api/preference.php" class="p-4 shadow-sm bg-white rounded">
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_preference' : 'add_preference'; ?>">
            <?php if ($edit_mode) { ?>
                <input type="hidden" name="preference_id" value="<?php echo $preference['preference_id']; ?>">
            <?php } ?>
            <div class="mb-3">
                <label class="form-label">Preference Name</label>
                <input type="text" class="form-control" name="preference_name" value="<?php echo $edit_mode ? htmlspecialchars($preference['preference_name']) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="4"><?php echo $edit_mode ? htmlspecialchars($preference['description']) : ''; ?></textarea>
            </div>
            <?php if ($edit_mode) { ?>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" <?php echo $edit_mode && $preference['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $edit_mode && $preference['status'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            <?php } ?>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-<?php echo $edit_mode ? 'edit' : 'plus'; ?> me-2"></i>
                <?php echo $edit_mode ? 'Update Preference' : 'Add Preference'; ?>
            </button>
            <a href="manage_content_preferences.php" class="btn btn-secondary">Cancel</a>
        </form>
    <?php } ?>

    <!-- Preferences Table -->
    <h2 class="mt-4">All Preferences</h2>
    <table class="table table-bordered" id="prefernceTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Status</th>
                <th width="20%">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $res = $conn->query("SELECT * FROM content_preferences ORDER BY preference_id DESC");
            while ($row = $res->fetch_assoc()) {
                $status_class = $row['status'] == 1 ? 'status-active' : 'status-inactive';
                $status_display = $row['status'] == 1 ? 'active' : 'inactive';
                echo "<tr>
                    <td>" . htmlspecialchars($row['preference_name']) . "</td>
                    <td>" . htmlspecialchars($row['description'] ?: 'No description') . "</td>
                    <td class='$status_class'>" . htmlspecialchars($status_display) . "</td>
                    <td>
                        <a href='manage_content_preferences.php?form=edit&id={$row['preference_id']}' class='btn btn-sm btn-warning me-1' title='Edit'><i class='fas fa-edit'></i></a>
                        <form method='post' action='../api/preference.php' style='display:inline-block;'>
                            <input type='hidden' name='action' value='delete_preference'>
                            <input type='hidden' name='preference_id' value='{$row['preference_id']}'>
                            <button type='submit' class='btn btn-sm btn-danger me-1' title='Delete'><i class='fas fa-trash'></i></button>
                        </form>
                        <form method='post' action='../api/preference.php' style='display:inline-block;'>
                            <input type='hidden' name='action' value='toggle_status'>
                            <input type='hidden' name='preference_id' value='{$row['preference_id']}'>
                            <input type='hidden' name='current_status' value='{$row['status']}'>
                            <button type='submit' class='btn btn-sm btn-" . ($row['status'] == 1 ? 'secondary' : 'success') . "' title='" . ($row['status'] == 1 ? 'Deactivate' : 'Activate') . "'>
                                <i class='fas fa-" . ($row['status'] == 1 ? 'pause' : 'play') . "'></i>
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