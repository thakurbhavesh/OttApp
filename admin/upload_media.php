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
    <title>Upload Media</title>
    <style>
        .status-active {
            color: green;
            font-weight: bold;
        }
        .status-inactive {
            color: red;
            font-weight: bold;
        }
    </style>
     <style>
        .content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding-top: 60px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container-fluid">
        <h1 class="mt-4">Upload Media</h1>
        
        <?php
        // Check if form should be shown (for add or edit)
        $show_form = isset($_GET['form']) && in_array($_GET['form'], ['add', 'edit']);
        $edit_mode = $show_form && $_GET['form'] == 'edit' && isset($_GET['id']);
        
        if ($edit_mode) {
            $media_id = (int)$_GET['id'];
            $result = $conn->query("SELECT * FROM media WHERE media_id = $media_id");
            $media = $result->fetch_assoc();
            if (!$media) {
                echo "<div class='alert alert-danger'>Media not found.</div>";
                $show_form = false;
                $edit_mode = false;
            }
        }
        ?>

        <!-- Add/Edit Form (shown only when form=add or form=edit) -->
        <?php if ($show_form) { ?>
            <form method="post" action="../api/media.php" class="p-4 shadow-sm rounded bg-white" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_media' : 'add_media'; ?>">
                <?php if ($edit_mode) { ?>
                    <input type="hidden" name="media_id" value="<?php echo $media['media_id']; ?>">
                <?php } ?>
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" name="title" value="<?php echo $edit_mode ? htmlspecialchars($media['title']) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">URL (optional)</label>
                    <input type="url" class="form-control" name="url" value="<?php echo $edit_mode ? htmlspecialchars($media['url']) : ''; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">File Upload (optional)</label>
                    <input type="file" class="form-control" name="file">
                    <?php if ($edit_mode && $media['file_path']) { ?>
                        <small>Current file: <a href="../uploads/<?php echo htmlspecialchars($media['file_path']); ?>" target="_blank"><?php echo htmlspecialchars($media['file_path']); ?></a></small>
                    <?php } ?>
                </div>
                <?php if ($edit_mode) { ?>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status" required>
                            <option value="active" <?php echo $media['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $media['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                <?php } ?>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-<?php echo $edit_mode ? 'edit' : 'plus'; ?> me-2"></i>
                    <?php echo $edit_mode ? 'Update Media' : 'Add Media'; ?>
                </button>
                <a href="upload_media.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php } else { ?>
            <!-- Add Media Button -->
            <a href="upload_media.php?form=add" class="btn btn-primary mb-3">
                <i class="fas fa-plus me-2"></i>Add Media
            </a>
        <?php } ?>

        <!-- Media Table -->
        <h2 class="mt-5">All Media</h2>
        <table id="mediaTable" class="table table-bordered">
    <thead>
        <tr>
            <th>Title</th>
            <th>URL</th>
            <th>File</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $result = $conn->query("SELECT * FROM media");
        while ($row = $result->fetch_assoc()) {
            $status_class = $row['status'] == 'active' ? 'status-active' : 'status-inactive';
            echo "<tr>
                <td>" . htmlspecialchars($row['title']) . "</td>
                <td>" . ($row['url'] 
                    ? '<a href="' . htmlspecialchars($row['url']) . '" target="_blank" class="text-truncate" style="max-width: 200px; display: inline-block;">' . htmlspecialchars($row['url']) . '</a> 
                       <button class="btn btn-sm btn-info copy-btn ms-2" data-url="' . htmlspecialchars($row['url']) . '" title="Copy URL"><i class="fas fa-copy"></i></button>' 
                    : '-') . "</td>
                <td>" . ($row['file_path'] 
                    ? '<a href="../' . htmlspecialchars($row['file_path']) . '" target="_blank" class="text-truncate" style="max-width: 200px; display: inline-block;">' . htmlspecialchars($row['file_path']) . '</a> 
                       <button class="btn btn-sm btn-info copy-btn ms-2" data-url="../' . htmlspecialchars($row['file_path']) . '" title="Copy File URL"><i class="fas fa-copy"></i></button>' 
                    : '-') . "</td>
                <td class='$status_class'>" . htmlspecialchars($row['status']) . "</td>
                <td>" . htmlspecialchars($row['created_at']) . "</td>
                <td>
                    <a href='upload_media.php?form=edit&id={$row['media_id']}' class='btn btn-sm btn-warning me-1' title='Edit'><i class='fas fa-edit'></i></a>
                    <form method='post' action='../api/media.php' style='display:inline;'>
                        <input type='hidden' name='action' value='delete_media'>
                        <input type='hidden' name='media_id' value='{$row['media_id']}'>
                        <button type='submit' class='btn btn-sm btn-danger me-1' title='Delete'><i class='fas fa-trash'></i></button>
                    </form>
                    <form method='post' action='../api/media.php' style='display:inline;'>
                        <input type='hidden' name='action' value='toggle_status'>
                        <input type='hidden' name='media_id' value='{$row['media_id']}'>
                        <input type='hidden' name='current_status' value='{$row['status']}'>
                        <button type='submit' class='btn btn-sm btn-" . ($row['status'] == 'active' ? 'secondary' : 'success') . "' title='" . ($row['status'] == 'active' ? 'Deactivate' : 'Activate') . "'>
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