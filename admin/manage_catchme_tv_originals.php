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
    .top-shows-active {
        color: purple !important;
        font-weight: bold;
    }
    .top-shows-inactive {
        color: gray !important;
    }
    .catchme-tv-originals-active {
        color: orange !important; /* New style for CATCHME TV Originals */
        font-weight: bold;
    }
    .catchme-tv-originals-inactive {
        color: gray !important;
    }
</style>

<?php include 'includes/navbar.php'; ?>
<div class="container-fluid">
    <h2 class="mt-2">Top Shows Content</h2>
    <div class="table-responsive">
        <table id="contentTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Language</th>
                    <th>Preference</th>
                    <th>Release Date</th>
                    <th>CATCHME TV Originals</th> <!-- New column -->
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
                        WHERE c.status = 'active'");
                while ($row = $result->fetch_assoc()) {
                    $status_class = $row['status'] == 'active' ? 'status-active' : 'status-inactive';
                    $banner_class = $row['banner'] == 1 ? 'banner-active' : 'banner-inactive';
                    $banner_text = $row['banner'] == 1 ? 'Active Banner' : 'Inactive Banner';
                    $banner_icon = $row['banner'] == 1 ? 'on' : 'off';
                    $top_shows_class = $row['top_shows'] == 1 ? 'top-shows-active' : 'top-shows-inactive';
                    $top_shows_text = $row['top_shows'] == 1 ? 'Active Top Shows' : 'Inactive Top Shows';
                    $top_shows_icon = $row['top_shows'] == 1 ? 'on' : 'off';
                    $catchme_tv_originals_class = $row['catchme_tv_originals'] == 1 ? 'catchme-tv-originals-active' : 'catchme-tv-originals-inactive'; // New class
                    $catchme_tv_originals_text = $row['catchme_tv_originals'] == 1 ? 'Active CATCHME TV Original' : 'Inactive CATCHME TV Original'; // New text
                    $catchme_tv_originals_icon = $row['catchme_tv_originals'] == 1 ? 'on' : 'off'; // New icon
                    echo "<tr>
                        <td>" . htmlspecialchars($row['title']) . "</td>
                        <td>" . htmlspecialchars($row['category_name']) . "</td>
                        <td>" . htmlspecialchars($row['language_name'] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($row['preference_name'] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($row['release_date']) . "</td>
                        <td class='$catchme_tv_originals_class'>$catchme_tv_originals_text</td> <!-- New column -->
                        <td>
                         
                            <form method='post' action='../api/content.php' style='display:inline;' onsubmit='return toggleCatchmeTVOriginals(event, {$row['content_id']}, {$row['catchme_tv_originals']})'>
                                <input type='hidden' name='action' value='toggle_catchme_tv_originals'>
                                <input type='hidden' name='content_id' value='{$row['content_id']}'>
                                <input type='hidden' name='current_catchme_tv_originals' value='{$row['catchme_tv_originals']}'>
                                <button type='submit' class='btn btn-sm btn-orange'> <!-- New button with orange color -->
                                    <i class='fas fa-toggle-$catchme_tv_originals_icon'></i>
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

   

    // Handle CATCHME TV Originals status toggle
    function toggleCatchmeTVOriginals(event, contentId, currentCatchmeTVOriginals) {
        event.preventDefault();
        var newCatchmeTVOriginals = currentCatchmeTVOriginals === 1 ? 0 : 1;
        var form = event.target;
        var formData = new FormData(form);
        formData.set('current_catchme_tv_originals', currentCatchmeTVOriginals);
        formData.set('new_catchme_tv_originals', newCatchmeTVOriginals);

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