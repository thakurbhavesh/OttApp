<footer class="py-4 bg-dark text-white text-center">
    <div class="container">
        <p>&copy; 2025 OTT App. All rights reserved.</p>
    </div>
</footer>

<button class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon"></i></button>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    // Theme Toggle
    function toggleTheme() {
        document.body.classList.toggle('dark-mode');
        const icon = document.querySelector('.theme-toggle i');
        icon.classList.toggle('fa-moon');
        icon.classList.toggle('fa-sun');
        localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
    }

    // Load saved theme
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
        document.querySelector('.theme-toggle i').classList.replace('fa-moon', 'fa-sun');
    }

    // Initialize DataTable
    $(document).ready(function() {
        $('#usersTable').DataTable();
    });
    $(document).ready(function() {
        $('#contentTable').DataTable();
    });
    $(document).ready(function() {
        $('#categoriesTable').DataTable();
    });
    $(document).ready(function() {
        $('#mediaTable').DataTable();
    });
    $(document).ready(function() {
        $('#languageTable').DataTable();
    });
    $(document).ready(function() {
        $('#prefernceTable').DataTable();
    });
    $(document).ready(function() {
        $('#allTable').DataTable();
    });$(document).ready(function() {
        $('#allUsers').DataTable();
    });
</script>


<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('URL copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy: ', err);
            alert('Failed to copy URL.');
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.copy-url-btn, .copy-file-btn').forEach(button => {
            button.addEventListener('click', () => {
                const url = button.getAttribute('data-url');
                copyToClipboard(url);
            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.copy-btn').forEach(button => {
            button.addEventListener('click', () => {
                const url = button.getAttribute('data-url');
                navigator.clipboard.writeText(url).then(() => {
                    alert('URL copied to clipboard!');
                }).catch(err => {
                    console.error('Failed to copy: ', err);
                    alert('Failed to copy URL.');
                });
            });
        });
    });
</script>

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // Initialize Quill Editor
    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
                ['blockquote', 'code-block'],
                [{'list': 'ordered'}, {'list': 'bullet'}],
                [{'script': 'sub'}, {'script': 'super'}],      // superscript/subscript
                [{'indent': '-1'}, {'indent': '+1'}],          // outdent/indent
                [{'direction': 'rtl'}],                         // text direction
                [{'size': ['small', false, 'large', 'huge']}],  // custom dropdown
                [{'header': [1, 2, 3, 4, 5, 6, false]}],
                [{'color': []}, {'background': []}],          // dropdown with defaults from theme
                ['link', 'image', 'video'],
                ['clean']                                         // remove formatting button
            ]
        }
    });

    // Set initial content from textarea
    var initialContent = document.getElementById('editor1').value;
    if (initialContent) {
        quill.root.innerHTML = initialContent;
    }

    // Update textarea with Quill content before form submission
    function updateQuillContent() {
        var content = quill.root.innerHTML;
        document.getElementById('editor1').value = content;
    }

    // Optional: Log form data for debugging
    document.getElementById('contentForm').addEventListener('submit', function(e) {
        updateQuillContent();
        console.log('Form submitting with data:', new FormData(this));
    });
</script>