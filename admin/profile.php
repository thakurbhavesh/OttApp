<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include '../api/config.php';

// Current user
$auth_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, status, role, created_at FROM auth_users WHERE auth_id = ?");
$stmt->bind_param("i", $auth_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If admin → fetch all users
$users = [];
if ($_SESSION['role'] === 'Admin') {
    $stmt = $conn->prepare("SELECT auth_id, username, email, role, status, created_at FROM auth_users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container-fluid mt-5">
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body">
                    <h2 class="text-center mb-4 text-primary">My Profile</h2>
                    <div class="row">
                        <div class="col-md-6 offset-md-3">
                            <div class="list-group mb-4">
                                <div class="list-group-item"><strong>Username:</strong> <?= htmlspecialchars($admin['username'] ?? 'N/A'); ?></div>
                                <div class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($admin['email'] ?? 'N/A'); ?></div>
                                <div class="list-group-item"><strong>Status:</strong> <span class="badge bg-<?= ($admin['status'] ?? '') === 'active' ? 'success' : 'danger'; ?>"><?= htmlspecialchars($admin['status'] ?? 'N/A'); ?></span></div>
                                <div class="list-group-item"><strong>Role:</strong> <span class="badge bg-info"><?= htmlspecialchars($admin['role'] ?? 'N/A'); ?></span></div>
                                <div class="list-group-item"><strong>Joined:</strong> <?= isset($admin['created_at']) ? date('F d, Y', strtotime($admin['created_at'])) : 'N/A'; ?></div>
                            </div>

                            <div class="d-flex justify-content-center gap-3">
                                <a href="dashboard.php" class="btn btn-outline-primary"><i class="fas fa-home"></i> Dashboard</a>
                                <?php if ($_SESSION['role'] === 'Admin') { ?>
                                    <a href="register.php" class="btn btn-success"><i class="fas fa-user-plus"></i> Add User</a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($_SESSION['role'] === 'Admin') { ?>
    <!-- User List Table -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body">
                    <h3 class="mb-4 text-center text-secondary">Manage Users</h3>
                    <div id="alert-container"></div>
                    <table class="table table-hover align-middle" id=allTable>
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $row) { ?>
                            <tr id="user-<?= $row['auth_id']; ?>">
                                <td><?= $row['auth_id']; ?></td>
                                <td><?= htmlspecialchars($row['username']); ?></td>
                                <td><?= htmlspecialchars($row['email']); ?></td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($row['role']); ?></span></td>
                                <td>
                                    <button class="btn btn-sm toggle-status <?= $row['status'] === 'active' ? 'btn-success' : 'btn-danger'; ?>" data-id="<?= $row['auth_id']; ?>" data-loading="false">
                                        <span class="status-text"><?= ucfirst($row['status']); ?></span>
                                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                    </button>
                                </td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning edit-user" data-id="<?= $row['auth_id']; ?>" data-username="<?= htmlspecialchars($row['username']); ?>" data-email="<?= htmlspecialchars($row['email']); ?>" data-role="<?= $row['role']; ?>">✏️ Edit</button>
                                    <button class="btn btn-sm btn-danger delete-user" data-id="<?= $row['auth_id']; ?>">🗑️ Delete</button>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" id="edit_username" required pattern="[A-Za-z0-9_]{3,20}" title="Username must be 3-20 characters long and contain only letters, numbers, or underscores.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" id="edit_role" class="form-control" required>
                            <option value="User">User</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div id="edit-error" class="text-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="save-changes-btn">
                        <span class="btn-text">Save changes</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Show alert
    function showAlert(message, type) {
        $("#alert-container").html(`
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
    }

    // Toggle status
    $(".toggle-status").click(function() {
        let button = $(this);
        let id = button.data("id");
        if (button.data("loading")) return;

        button.data("loading", true);
        button.find(".spinner-border").removeClass("d-none");
        button.find(".status-text").addClass("d-none");

        $.ajax({
            url: "../api/user_actions.php",
            type: "POST",
            data: { action: "toggle_status", id: id, csrf_token: "<?= $_SESSION['csrf_token']; ?>" },
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    location.reload();
                } else {
                    showAlert(res.message || "Failed to toggle status.", "danger");
                }
            },
            error: function() {
                showAlert("An error occurred while toggling status.", "danger");
            },
            complete: function() {
                button.data("loading", false);
                button.find(".spinner-border").addClass("d-none");
                button.find(".status-text").removeClass("d-none");
            }
        });
    });

    // Delete user
    $(".delete-user").click(function() {
        if (!confirm("Are you sure you want to delete this user?")) return;

        let button = $(this);
        let id = button.data("id");
        $.ajax({
            url: "../api/user_actions.php",
            type: "POST",
            data: { action: "delete", id: id, csrf_token: "<?= $_SESSION['csrf_token']; ?>" },
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    $("#user-" + id).fadeOut();
                    showAlert("User deleted successfully.", "success");
                } else {
                    showAlert(res.message || "Failed to delete user.", "danger");
                }
            },
            error: function() {
                showAlert("An error occurred while deleting the user.", "danger");
            }
        });
    });

    // Edit user - open modal
    $(".edit-user").click(function() {
        $("#edit_id").val($(this).data("id"));
        $("#edit_username").val($(this).data("username"));
        $("#edit_email").val($(this).data("email"));
        $("#edit_role").val($(this).data("role"));
        $("#edit-error").addClass("d-none").text("");
        $("#editUserModal").modal("show");
    });

    // Save edit
    $("#editUserForm").submit(function(e) {
        e.preventDefault();
        let button = $("#save-changes-btn");
        if (button.find(".spinner-border").hasClass("d-none")) {
            button.find(".spinner-border").removeClass("d-none");
            button.find(".btn-text").addClass("d-none");
            $("#edit-error").addClass("d-none").text("");

            $.ajax({
                url: "../api/user_actions.php",
                type: "POST",
                data: $(this).serialize() + "&action=edit",
                dataType: "json",
                success: function(res) {
                    if (res.status === "success") {
                        location.reload();
                    } else {
                        $("#edit-error").removeClass("d-none").text(res.message || "Failed to update user.");
                    }
                },
                error: function() {
                    $("#edit-error").removeClass("d-none").text("An error occurred while updating the user.");
                },
                complete: function() {
                    button.find(".spinner-border").addClass("d-none");
                    button.find(".btn-text").removeClass("d-none");
                }
            });
        }
    });
});
</script>