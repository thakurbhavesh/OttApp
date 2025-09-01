<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="../admin/dashboard.php">OTT Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link" href="../admin/dashboard.php">Dashboard</a>
                </li>

                <!-- Dropdown for Manage -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="manageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Manage
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="manageDropdown">
                        <li><a class="dropdown-item" href="../admin/manage_content.php">Manage Content</a></li>
                        <li><a class="dropdown-item" href="../admin/manage_selected_content.php">Manage Selected Content</a></li>
                        <li><a class="dropdown-item" href="../admin/manage_cast_crew.php">Manage Cast & Crew</a></li>
                        <!-- <li><a class="dropdown-item" href="../admin/manage_users.php">Manage Users</a></li> -->
                        <li><a class="dropdown-item" href="../admin/main_categories.php">Manage Main Categories</a></li>
                        <li><a class="dropdown-item" href="../admin/manage_categories.php">Manage Categories</a></li>
                        <li><a class="dropdown-item" href="../admin/manage_languages.php">Manage Language</a></li>
                        <li><a class="dropdown-item" href="../admin/manage_content_preferences.php">Manage Content Preferences</a></li>
                    </ul>
                </li>

                <!-- Dropdown for Manage Home -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="manageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Manage Home Content
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="manageDropdown">
                        <li><a class="dropdown-item" href="../admin/manage_home_banner.php">Manage Home Banner</a></li>
                        <li><a class="dropdown-item" href="../admin/manage_home_topshows.php">Manage Home Top Shows</a></li>
                        <li><a class="dropdown-item" href="../admin/manage_binge_worthy.php">Manage Home Binge Worthy</a></li>
                        <li><a class="dropdown-item" href="../admin/manage_bollywood_binge.php">Manage Home Bollywood Binge</a></li>
                        <li><a class="dropdown-item" href="../admin/manage_dubbed.php">Manage Home Hindi Dubbed</a></li>
                        
                    </ul>
                </li>

                <!-- Upload Media -->
                <li class="nav-item">
                    <a class="nav-link" href="../admin/upload_media.php">Upload Media</a>
                </li>

                <!-- Profile -->
                <li class="nav-item">
                    <a class="nav-link" href="../admin/manage_users.php">Manage Users</a>
                </li>
                
                <!-- Profile -->
                <li class="nav-item">
                    <a class="nav-link" href="../admin/manage_ips.php">Manage Ip's</a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="../admin/profile.php">Profile</a>
                </li>

                <!-- Logout -->
                <li class="nav-item">
                    <form method="post" action="../api/logout.php" class="d-inline">
                        <button type="submit" class="nav-link btn btn-link text-danger" style="color: red !important;">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
