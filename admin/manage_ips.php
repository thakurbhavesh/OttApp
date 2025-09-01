<?php
session_start();
include '../api/config.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/header.php'; ?>
    <title>Manage IPs</title>
    <style>
        .status-active { color: green!important; font-weight: bold; }
        .status-inactive { color: red!important; font-weight: bold; }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container-fluid">
    <h1 class="mt-4">Manage IP Addresses</h1>

    <!-- Add IP Button -->
    <a href="manage_ips.php?form=add" class="btn btn-primary mb-3">
        <i class="fas fa-plus me-2"></i>Add IP Address
    </a>

    <?php
    $show_form = isset($_GET['form']) && in_array($_GET['form'], ['add','edit']);
    $edit_mode = $show_form && $_GET['form'] == 'edit' && isset($_GET['id']);

    if ($edit_mode) {
        $ip_id = (int)$_GET['id'];
        $res = $conn->query("SELECT * FROM allowed_ips WHERE id=$ip_id");
        $ip_data = $res->fetch_assoc();
        if (!$ip_data) {
            echo "<div class='alert alert-danger'>IP not found.</div>";
            $show_form = false;
            $edit_mode = false;
        }
    }

    if ($show_form) { ?>
        <!-- IP Form -->
        <form method="post" action="../api/manageip.php" class="p-4 shadow-sm bg-white rounded">
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_ip' : 'add_ip'; ?>">
            <?php if ($edit_mode) { ?>
                <input type="hidden" name="id" value="<?php echo $ip_data['id']; ?>">
            <?php } ?>
            <div class="mb-3">
                <label class="form-label">IP Address</label>
                <input type="text" class="form-control" name="ip_address"
                       value="<?php echo $edit_mode ? htmlspecialchars($ip_data['ip_address']) : ''; ?>" required>
            </div>
            <?php if ($edit_mode) { ?>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" <?php echo $ip_data['status']==1?'selected':''; ?>>Active</option>
                        <option value="0" <?php echo $ip_data['status']==0?'selected':''; ?>>Inactive</option>
                    </select>
                </div>
            <?php } ?>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-<?php echo $edit_mode?'edit':'plus'; ?>"></i>
                <?php echo $edit_mode?'Update IP':'Add IP'; ?>
            </button>
            <a href="manage_ips.php" class="btn btn-secondary">Cancel</a>
        </form>
    <?php } ?>

    <!-- IPs Table -->
    <h2 class="mt-4">All IP Addresses</h2>
    <table class="table table-bordered" id="allTable">
        <thead>
            <tr>
                <th>IP Address</th>
                <th>Status</th>
                <th>Created At</th>
                <th width="25%">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $res = $conn->query("SELECT * FROM allowed_ips ORDER BY id DESC");
            while($row = $res->fetch_assoc()) {
                $status_class = $row['status']==1 ? 'status-active' : 'status-inactive';
                $status_text = $row['status']==1 ? 'Active' : 'Inactive';
                echo "<tr>
                    <td>".htmlspecialchars($row['ip_address'])."</td>
                    <td class='$status_class'>$status_text</td>
                    <td>".$row['created_at']."</td>
                    <td>
                        <a href='manage_ips.php?form=edit&id={$row['id']}' class='btn btn-sm btn-warning'>
                            <i class='fas fa-edit'></i>
                        </a>
                        <a href='../api/manageip.php?action=toggle_status&id={$row['id']}' 
                           class='btn btn-sm btn-info'
                           onclick=\"return confirm('Are you sure to change status?');\">
                           <i class='fas fa-sync'></i>
                        </a>
                        <a href='../api/manageip.php?action=delete&id={$row['id']}' 
                           class='btn btn-sm btn-danger'
                           onclick=\"return confirm('Delete this IP?');\">
                           <i class='fas fa-trash'></i>
                        </a>
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
