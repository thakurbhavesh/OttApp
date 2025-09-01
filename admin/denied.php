<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Denied</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            max-width: 500px;
        }
        .icon {
            font-size: 70px;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="card text-center p-5 bg-white">
        <div class="icon mb-3">
            <i class="fas fa-ban"></i>
        </div>
        <h1 class="text-danger fw-bold">Access Denied</h1>
        <p class="mt-3">🚫 Your IP address <?php echo $user_ip = $_SERVER['REMOTE_ADDR'];?> is not authorized to view this website.</p>
        <p>Please contact the administrator if you believe this is a mistake.</p>
        <a href="login.php" class="btn btn-danger mt-3 px-4">Go Back</a>
    </div>

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/your-kit-id.js" crossorigin="anonymous"></script>
</body>
</html>
