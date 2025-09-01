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
</style>

<?php include 'includes/navbar.php'; ?>
<div class="container-fluid">
    <h2 class="mt-2">Banner Content</h2>
    <div class="table-responsive">
        <table id="contentTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Language</th>
                    <th>Preference</th>
                    <th>Release Date</th>
                    <th>Status</th>
                    <th>Banner Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT c.*, cat.name as category_name, l.name as language_name, cp.preference_name 
                                        FROM content c 
                                        JOIN categories cat ON c.category_id = cat.category_id 
                                        LEFT JOIN languages l ON c.language_id = l.language_id 
                                        LEFT JOIN content_preferences cp ON c.preference_id = cp.preference_id where c.status='active'");
                while ($row = $result->fetch_assoc()) {
                    $status_class = $row['status'] == 'active' ? 'status-active' : 'status-inactive';
                    $banner_class = $row['banner'] == 1 ? 'banner-active' : 'banner-inactive';
                    $banner_text = $row['banner'] == 1 ? 'Active Banner' : 'Inactive Banner';
                    $banner_icon = $row['banner'] == 1 ? 'on' : 'off';
                    echo "<tr>
                        <td>" . htmlspecialchars($row['title']) . "</td>
                        <td>" . htmlspecialchars($row['category_name']) . "</td>
                        <td>" . htmlspecialchars($row['language_name'] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($row['preference_name'] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($row['release_date']) . "</td>
                        <td class='$status_class'>" . htmlspecialchars($row['status']) . "</td>
                        <td class='$banner_class'>$banner_text</td>
                        <td>
                            <form method='post' action='../api/content.php' style='display:inline;' onsubmit='return toggleBanner(event, {$row['content_id']}, {$row['banner']})'>
                                <input type='hidden' name='action' value='toggle_banner'>
                                <input type='hidden' name='content_id' value='{$row['content_id']}'>
                                <input type='hidden' name='current_banner' value='{$row['banner']}'>
                                <button type='submit' class='btn btn-sm btn-info'>
                                    <i class='fas fa-toggle-$banner_icon'></i>
                                </button>
                            </form>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script>
    // Handle banner status toggle
    function toggleBanner(event, contentId, currentBanner) {
        event.preventDefault();
        var newBanner = currentBanner === 1 ? 0 : 1;
        var form = event.target;
        var formData = new FormData(form);
        formData.set('current_banner', currentBanner);
        formData.set('new_banner', newBanner);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../api/content.php', true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    location.reload(); // Reload page to reflect changes
                } else {
                    alert('Error: ' + response.message);
                }
            }
        };
        xhr.send(formData);
        return false;
    }
</script>
</body>

</html>