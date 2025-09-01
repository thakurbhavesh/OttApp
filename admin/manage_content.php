<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include '../api/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/header.php'; ?>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <style>
        .status-active {
            color: green !important;
            font-weight: bold;
        }
        .status-inactive {
            color: red !important;
            font-weight: bold;
        }
        #description {
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container-fluid">
        <h1 class="mt-4">Manage Content</h1>

        <?php
        // Check if form should be shown (for add or edit)
        $show_form = isset($_GET['form']) && in_array($_GET['form'], ['add', 'edit']);
        $edit_mode = $show_form && $_GET['form'] == 'edit' && isset($_GET['id']);

        if ($edit_mode) {
            $content_id = (int)$_GET['id'];
            $stmt = $conn->prepare("SELECT c.*, cat.main_category_id 
                                    FROM content c 
                                    JOIN categories cat ON c.category_id = cat.category_id 
                                    WHERE c.content_id = ?");
            $stmt->bind_param("i", $content_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $content = $result->fetch_assoc();
            $stmt->close();
            if (!$content) {
                echo "<div class='alert alert-danger'>Content not found.</div>";
                $show_form = false;
                $edit_mode = false;
            } else {
                // Decode HTML entities to ensure proper display in Quill
                $content['description'] = html_entity_decode($content['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        // Fetch main_categories, languages, and preferences
        $main_categories_result = $conn->query("SELECT category_id, name FROM main_categories WHERE status = 'active'");
        $main_categories = $main_categories_result->fetch_all(MYSQLI_ASSOC);

        $languages_result = $conn->query("SELECT language_id, name FROM languages WHERE status = 'active'");
        $languages = $languages_result->fetch_all(MYSQLI_ASSOC);

        $preferences_result = $conn->query("SELECT preference_id, preference_name FROM content_preferences WHERE status = 1");
        $preferences = $preferences_result->fetch_all(MYSQLI_ASSOC);

        // Fetch categories based on selected main_category
        $selected_main_category = $edit_mode ? $content['main_category_id'] : (isset($_GET['main_category_id']) ? (int)$_GET['main_category_id'] : 0);
        $categories_query = "SELECT category_id, name FROM categories 
                            WHERE main_category_id = " . ($selected_main_category ? $selected_main_category : '0') . " 
                            AND status = 'active'";
        $categories_result = $conn->query($categories_query);
        $categories = $categories_result->fetch_all(MYSQLI_ASSOC);
        ?>

        <!-- Add/Edit Form -->
        <?php if ($show_form) { ?>
            <form method="post" action="../api/content.php" class="p-4 shadow-sm rounded bg-white" id="contentForm">
                <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_content' : 'add_content'; ?>">
                <?php if ($edit_mode) { ?>
                    <input type="hidden" name="content_id" value="<?php echo $content['content_id']; ?>">
                <?php } ?>
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" name="title" value="<?php echo $edit_mode ? htmlspecialchars($content['title']) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <div id="quill-editor" style="height: 200px;"></div>
                    <textarea id="description" name="description" class="form-control" required><?php echo $edit_mode ? htmlspecialchars($content['description']) : ''; ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Main Category</label>
                    <select class="form-control" name="main_category_id" id="main_category_id" onchange="loadCategories()" required>
                        <option value="">Select Main Category</option>
                        <?php foreach ($main_categories as $mc) {
                            $selected = $edit_mode && $mc['category_id'] == $content['main_category_id'] ? 'selected' : '';
                            echo "<option value='{$mc['category_id']}' $selected>{$mc['name']}</option>";
                        } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select class="form-control" name="category_id" id="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat) {
                            $selected = $edit_mode && $cat['category_id'] == $content['category_id'] ? 'selected' : '';
                            echo "<option value='{$cat['category_id']}' $selected>{$cat['name']}</option>";
                        } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Language</label>
                    <select class="form-control" name="language_id" required>
                        <option value="">Select Language</option>
                        <?php foreach ($languages as $lang) {
                            $selected = $edit_mode && $lang['language_id'] == $content['language_id'] ? 'selected' : '';
                            echo "<option value='{$lang['language_id']}' $selected>{$lang['name']}</option>";
                        } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Preference</label>
                    <select class="form-control" name="preference_id" required>
                        <option value="">Select Preference</option>
                        <?php foreach ($preferences as $pref) {
                            $selected = $edit_mode && $pref['preference_id'] == $content['preference_id'] ? 'selected' : '';
                            echo "<option value='{$pref['preference_id']}' $selected>{$pref['preference_name']}</option>";
                        } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Thumbnail URL</label>
                    <input type="text" class="form-control" name="thumbnail_url" value="<?php echo $edit_mode ? htmlspecialchars($content['thumbnail_url']) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Trailer URL</label>
                    <input type="text" class="form-control" name="trailer_url" value="<?php echo $edit_mode ? htmlspecialchars($content['trailer_url']) : ''; ?>" placeholder="Enter trailer URL" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Duration (minutes)</label>
                    <input type="number" class="form-control" name="duration" value="<?php echo $edit_mode ? $content['duration'] : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Release Date</label>
                    <input type="date" class="form-control" name="release_date" value="<?php echo $edit_mode ? $content['release_date'] : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Plan Type</label>
                    <select class="form-control" name="plan" required>
                        <option value="free" <?php echo $edit_mode && $content['plan'] == 'free' ? 'selected' : ''; ?>>Free</option>
                        <option value="paid" <?php echo $edit_mode && $content['plan'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Industry</label>
                    <select class="form-control" name="industry" required>
                        <option value="">Select Industry</option>
                        <?php
                        $industries = ['Bollywood', 'Hollywood', 'Tollywood (South - Telugu)', 'Kollywood (South - Tamil)', 'Mollywood (South - Malayalam)', 'Sandalwood (South - Kannada)', 'Bhojpuri', 'Punjabi', 'Marathi', 'International'];
                        foreach ($industries as $ind) {
                            $selected = $edit_mode && $content['industry'] == $ind ? 'selected' : '';
                            echo "<option value='{$ind}' $selected>{$ind}</option>";
                        }
                        ?>
                    </select>
                </div>
                <?php if ($edit_mode) { ?>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status" required>
                            <option value="active" <?php echo $content['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $content['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                <?php } ?>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-<?php echo $edit_mode ? 'edit' : 'plus'; ?> me-2"></i>
                    <?php echo $edit_mode ? 'Update Content' : 'Add Content'; ?>
                </button>
                <a href="manage_content.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php } else { ?>
            <!-- Add Content Button -->
            <a href="manage_content.php?form=add" class="btn btn-primary mb-3">
                <i class="fas fa-plus me-2"></i>Add Content
            </a>
        <?php } ?>

        <!-- Content Table -->
        <h2 class="mt-5">All Content</h2>
        <table id="contentTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Main Category</th>
                    <th>Category</th>
                    <th>Language</th>
                    <th>Preference</th>
                    <th>Plan Type</th>
                    <th>Industry</th>
                    <th>Release Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT c.*, cat.name AS category_name, mc.name AS main_category_name, l.name AS language_name, cp.preference_name 
                                        FROM content c 
                                        JOIN categories cat ON c.category_id = cat.category_id 
                                        JOIN main_categories mc ON cat.main_category_id = mc.category_id 
                                        LEFT JOIN languages l ON c.language_id = l.language_id 
                                        LEFT JOIN content_preferences cp ON c.preference_id = cp.preference_id");
                while ($row = $result->fetch_assoc()) {
                    $status_class = $row['status'] == 'active' ? 'status-active' : 'status-inactive';
                    echo "<tr>
                        <td>" . htmlspecialchars($row['title']) . "</td>
                        <td>" . htmlspecialchars($row['main_category_name'] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($row['category_name'] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($row['language_name'] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($row['preference_name'] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($row['plan'] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($row['industry'] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($row['release_date']) . "</td>
                        <td class='$status_class'>" . htmlspecialchars($row['status']) . "</td>
                        <td>
                            <a href='manage_content.php?form=edit&id={$row['content_id']}' class='btn btn-sm btn-warning'><i class='fas fa-edit'></i></a>
                            <form method='post' action='../api/content.php' style='display:inline;'>
                                <input type='hidden' name='action' value='delete_content'>
                                <input type='hidden' name='content_id' value='{$row['content_id']}'>
                                <button type='submit' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>
                            </form>
                            <form method='post' action='../api/content.php' style='display:inline;'>
                                <input type='hidden' name='action' value='toggle_status'>
                                <input type='hidden' name='content_id' value='{$row['content_id']}'>
                                <input type='hidden' name='current_status' value='{$row['status']}'>
                                <button type='submit' class='btn btn-sm btn-" . ($row['status'] == 'active' ? 'secondary' : 'success') . "'>
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
    <script>
 

    // Sync Quill content with textarea
    quill.on('text-change', function() {
        document.getElementById('description').value = quill.root.innerHTML;
    });

    // Load existing content in edit mode
    <?php if ($edit_mode) { ?>
        quill.root.innerHTML = <?php echo json_encode($content['description']); ?>;
    <?php } ?>

    function loadCategories() {
        var mainCategoryId = document.getElementById('main_category_id').value;
        var categorySelect = document.getElementById('category_id');
        categorySelect.innerHTML = '<option value="">Select Category</option>';

        if (mainCategoryId) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../api/get_categories.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    categorySelect.innerHTML = '<option value="">Select Category</option>' + xhr.responseText;
                    <?php if ($edit_mode) { ?>
                        categorySelect.value = '<?php echo $content['category_id']; ?>';
                    <?php } ?>
                }
            };
            xhr.send('main_category_id=' + mainCategoryId);
        }
    }

    // Trigger category load on page load in edit mode
    window.onload = function() {
        <?php if ($edit_mode) { ?>
            loadCategories();
        <?php } ?>
    };
    </script>
</body>
</html>