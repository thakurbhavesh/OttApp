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
    // Feedback message
    $message = '';
    $message_type = 'success';
    if (isset($_GET['message'])) {
        $message = htmlspecialchars($_GET['message']);
        $message_type = isset($_GET['error']) ? 'danger' : 'success';
    }
    if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="userTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="paid-tab" data-bs-toggle="tab" data-bs-target="#paid" type="button" role="tab">
                Paid Users
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="free-tab" data-bs-toggle="tab" data-bs-target="#free" type="button" role="tab">
                Free Users
            </button>
        </li>
    </ul>

    <div class="tab-content mt-4" id="userTabsContent">
        <!-- Paid Users -->
        <div class="tab-pane fade show active" id="paid" role="tabpanel">
            <?php echo renderUserTable($conn, 'paid'); ?>
        </div>

        <!-- Free / Not Paid Users -->
        <div class="tab-pane fade" id="free" role="tabpanel">
            <?php echo renderUserTable($conn, 'free'); ?>
        </div>
    </div>
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

<?php
// ---------- Helper to render table ----------
function renderUserTable($conn, $type) {
    $html = '<table class="table table-bordered" id="allUsers' . $type . '">
        <thead>
            <tr>
                <th>Username</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subscription Status</th>
                <th>Created At</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead><tbody>';

    if ($type === 'paid') {
        $stmt = $conn->prepare("SELECT * FROM users WHERE subscription_status='paid'");
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE subscription_status!='paid' OR subscription_status IS NULL");
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $status_class = $row['status'] === 'active' ? 'status-active' : 'status-inactive';
        $html .= "<tr>
            <td>" . htmlspecialchars($row['username']) . "</td>
            <td>" . htmlspecialchars($row['name']) . "</td>
            <td>" . htmlspecialchars($row['email']) . "</td>
            <td>" . htmlspecialchars($row['subscription_status']) . "</td>
            <td>" . htmlspecialchars($row['created_at']) . "</td>
            <td class='{$status_class}'>" . htmlspecialchars($row['status']) . "</td>
            <td>
                <a href='#' class='btn btn-sm btn-info view-history' data-user-id='{$row['user_id']}'><i class='fas fa-eye'></i> View History</a>
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
    $html .= '</tbody></table>';
    return $html;
}
?>

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
                data.data.forEach(function(h) {
                    html += '<tr>'
                        + '<td>' + (h.subscription_status || 'N/A') + '</td>'
                        + '<td>' + (h.subscription_end_date || 'N/A') + '</td>'
                        + '<td>' + (h.price ? parseFloat(h.price).toFixed(2) : '0.00') + '</td>'
                        + '<td>' + (h.change_date || 'N/A') + '</td>'
                        + '<td>' + (h.changed_by || 'N/A') + '</td>'
                        + '<td>' + (h.notes || 'N/A') + '</td>'
                        + '</tr>';
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
