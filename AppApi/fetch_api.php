<?php
session_start(); // Session start karein

// Password check
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    if (!isset($_POST['password']) || $_POST['password'] !== '1111') {
        // Login form with Bootstrap
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Login - Text File Viewer</title>
            <!-- Bootstrap CSS CDN -->
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h2 class="card-title text-center mb-4">Login</h2>
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" name="password" id="password" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Login</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    // Password sahi hai, session set karein
    $_SESSION['authenticated'] = true;
}

// Session active hai, ab content show karein
$directory = "C:/xampp/htdocs/ott_app/AppApi/"; // Forward slash use karein, path correct karein

// Ensure karein ki path correct hai aur folder exist karta hai
if (!is_dir($directory)) {
    die("Error: Directory '$directory' nahi mili.");
}

// Sirf .txt files ko fetch karein
$txtFiles = glob($directory . "*.txt");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Text File Viewer</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .file-content {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: 'Courier New', Courier, monospace;
        }
        .file-header {
            color: #007bff;
            margin-bottom: 10px;
        }
        .separator {
            border: 0;
            height: 1px;
            background: #eee;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Text File Contents</h1>
        <?php
        if (!empty($txtFiles)) {
            foreach ($txtFiles as $file) {
                // File ka naam print karein
                echo '<div class="file-header"><h3>File: ' . htmlspecialchars(basename($file)) . '</h3></div>';
                
                // File ka content read karein
                $content = file_get_contents($file);
                if ($content !== false) {
                    // Content ko HTML-safe banaye aur stylish pre tag mein display karein
                    echo '<div class="file-content"><pre>' . htmlspecialchars($content) . '</pre></div>';
                } else {
                    echo '<p class="text-danger">Error: File ' . htmlspecialchars(basename($file)) . ' ko read nahi kar sake.</p>';
                }
                echo '<hr class="separator">';
            }
        } else {
            echo '<p class="text-center text-muted">Koi txt file nahi mili in folder: ' . htmlspecialchars($directory) . '</p>';
        }
        ?>
    </div>
</body>
</html>