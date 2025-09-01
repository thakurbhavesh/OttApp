<?php
session_start();
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="register-card">
        <h1>User Registration</h1>
        <?php
        // Display error message if set in session
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        ?>
        <form method="post" action="../api/auth.php" id="registerForm" class="p-2">
    <input type="hidden" name="action" value="register">

    <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" class="form-control" name="username" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" class="form-control" name="password" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-control" required>
            <option value="User">User</option>
            <option value="Admin">Admin</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-user-plus me-2"></i>Register
    </button>
</form>

        <div id="response" class="mt-3"></div>
    </div>
</div>

<button class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon"></i></button>

<?php include 'includes/footer.php'; ?>

