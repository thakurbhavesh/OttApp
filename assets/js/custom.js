$(document).ready(function() {
    // Initialize CKEditor
    ClassicEditor.create(document.querySelector('#descriptionEditor'))
        .then(editor => {
            window.editor = editor;
        })
        .catch(error => {
            console.error(error);
        });

    // Add Content
    $('#addContentForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add_content');
        formData.append('description', window.editor.getData());

        $.ajax({
            url: '../api/content.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                response = JSON.parse(response);
                $('#response').html(`<div class="alert alert-${response.status === 'success' ? 'success' : 'danger'}">${response.message}</div>`);
                if (response.status === 'success') {
                    $('#addContentForm')[0].reset();
                    window.editor.setData('');
                    loadContent();
                }
            },
            error: function(xhr, status, error) {
                $('#response').html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
            }
        });
    });

    // Show Content
    function loadContent() {
        $.ajax({
            url: '../api/content.php',
            type: 'POST',
            data: { action: 'get_content' },
            success: function(response) {
                response = JSON.parse(response);
                if (response.status === 'success') {
                    let html = '';
                    response.data.forEach(item => {
                        html += `<tr>
                            <td>${item.title}</td>
                            <td>${item.category_name}</td>
                            <td>${item.release_date}</td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-btn" data-id="${item.content_id}"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="${item.content_id}"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>`;
                    });
                    $('#contentTable tbody').html(html);
                }
            }
        });
    }
    loadContent();

    // Delete Content
    $(document).on('click', '.delete-btn', function() {
        if (confirm('Are you sure you want to delete this content?')) {
            const contentId = $(this).data('id');
            $.ajax({
                url: '../api/content.php',
                type: 'POST',
                data: { action: 'delete_content', content_id: contentId },
                success: function(response) {
                    response = JSON.parse(response);
                    $('#response').html(`<div class="alert alert-${response.status === 'success' ? 'success' : 'danger'}">${response.message}</div>`);
                    loadContent();
                }
            });
        }
    });

    // Edit Content (Basic setup, expand as needed)
    $(document).on('click', '.edit-btn', function() {
        const contentId = $(this).data('id');
        $.ajax({
            url: '../api/content.php',
            type: 'POST',
            data: { action: 'get_content_by_id', content_id: contentId },
            success: function(response) {
                response = JSON.parse(response);
                if (response.status === 'success') {
                    const item = response.data[0];
                    $('#editContentModal #contentId').val(item.content_id);
                    $('#editContentModal #title').val(item.title);
                    window.editor.setData(item.description);
                    $('#editContentModal #category_id').val(item.category_id);
                    $('#editContentModal #thumbnail_url').val(item.thumbnail_url);
                    $('#editContentModal #video_url').val(item.video_url);
                    $('#editContentModal #duration').val(item.duration);
                    $('#editContentModal #release_date').val(item.release_date);
                    $('#editContentModal').modal('show');
                }
            }
        });
    });

    // Update Content
    $('#editContentForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update_content');
        formData.append('description', window.editor.getData());

        $.ajax({
            url: '../api/content.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                response = JSON.parse(response);
                $('#response').html(`<div class="alert alert-${response.status === 'success' ? 'success' : 'danger'}">${response.message}</div>`);
                if (response.status === 'success') {
                    $('#editContentModal').modal('hide');
                    loadContent();
                }
            }
        });
    });

    // Add Category
    $('#addCategoryForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add_category');

        $.ajax({
            url: '../api/category.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                response = JSON.parse(response);
                $('#categoryResponse').html(`<div class="alert alert-${response.status === 'success' ? 'success' : 'danger'}">${response.message}</div>`);
                if (response.status === 'success') {
                    $('#addCategoryForm')[0].reset();
                    loadCategories();
                }
            }
        });
    });

    // Load Categories
    function loadCategories() {
        $.ajax({
            url: '../api/category.php',
            type: 'POST',
            data: { action: 'get_categories' },
            success: function(response) {
                response = JSON.parse(response);
                if (response.status === 'success') {
                    let html = '<option value="">Select Category</option>';
                    response.data.forEach(category => {
                        html += `<option value="${category.category_id}">${category.name}</option>`;
                    });
                    $('#category_id, #editContentModal #category_id').html(html);
                }
            }
        });
    }
    loadCategories();
});