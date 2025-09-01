<div class="sidebar">
    <h4 class="text-center mb-4">OTT Admin</h4>
    <a href="../admin/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
    <a href="../admin/manage_content.php"><i class="fas fa-film me-2"></i>Manage Content</a>
    <a href="../admin/manage_users.php"><i class="fas fa-users me-2"></i>Manage Users</a>
    <a href="../admin/manage_categories.php"><i class="fas fa-folder me-2"></i>Manage Categories</a>
    <a href="../admin/upload_media.php"><i class="fas fa-upload me-2"></i>Upload Media</a>
    <a href="../admin/profile.php"><i class="fas fa-user me-2"></i>Profile</a>
    <a href="../api/logout.php" class="mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
</div>
<style>
    .sidebar {
        height: 100vh;
        width: 250px;
        position: fixed;
        top: 0;
        left: 0;
        background: linear-gradient(90deg, #1a73e8, #0d47a1);
        padding-top: 20px;
        color: #fff;
        transition: all 0.3s;
    }
    .sidebar a {
        color: #fff;
        padding: 10px 15px;
        text-decoration: none;
        display: block;
        font-size: 16px;
        transition: all 0.3s ease;
    }
    .sidebar a:hover {
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 5px;
        transform: translateY(-2px);
    }
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
            padding-bottom: 20px;
        }
        .sidebar a {
            padding: 10px;
            text-align: center;
        }
        .content {
            margin-left: 0;
            padding-top: 60px;
        }
    }
</style>