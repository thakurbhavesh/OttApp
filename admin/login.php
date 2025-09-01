<?php
session_start();
if (isset($_SESSION['auth_id'])) { // Corrected to auth_id, not user_id
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/header.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .container {
            max-width: 400px;
            margin: auto;
        }

        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            padding: 2rem;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1e3c72;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #2a5298;
            box-shadow: 0 0 5px rgba(42, 82, 152, 0.5);
        }

        .btn-primary {
            background: #2a5298;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #1e3c72;
        }

        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #2a5298;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            background: #1e3c72;
        }

        .dark-mode {
            background: linear-gradient(135deg, #0f172a, #1e293b);
        }

        .dark-mode .login-card {
            background: #1e293b;
            color: #e2e8f0;
        }

        .dark-mode .login-card h1 {
            color: #e2e8f0;
        }

        .dark-mode .form-control {
            background: #334155;
            border-color: #475569;
            color: #e2e8f0;
        }

        .dark-mode .form-control:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 5px rgba(96, 165, 250, 0.5);
        }

        .dark-mode .btn-primary {
            background: #60a5fa;
        }

        .dark-mode .btn-primary:hover {
            background: #3b82f6;
        }

        .register-link {
            text-align: center;
            margin-top: 1rem;
        }

        .register-link a {
            color: #2a5298;
            text-decoration: none;
            font-weight: 500;
        }

        .dark-mode .register-link a {
            color: #60a5fa;
        }

        .alert {
            border-radius: 8px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="login-card">
            <h1>Admin Login</h1>
            <form method="post" action="../api/auth.php" id="loginForm" class="p-2">
                <input type="hidden" name="action" value="login">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-sign-in-alt me-2"></i>Login</button>
            </form>
            <!-- <p class="register-link">Don't have an account? <a href="register.php">Register here</a></p> -->
            <div id="response" class="mt-3"></div>
        </div>
    </div>
    <button class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon"></i></button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

        // Form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const responseDiv = document.getElementById('response');

            try {
                const response = await fetch('../api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                responseDiv.innerHTML = `<div class="alert alert-${result.status === 'success' ? 'success' : 'danger'}">${result.message}</div>`;
                if (result.status === 'success') {
                    window.location.href = 'dashboard.php'; // Redirect to dashboard on success
                }
            } catch (error) {
                responseDiv.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            }
        });
    </script>
</body>
</html>