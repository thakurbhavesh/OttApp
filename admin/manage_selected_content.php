<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include '../api/config.php';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<style>
    .status-active { color: green !important; font-weight: bold; }
    .status-inactive { color: red !important; font-weight: bold; }
    .form-section { display: none; margin-top: 20px; }
    .episode-form { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
    .add-episode-btn { margin-top: 10px; }
    .table-responsive { overflow-x: auto; }
    @media (max-width: 768px) {
        .table th, .table td { font-size: 14px; padding: 8px; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
    }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.min.js"></script>

<div class="container-fluid">
    <h1 class="mt-4">Manage Selected Content</h1>

    <!-- Content Selection Dropdown -->
    <div class="mb-3">
        <label class="form-label">Select Content</label>
        <select class="form-control" id="content_id" name="content_id" onchange="showContentTypeDropdown()" required>
            <option value="">Select Content</option>
            <?php
            $content_result = $conn->query("SELECT content_id, title FROM content WHERE status = 'active'");
            while ($content = $content_result->fetch_assoc()) {
                echo "<option value='{$content['content_id']}'>{$content['title']}</option>";
            }
            ?>
        </select>
    </div>

    <!-- Single/Multi Content Type Dropdown -->
    <div class="mb-3" id="content_type_section" style="display: none;">
        <label class="form-label">Content Type</label>
        <select class="form-control" id="content_type" name="content_type" onchange="showContentForm()" required>
            <option value="">Select Type</option>
            <option value="single">Single</option>
            <option value="multi">Multi (Series)</option>
        </select>
    </div>

    <!-- Single Content Form -->
    <div id="single_form" class="form-section p-4 shadow-sm rounded bg-white">
        <form method="post" action="../api/selectedcontent.php" id="singleContentForm">
            <input type="hidden" name="action" value="add_single_content">
            <input type="hidden" name="id" id="single_id">
            <input type="hidden" name="content_id" id="single_content_id">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" class="form-control" name="title" id="single_title" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <div id="quill-editor" style="height: 200px;"></div>
                <input type="hidden" name="description" id="quill-description">
            </div>
            <div class="mb-3">
                <label class="form-label">Thumbnail URL</label>
                <input type="text" class="form-control" name="thumbnail_url" id="single_thumbnail_url" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Video URL</label>
                <input type="text" class="form-control" name="video_url" id="single_video_url" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Length (minutes)</label>
                <input type="number" class="form-control" name="length" id="single_length" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Release Date</label>
                <input type="date" class="form-control" name="release_date" id="single_release_date" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-control" name="status" id="single_status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" id="single_submit_button">
                <i class="fas fa-plus me-2"></i><span id="single_button_text">Add Single Content</span>
            </button>
            <a href="manage_selected_content.php" class="btn btn-secondary">Cancel</a>
            <a href="manage_cast_crew.php?content_id=" id="manage_cast_crew_link" class="btn btn-info">Manage Cast & Crew</a>
        </form>
    </div>

    <!-- Multi Content Form (Seasons and Episodes) -->
    <div id="multi_form" class="form-section p-4 shadow-sm rounded bg-white">
        <form method="post" action="../api/selectedcontent.php" id="multiContentForm">
            <input type="hidden" name="action" value="save_multi_content">
            <input type="hidden" name="content_id" id="multi_content_id">
            <div class="mb-3">
                <label class="form-label">Select Season</label>
                <select class="form-control" name="season_number" id="season_number" onchange="loadEpisodesForEdit()" required>
                    <option value="">Select Season</option>
                    <?php for ($i = 1; $i <= 50; $i++) {
                        echo "<option value='$i'>Season $i</option>";
                    } ?>
                </select>
            </div>
            <div id="episodes_container"></div>
            <button type="button" class="btn btn-secondary add-episode-btn" onclick="addEpisodeForm()">Add New Episode</button>
            <button type="submit" class="btn btn-primary mt-3">
                <i class="fas fa-save me-2"></i>Save Episodes
            </button>
            <a href="manage_cast_crew.php?content_id=" id="manage_cast_crew_link_multi" class="btn btn-info">Manage Cast & Crew</a>
        </form>
    </div>

    <!-- Content Table -->
    <h2 class="mt-5">All Content and Episodes</h2>
    <div class="mb-3">
        <label class="form-label">View Content/Episodes</label>
        <select class="form-control" id="view_content_id" onchange="loadContent()">
            <option value="">Select Content</option>
            <?php
            $content_result->data_seek(0);
            while ($content = $content_result->fetch_assoc()) {
                echo "<option value='{$content['content_id']}'>{$content['title']}</option>";
            }
            ?>
        </select>
    </div>
    <div class="table-responsive">
        <table id="contentTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>Main Category</th>
                    <th>Category</th>
                    <th>Content Title</th>
                    <th>Season</th>
                    <th>Title/Episode Title</th>
                    <th>Length (min)</th>
                    <th>Release Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="content_table_body"></tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
    // Initialize Quill editor for single content
    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'header': 1 }, { 'header': 2 }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'script': 'sub' }, { 'script': 'super' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                [{ 'direction': 'rtl' }],
                [{ 'size': ['small', false, 'large', 'huge'] }],
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'font': [] }],
                [{ 'align': [] }],
                ['clean']
            ]
        }
    });

    quill.on('text-change', function() {
        document.getElementById('quill-description').value = quill.root.innerHTML;
    });

    // Show content type dropdown when content is selected
    function showContentTypeDropdown() {
        var contentId = document.getElementById('content_id').value;
        var contentTypeSection = document.getElementById('content_type_section');
        var singleForm = document.getElementById('single_form');
        var multiForm = document.getElementById('multi_form');
        if (contentId) {
            // Check existing content type
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../api/selectedcontent.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status === 'success' && response.data.length > 0) {
                        var isMulti = response.data[0].season_number > 0;
                        contentTypeSection.style.display = 'block';
                        singleForm.style.display = 'none';
                        multiForm.style.display = 'none';
                        document.getElementById('single_content_id').value = contentId;
                        document.getElementById('multi_content_id').value = contentId;
                        document.getElementById('manage_cast_crew_link').href = 'manage_cast_crew.php?content_id=' + contentId;
                        document.getElementById('manage_cast_crew_link_multi').href = 'manage_cast_crew.php?content_id=' + contentId;
                        document.getElementById('content_type').value = isMulti ? 'multi' : 'single';
                        showContentForm(); // Auto-select based on existing type
                    } else {
                        contentTypeSection.style.display = 'block';
                        singleForm.style.display = 'none';
                        multiForm.style.display = 'none';
                        document.getElementById('single_content_id').value = contentId;
                        document.getElementById('multi_content_id').value = contentId;
                        document.getElementById('manage_cast_crew_link').href = 'manage_cast_crew.php?content_id=' + contentId;
                        document.getElementById('manage_cast_crew_link_multi').href = 'manage_cast_crew.php?content_id=' + contentId;
                    }
                }
            };
            xhr.send('action=get_content&content_id=' + contentId);
        } else {
            contentTypeSection.style.display = 'none';
            singleForm.style.display = 'none';
            multiForm.style.display = 'none';
            document.getElementById('manage_cast_crew_link').href = 'manage_cast_crew.php?content_id=';
            document.getElementById('manage_cast_crew_link_multi').href = 'manage_cast_crew.php?content_id=';
        }
    }

    // Show appropriate form based on content type
    function showContentForm() {
        var contentId = document.getElementById('content_id').value;
        var contentType = document.getElementById('content_type').value;
        var singleForm = document.getElementById('single_form');
        var multiForm = document.getElementById('multi_form');

        if (contentId) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../api/selectedcontent.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        var isMulti = response.data.some(item => item.season_number > 0);
                        if ((contentType === 'single' && !isMulti) || (contentType === 'multi' && isMulti)) {
                            if (contentType === 'single') {
                                singleForm.style.display = 'block';
                                multiForm.style.display = 'none';
                                resetSingleForm();
                                if (response.data.length > 0) {
                                    var item = response.data[0];
                                    document.getElementById('single_id').value = item.id;
                                    document.getElementById('single_title').value = item.title;
                                    quill.clipboard.dangerouslyPasteHTML(item.description || '');
                                    document.getElementById('quill-description').value = item.description || '';
                                    document.getElementById('single_thumbnail_url').value = item.thumbnail_url;
                                    document.getElementById('single_video_url').value = item.video_url;
                                    document.getElementById('single_length').value = item.length;
                                    document.getElementById('single_release_date').value = item.release_date;
                                    document.getElementById('single_status').value = item.status;
                                    document.getElementById('single_button_text').textContent = 'Update Single Content';
                                    document.querySelector('input[name="action"]').value = 'edit_content';
                                }
                            } else if (contentType === 'multi') {
                                singleForm.style.display = 'none';
                                multiForm.style.display = 'block';
                                document.getElementById('season_number').value = '';
                                document.getElementById('episodes_container').innerHTML = '';
                                episodeCount = 0;
                                loadEpisodesForEdit();
                            }
                        } else {
                            alert('Cannot change content type. This content is already ' + (isMulti ? 'multi' : 'single') + '.');
                            document.getElementById('content_type').value = isMulti ? 'multi' : 'single';
                        }
                    }
                }
            };
            xhr.send('action=get_content&content_id=' + contentId);
        } else {
            singleForm.style.display = 'none';
            multiForm.style.display = 'none';
        }
    }

    // Reset single content form for adding new content
    function resetSingleForm() {
        var form = document.getElementById('singleContentForm');
        form.querySelector('input[name="action"]').value = 'add_single_content';
        form.querySelector('input[name="id"]').value = '';
        form.querySelector('input[name="title"]').value = '';
        quill.setContents([]);
        document.getElementById('quill-description').value = '';
        form.querySelector('input[name="thumbnail_url"]').value = '';
        form.querySelector('input[name="video_url"]').value = '';
        form.querySelector('input[name="length"]').value = '';
        form.querySelector('input[name="release_date"]').value = '';
        form.querySelector('select[name="status"]').value = 'active';
        document.getElementById('single_button_text').textContent = 'Add Single Content';
    }

    // Dynamically add episode forms with Quill editor
    let episodeCount = 0;
    let quillInstances = [];
    function addEpisodeForm() {
        var container = document.getElementById('episodes_container');
        var index = episodeCount;
        var episodeNumber = container.childNodes.length + 1;
        var episodeForm = document.createElement('div');
        episodeForm.className = 'episode-form';
        episodeForm.innerHTML = `
            <h5>Episode ${episodeNumber}</h5>
            <input type="hidden" name="episodes[${index}][id]" value="">
            <input type="hidden" name="episodes[${index}][episode_number]" value="${episodeNumber}">
            <div class="mb-3">
                <label class="form-label">Episode Title</label>
                <input type="text" class="form-control" name="episodes[${index}][episode_title]" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <div id="quill-editor-${index}" style="height: 150px;"></div>
                <input type="hidden" name="episodes[${index}][description]" id="quill-description-${index}">
            </div>
            <div class="mb-3">
                <label class="form-label">Thumbnail URL</label>
                <input type="text" class="form-control" name="episodes[${index}][thumbnail_url]" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Video URL</label>
                <input type="text" class="form-control" name="episodes[${index}][video_url]" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Length (minutes)</label>
                <input type="number" class="form-control" name="episodes[${index}][length]" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Release Date</label>
                <input type="date" class="form-control" name="episodes[${index}][release_date]" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-control" name="episodes[${index}][status]" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        `;
        container.appendChild(episodeForm);

        var quillInstance = new Quill(`#quill-editor-${index}`, {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'header': 1 }, { 'header': 2 }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'script': 'sub' }, { 'script': 'super' }],
                    [{ 'indent': '-1' }, { 'indent': '+1' }],
                    [{ 'direction': 'rtl' }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'font': [] }],
                    [{ 'align': [] }],
                    ['clean']
                ]
            }
        });

        quillInstance.on('text-change', function() {
            document.getElementById(`quill-description-${index}`).value = quillInstance.root.innerHTML;
        });

        quillInstances.push(quillInstance);
        episodeCount++;
    }

    // Load episodes for editing in multi form
    function loadEpisodesForEdit() {
        var contentId = document.getElementById('multi_content_id').value;
        var seasonNumber = document.getElementById('season_number').value;
        if (!seasonNumber) {
            document.getElementById('episodes_container').innerHTML = '';
            episodeCount = 0;
            quillInstances = [];
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../api/selectedcontent.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                var container = document.getElementById('episodes_container');
                container.innerHTML = '';
                episodeCount = 0;
                quillInstances = [];
                if (response.status === 'success' && response.data.length > 0) {
                    response.data.forEach(function(item) {
                        if (item.season_number == seasonNumber) {
                            var index = episodeCount;
                            var episodeForm = document.createElement('div');
                            episodeForm.className = 'episode-form';
                            episodeForm.innerHTML = `
                                <h5>Episode ${item.episode_number}</h5>
                                <input type="hidden" name="episodes[${index}][id]" value="${item.id}">
                                <input type="hidden" name="episodes[${index}][episode_number]" value="${item.episode_number}">
                                <div class="mb-3">
                                    <label class="form-label">Episode Title</label>
                                    <input type="text" class="form-control" name="episodes[${index}][episode_title]" value="${item.title || ''}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <div id="quill-editor-${index}" style="height: 150px;"></div>
                                    <input type="hidden" name="episodes[${index}][description]" id="quill-description-${index}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Thumbnail URL</label>
                                    <input type="text" class="form-control" name="episodes[${index}][thumbnail_url]" value="${item.thumbnail_url || ''}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Video URL</label>
                                    <input type="text" class="form-control" name="episodes[${index}][video_url]" value="${item.video_url || ''}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Length (minutes)</label>
                                    <input type="number" class="form-control" name="episodes[${index}][length]" value="${item.length || ''}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Release Date</label>
                                    <input type="date" class="form-control" name="episodes[${index}][release_date]" value="${item.release_date || ''}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-control" name="episodes[${index}][status]" required>
                                        <option value="active" ${item.status == 'active' ? 'selected' : ''}>Active</option>
                                        <option value="inactive" ${item.status == 'inactive' ? 'selected' : ''}>Inactive</option>
                                    </select>
                                </div>
                            `;
                            container.appendChild(episodeForm);

                            var quillInstance = new Quill(`#quill-editor-${index}`, {
                                theme: 'snow',
                                modules: {
                                    toolbar: [
                                        ['bold', 'italic', 'underline', 'strike'],
                                        ['blockquote', 'code-block'],
                                        [{ 'header': 1 }, { 'header': 2 }],
                                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                                        [{ 'script': 'sub' }, { 'script': 'super' }],
                                        [{ 'indent': '-1' }, { 'indent': '+1' }],
                                        [{ 'direction': 'rtl' }],
                                        [{ 'size': ['small', false, 'large', 'huge'] }],
                                        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                                        [{ 'color': [] }, { 'background': [] }],
                                        [{ 'font': [] }],
                                        [{ 'align': [] }],
                                        ['clean']
                                    ]
                                }
                            });

                            if (item.description) {
                                quillInstance.clipboard.dangerouslyPasteHTML(item.description);
                            }
                            quillInstance.on('text-change', function() {
                                document.getElementById(`quill-description-${index}`).value = quillInstance.root.innerHTML;
                            });

                            quillInstances.push(quillInstance);
                            episodeCount++;
                        }
                    });
                }
            }
        };
        xhr.send('action=get_content&content_id=' + contentId + '&season_number=' + seasonNumber);
    }

    // Load content and episodes for the view table
    function loadContent() {
        var contentId = document.getElementById('view_content_id').value;
        var tableBody = document.getElementById('content_table_body');
        tableBody.innerHTML = '';

        if (contentId) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../api/selectedcontent.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        response.data.forEach(function(item) {
                            var title = item.season_number == 0 ? item.title : `Episode ${item.episode_number}: ${item.title}`;
                            var season = item.season_number == 0 ? 'N/A' : item.season_number;
                            var row = `
                                <tr>
                                    <td>${item.main_category_name}</td>
                                    <td>${item.category_name}</td>
                                    <td>${item.content_title}</td>
                                    <td>${season}</td>
                                    <td>${title}</td>
                                    <td>${item.length}</td>
                                    <td>${item.release_date}</td>
                                    <td class="${item.status == 'active' ? 'status-active' : 'status-inactive'}">${item.status}</td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick='editContent(${JSON.stringify(item)})'><i class="fas fa-edit"></i></button>
                                        <form method="post" action="../api/selectedcontent.php" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_content">
                                            <input type="hidden" name="id" value="${item.id}">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>`;
                            tableBody.innerHTML += row;
                        });
                        $('#contentTable').DataTable();
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="9">No content or episodes found.</td></tr>';
                    }
                }
            };
            xhr.send('action=get_content&content_id=' + contentId);
        }
    }

    // Edit content or episode
    function editContent(item) {
        document.getElementById('content_id').value = item.content_id;
        showContentTypeDropdown();
        if (item.season_number == 0) {
            document.getElementById('content_type').value = 'single';
            showContentForm();
            var form = document.getElementById('singleContentForm');
            form.querySelector('input[name="action"]').value = 'edit_content';
            form.querySelector('input[name="id"]').value = item.id;
            form.querySelector('input[name="content_id"]').value = item.content_id;
            form.querySelector('input[name="title"]').value = item.title || '';
            quill.setContents([]);
            if (item.description) {
                quill.clipboard.dangerouslyPasteHTML(item.description);
            }
            document.getElementById('quill-description').value = item.description || '';
            form.querySelector('input[name="thumbnail_url"]').value = item.thumbnail_url || '';
            form.querySelector('input[name="video_url"]').value = item.video_url || '';
            form.querySelector('input[name="length"]').value = item.length || '';
            form.querySelector('input[name="release_date"]').value = item.release_date || '';
            form.querySelector('select[name="status"]').value = item.status || 'active';
            document.getElementById('single_button_text').textContent = 'Update Single Content';
        } else {
            document.getElementById('content_type').value = 'multi';
            showContentForm();
            document.getElementById('season_number').value = item.season_number;
            loadEpisodesForEdit();
        }
    }
</script>
</body>
</html>