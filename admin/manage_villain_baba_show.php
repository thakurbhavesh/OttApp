<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include '../api/config.php';
?>

    <style>
        .status-active { color: green !important; font-weight: bold; }
        .status-inactive { color: red !important; font-weight: bold; }
        .banner-active { color: blue !important; font-weight: bold; }
        .banner-inactive { color: gray !important; }
        .villain-baba-show-active { color: #ff1493 !important; font-weight: bold; }
        .villain-baba-show-inactive { color: gray !important; }
        .btn-pink { background-color: #ff1493; color: white; }
        .btn-pink:hover { background-color: #e01183; }
    </style>

<?php include 'includes/header.php'; ?>
    <?php include 'includes/navbar.php'; ?>
    <div class="container-fluid">
        <h2 class="mt-2">THE VILLAIN BABA SHOW Content</h2>
        <div class="table-responsive">
            <table id="contentTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Language</th>
                        <th>Preference</th>
                        <th>Industry</th>
                        <th>Release Date</th>
                        <th>THE VILLAIN BABA SHOW Status</th>
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
                                            WHERE c.status = 'active'
                                            ORDER BY c.release_date DESC");
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            $status_class = $row['status'] == 'active' ? 'status-active' : 'status-inactive';
                            $banner_class = isset($row['banner']) && $row['banner'] == 1 ? 'banner-active' : 'banner-inactive';
                            $banner_text = isset($row['banner']) && $row['banner'] == 1 ? 'Active Banner' : 'Inactive Banner';
                            $banner_icon = isset($row['banner']) && $row['banner'] == 1 ? 'on' : 'off';
                            $villain_baba_show_class = isset($row['villain_baba_show']) && $row['villain_baba_show'] == 1 ? 'villain-baba-show-active' : 'villain-baba-show-inactive';
                            $villain_baba_show_text = isset($row['villain_baba_show']) && $row['villain_baba_show'] == 1 ? 'Active THE VILLAIN BABA SHOW' : 'Inactive THE VILLAIN BABA SHOW';
                            $villain_baba_show_icon = isset($row['villain_baba_show']) && $row['villain_baba_show'] == 1 ? 'on' : 'off';
                            $current_villain = isset($row['villain_baba_show']) ? $row['villain_baba_show'] : 0;
                            echo "<tr>
                                <td>" . htmlspecialchars($row['title']) . "</td>
                                <td>" . htmlspecialchars($row['category_name']) . "</td>
                                <td>" . htmlspecialchars($row['language_name'] ?? 'N/A') . "</td>
                                <td>" . htmlspecialchars($row['preference_name'] ?? 'N/A') . "</td>
                                <td>" . htmlspecialchars($row['industry'] ?? 'N/A') . "</td>
                                <td>" . htmlspecialchars($row['release_date']) . "</td>
                                <td class='$villain_baba_show_class'>$villain_baba_show_text</td>
                                <td>
                                    <form method='post' action='../api/content.php' style='display:inline;' onsubmit='return toggleVillainBabaShow(event, {$row['content_id']}, $current_villain)'>
                                        <input type='hidden' name='action' value='toggle_villain_baba_show'>
                                        <input type='hidden' name='content_id' value='{$row['content_id']}'>
                                        <input type='hidden' name='current_villain_baba_show' value='$current_villain'>
                                        <button type='submit' class='btn btn-sm btn-pink'>
                                            <i class='fas fa-toggle-$villain_baba_show_icon'></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8'>Error: " . $conn->error . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#contentTable').DataTable({
                "pageLength": 10,
                "destroy": true,
                "scrollX": true
            });
        });

        function toggleVillainBabaShow(event, contentId, currentVillainBabaShow) {
            event.preventDefault();
            var form = event.target.closest('form');
            if (!form) {
                console.error('THE VILLAIN BABA SHOW form not found');
                return false;
            }
            var newVillainBabaShow = currentVillainBabaShow === 1 ? 0 : 1;
            var formData = new FormData(form);
            formData.set('current_villain_baba_show', currentVillainBabaShow);
            formData.set('new_villain_baba_show', newVillainBabaShow);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../api/content.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        location.reload();
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