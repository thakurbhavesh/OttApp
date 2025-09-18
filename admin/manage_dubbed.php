<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include '../api/config.php';
?>

<?php include 'includes/header.php'; ?>
<style>
    .status-active {
        color: green !important;
        font-weight: bold;
    }
    .status-inactive {
        color: red !important;
        font-weight: bold;
    }
    .banner-active {
        color: blue !important;
        font-weight: bold;
    }
    .banner-inactive {
        color: gray !important;
    }
    .dubbed-in-hindi-active {
        color: purple !important;
        font-weight: bold;
    }
    .dubbed-in-hindi-inactive {
        color: gray !important;
    }
</style>

<?php include 'includes/navbar.php'; ?>
<div class="container-fluid">
    <h2 class="mt-2">Dubbed In Hindi Content</h2>
    <div class="table-responsive">
        <table id="contentTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Language</th>
                    <th>Preference</th>
                    <th>Release Date</th>
                    <th>Dubbed In Hindi Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT c.*, cat.name as category_name, l.name as language_name, cp.preference_name 
                                        FROM content c 
                                        JOIN categories cat ON c.category_id = cat.category_id 
                                        LEFT JOIN languages l ON c.language_id = l.language_id 
                                        LEFT JOIN content_preferences cp ON c.preference_id = cp.preference_id 
                                        WHERE c.status = 'active' and c.language_id = 1
                                        ORDER BY c.release_date DESC");
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $status_class = $row['status'] == 'active' ? 'status-active' : 'status-inactive';
                        $banner_class = $row['banner'] == 1 ? 'banner-active' : 'banner-inactive';
                        $banner_text = $row['banner'] == 1 ? 'Active Banner' : 'Inactive Banner';
                        $banner_icon = $row['banner'] == 1 ? 'on' : 'off';
                        $dubbed_in_hindi_class = $row['dubbed_in_hindi'] == 1 ? 'dubbed-in-hindi-active' : 'dubbed-in-hindi-inactive';
                        $dubbed_in_hindi_text = $row['dubbed_in_hindi'] == 1 ? 'Active Dubbed In Hindi' : 'Inactive Dubbed In Hindi';
                        $dubbed_in_hindi_icon = $row['dubbed_in_hindi'] == 1 ? 'on' : 'off';
                        echo "<tr data-content-id='{$row['content_id']}'>
                            <td>" . htmlspecialchars($row['title']) . "</td>
                            <td>" . htmlspecialchars($row['category_name']) . "</td>
                            <td>" . htmlspecialchars($row['language_name'] ?? 'N/A') . "</td>
                            <td>" . htmlspecialchars($row['preference_name'] ?? 'N/A') . "</td>
                            <td>" . htmlspecialchars($row['release_date']) . "</td>
                            <td class='$dubbed_in_hindi_class dubbed-status' data-current-status='{$row['dubbed_in_hindi']}'>$dubbed_in_hindi_text</td>
                            <td>
                                <form method='post' action='../api/content.php' style='display:inline;' onsubmit='return toggleDubbedInHindi(event, {$row['content_id']}, {$row['dubbed_in_hindi']})'>
                                    <input type='hidden' name='action' value='toggle_dubbed_in_hindi'>
                                    <input type='hidden' name='content_id' value='{$row['content_id']}'>
                                    <input type='hidden' name='current_dubbed_in_hindi' value='{$row['dubbed_in_hindi']}'>
                                    <button type='submit' class='btn btn-sm btn-purple toggle-btn'>
                                        <i class='fas fa-toggle-$dubbed_in_hindi_icon'></i>
                                    </button>
                                </form>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No active content found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle Dubbed In Hindi status toggle
        function toggleDubbedInHindi(event, contentId, currentDubbedInHindi) {
            event.preventDefault();
            var newDubbedInHindi = currentDubbedInHindi === 1 ? 0 : 1;
            var form = event.target;
            if (!form) {
                console.error('Form element not found');
                return false;
            }
            var formData = new FormData(form);
            formData.set('current_dubbed_in_hindi', currentDubbedInHindi);
            formData.set('new_dubbed_in_hindi', newDubbedInHindi);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../api/content.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.status === 'success') {
                            // Update the UI dynamically
                            var row = document.querySelector(`tr[data-content-id="${contentId}"]`);
                            if (row) {
                                var statusCell = row.querySelector('.dubbed-status');
                                var toggleButton = row.querySelector('.toggle-btn i');
                                if (statusCell) {
                                    statusCell.className = 'dubbed-status ' + (newDubbedInHindi === 1 ? 'dubbed-in-hindi-active' : 'dubbed-in-hindi-inactive');
                                    statusCell.dataset.currentStatus = newDubbedInHindi;
                                    statusCell.textContent = newDubbedInHindi === 1 ? 'Active Dubbed In Hindi' : 'Inactive Dubbed In Hindi';
                                }
                                if (toggleButton) {
                                    toggleButton.className = 'fas fa-toggle-' + (newDubbedInHindi === 1 ? 'on' : 'off');
                                }
                                alert(response.message); // Show success message
                            }
                        } else {
                            alert('Error: ' + response.message);
                        }
                    } else {
                        alert('Server error: Status ' + xhr.status);
                    }
                }
            };
            xhr.send(formData);
            return false;
        }
    });
</script>
</body>
</html>