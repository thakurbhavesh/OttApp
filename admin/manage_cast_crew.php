<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include '../api/config.php';
include 'includes/header.php';
include 'includes/navbar.php';

$content_id = isset($_GET['content_id']) ? (int)$_GET['content_id'] : 0;
?>

<style>
    .cast-crew-form {
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 5px;
    }
    .add-cast-crew-btn {
        margin-top: 10px;
    }
    .table-responsive {
        overflow-x: auto;
    }
    @media (max-width: 768px) {
        .table th, .table td {
            font-size: 14px;
            padding: 8px;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
    }
</style>

<div class="container-fluid">
    <h1 class="mt-4">Manage Cast & Crew</h1>

    <!-- Content Selection Dropdown -->
    <div class="mb-3">
        <label class="form-label">Select Content</label>
        <select class="form-control" id="content_id" name="content_id" onchange="loadCastCrew()" required>
            <option value="">Select Content</option>
            <?php
            $content_result = $conn->query("SELECT content_id, title FROM content WHERE status = 'active'");
            while ($content = $content_result->fetch_assoc()) {
                $selected = $content['content_id'] == $content_id ? 'selected' : '';
                echo "<option value='{$content['content_id']}' $selected>{$content['title']}</option>";
            }
            ?>
        </select>
    </div>

    <!-- Cast & Crew Form -->
    <div class="p-4 shadow-sm rounded bg-white">
        <form method="post" action="../api/selectedcontent.php" id="castCrewForm">
            <input type="hidden" name="action" value="save_cast_crew">
            <input type="hidden" name="content_id" id="cast_crew_content_id">
            <div id="cast_crew_container">
                <div class="cast-crew-form">
                    <h5>Cast/Crew 1</h5>
                    <input type="hidden" name="cast_crew[0][cast_crew_id]" value="">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="cast_crew[0][name]" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" name="cast_crew[0][role]" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image URL</label>
                        <input type="text" class="form-control" name="cast_crew[0][image]">
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-secondary add-cast-crew-btn" onclick="addCastCrewForm()">Add Another Cast/Crew</button>
            <button type="submit" class="btn btn-primary mt-3">
                <i class="fas fa-save me-2"></i>Save Cast & Crew
            </button>
        </form>
    </div>

    <!-- Cast & Crew Table -->
    <h2 class="mt-5">Cast & Crew List</h2>
    <div class="table-responsive">
        <table id="castCrewTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="cast_crew_table_body"></tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
    // Load cast and crew when content is selected
    function loadCastCrew() {
        var contentId = document.getElementById('content_id').value;
        document.getElementById('cast_crew_content_id').value = contentId;
        if (contentId) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../api/selectedcontent.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    var container = document.getElementById('cast_crew_container');
                    container.innerHTML = '<div class="cast-crew-form"><h5>Cast/Crew 1</h5><input type="hidden" name="cast_crew[0][cast_crew_id]" value=""><div class="mb-3"><label class="form-label">Name</label><input type="text" class="form-control" name="cast_crew[0][name]" required></div><div class="mb-3"><label class="form-label">Role</label><input type="text" class="form-control" name="cast_crew[0][role]" required></div><div class="mb-3"><label class="form-label">Image URL</label><input type="text" class="form-control" name="cast_crew[0][image]"></div></div>';
                    var tableBody = document.getElementById('cast_crew_table_body');
                    tableBody.innerHTML = '';
                    if (response.status === 'success' && response.data.length > 0) {
                        response.data.forEach(function(item, index) {
                            if (index > 0) {
                                addCastCrewForm(item.cast_crew_id, item.name, item.role, item.image);
                            } else {
                                document.querySelector('input[name="cast_crew[0][cast_crew_id]"]').value = item.cast_crew_id || '';
                                document.querySelector('input[name="cast_crew[0][name]"]').value = item.name || '';
                                document.querySelector('input[name="cast_crew[0][role]"]').value = item.role || '';
                                document.querySelector('input[name="cast_crew[0][image]"]').value = item.image || '';
                            }
                            var row = `
                                <tr>
                                    <td>${item.name}</td>
                                    <td>${item.role}</td>
                                    <td>${item.image || 'N/A'}</td>
                                    <td>
                                        <form method="post" action="../api/selectedcontent.php" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_cast_crew">
                                            <input type="hidden" name="cast_crew_id" value="${item.cast_crew_id}">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>`;
                            tableBody.innerHTML += row;
                        });
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="4">No cast or crew found.</td></tr>';
                    }
                }
            };
            xhr.send('action=get_cast_crew&content_id=' + contentId);
        }
    }

    // Dynamically add cast/crew forms
    let castCrewCount = 0;
    function addCastCrewForm(castCrewId = '', name = '', role = '', image = '') {
        var container = document.getElementById('cast_crew_container');
        var index = ++castCrewCount;
        var castCrewForm = document.createElement('div');
        castCrewForm.className = 'cast-crew-form';
        castCrewForm.innerHTML = `
            <h5>Cast/Crew ${index + 1}</h5>
            <input type="hidden" name="cast_crew[${index}][cast_crew_id]" value="${castCrewId}">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="cast_crew[${index}][name]" value="${name}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <input type="text" class="form-control" name="cast_crew[${index}][role]" value="${role}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Image URL</label>
                <input type="text" class="form-control" name="cast_crew[${index}][image]" value="${image}">
            </div>
        `;
        container.appendChild(castCrewForm);
    }

    // Load cast/crew on page load if content_id is provided
    window.onload = function() {
        if (<?php echo $content_id; ?>) {
            document.getElementById('content_id').value = <?php echo $content_id; ?>;
            loadCastCrew();
        }
    };
</script>
</body>
</html>