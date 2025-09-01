<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include '../api/config.php';
?>

    <?php include 'includes/header.php'; ?>
    <title>Manage Users</title>
    <style>
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; font-weight: bold; }
        #subscriptionHistoryModal .modal-body { max-height: 400px; overflow-y: auto; }
    </style>
    <?php include 'includes/navbar.php'; ?>
    <div class="container-fluid">
        <h1 class="mt-4">Manage Users</h1>

        <?php
        // Initialize variables and handle form submission feedback
        $message = '';
        $message_type = 'success';
        if (isset($_GET['message'])) {
            $message = htmlspecialchars($_GET['message']);
            $message_type = isset($_GET['error']) ? 'danger' : 'success';
        }

        // Check if form should be shown (for add or edit)
        $show_form = isset($_GET['form']) && in_array($_GET['form'], ['add', 'edit']);
        $edit_mode = $show_form && $_GET['form'] == 'edit' && isset($_GET['id']);

        if ($edit_mode) {
            $user_id = (int)$_GET['id'];
            $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            if (!$user) {
                $message = 'User not found.';
                $message_type = 'danger';
                $show_form = false;
                $edit_mode = false;
            }
        }
        ?>

        <!-- Display message -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form (shown only when form=add or form=edit) -->
        <?php if ($show_form) { ?>
            <form method="post" action="../api/users.php" class="p-4 shadow-sm rounded bg-white">
                <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_user' : 'add_user'; ?>">
                <?php if ($edit_mode) { ?>
                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                <?php } ?>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" readonly value="<?php echo $edit_mode ? htmlspecialchars($user['username']) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" readonly value="<?php echo $edit_mode ? htmlspecialchars($user['email']) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Subscription Status</label>
                    <select class="form-control" name="subscription_status" required>
                        <option value="free" <?php echo $edit_mode && $user['subscription_status'] == 'free' ? 'selected' : ''; ?>>Free</option>
                        <option value="paid" <?php echo $edit_mode && $user['subscription_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-control" name="status" required>
                        <option value="active" <?php echo $edit_mode && $user['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $edit_mode && $user['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-<?php echo $edit_mode ? 'edit' : 'plus'; ?> me-2"></i>
                    <?php echo $edit_mode ? 'Update User' : 'Add User'; ?>
                </button>
                <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php } else { ?>
            <!-- Add User Button -->
            <a href="manage_users.php?form=add" class="btn btn-primary mb-3">
                <i class="fas fa-plus me-2"></i>Add User
            </a>
        <?php } ?>

        <!-- Users Table -->
        <h2 class="mt-5">All Users</h2>
        <table id="allTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Subscription Status</th>
                    <th>Created At</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conn->prepare("SELECT * FROM users");
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $status_class = $row['status'] == 'active' ? 'status-active' : 'status-inactive';
                    echo "<tr>
                        <td>" . htmlspecialchars($row['username']) . "</td>
                        <td>" . htmlspecialchars($row['email']) . "</td>
                        <td>" . htmlspecialchars($row['subscription_status']) . "</td>
                        <td>" . htmlspecialchars($row['created_at']) . "</td>
                        <td class='$status_class'>" . htmlspecialchars($row['status']) . "</td>
                        <td>
                            <a href='#' class='btn btn-sm btn-info view-history' data-user-id='{$row['user_id']}'><i class='fas fa-eye'></i> View History</a>
                            <a href='manage_users.php?form=edit&id={$row['user_id']}' class='btn btn-sm btn-warning'><i class='fas fa-edit'></i></a>
                            <form method='post' action='../api/users.php' style='display:inline;'>
                                <input type='hidden' name='action' value='delete_user'>
                                <input type='hidden' name='user_id' value='{$row['user_id']}'>
                                <button type='submit' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>
                            </form>
                            <form method='post' action='../api/users.php' style='display:inline;'>
                                <input type='hidden' name='action' value='toggle_status'>
                                <input type='hidden' name='user_id' value='{$row['user_id']}'>
                                <input type='hidden' name='current_status' value='{$row['status']}'>
                                <button type='submit' class='btn btn-sm btn-" . ($row['status'] == 'active' ? 'secondary' : 'success') . "'>
                                    <i class='fas fa-" . ($row['status'] == 'active' ? 'pause' : 'play') . "'></i>
                                </button>
                            </form>
                        </td>
                    </tr>";
                }
                $stmt->close();
                ?>
            </tbody>
        </table>
    </div>

    <!-- Subscription History Modal -->
    <div class="modal fade" id="subscriptionHistoryModal" tabindex="-1" aria-labelledby="subscriptionHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="subscriptionHistoryModalLabel">Subscription History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="subscriptionHistoryBody">
                    <p>Loading...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script>
        $(document).ready(function() {
            $('.view-history').on('click', function(e) {
                e.preventDefault();
                var userId = $(this).data('user-id');
                $('#subscriptionHistoryBody').html('<p>Loading...</p>');

                $.getJSON('../api/subscription_history.php', { user_id: userId }, function(data) {
                    if (data.status === 'success' && data.data.length > 0) {
                        var html = '<table class="table table-bordered"><thead><tr><th>Status</th><th>End Date</th><th>Price (INR)</th><th>Change Date</th><th>Changed By</th><th>Notes</th></tr></thead><tbody>';
                        data.data.forEach(function(history) {
                            html += '<tr>';
                            html += '<td>' + (history.subscription_status || 'N/A') + '</td>';
                            html += '<td>' + (history.subscription_end_date || 'N/A') + '</td>';
                            html += '<td>' + (history.price ? parseFloat(history.price).toFixed(2) : '0.00') + '</td>';
                            html += '<td>' + (history.change_date || 'N/A') + '</td>';
                            html += '<td>' + (history.changed_by || 'N/A') + '</td>';
                            html += '<td>' + (history.notes || 'N/A') + '</td>';
                            html += '</tr>';
                        });
                        html += '</tbody></table>';
                        $('#subscriptionHistoryBody').html(html);
                    } else {
                        $('#subscriptionHistoryBody').html('<p>No subscription history found.</p>');
                    }
                }).fail(function() {
                    $('#subscriptionHistoryBody').html('<p>Error loading history.</p>');
                });

                $('#subscriptionHistoryModal').modal('show');
            });
        });
    </script>
</body>
</html>